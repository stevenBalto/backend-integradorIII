<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pedido;
use App\Models\Resena;
use App\Repositories\ResenaRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio del modulo de reseñas.
 *
 * Reglas clave:
 *   - Solo se reseña un pedido propio y ya 'entregado'.
 *   - Reseña GENERAL: 1 por pedido. Reseña de PRODUCTO: solo productos que
 *     estan en ese pedido (o sea, que el cliente realmente compro), 1 por
 *     (pedido, producto).
 *   - El cliente puede "descartar" el pedido de reseña (re-prompt estilo Uber).
 */
final class ResenaService
{
    public function __construct(
        private readonly ResenaRepository $resenas,
        private readonly NotificacionService $notificaciones,
        private readonly ConfiguracionService $configuracion,
    ) {
    }

    // ── Cliente ──────────────────────────────────────────────────────────────

    /**
     * Pedidos ENTREGADOS del cliente que aun no reseño (general) y no descarto.
     * Alimenta el re-prompt tipo Uber. Cada pedido trae sus productos y cuales
     * ya fueron reseñados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendientes(int $userId): array
    {
        $pedidos = Pedido::query()
            ->where('cliente_id', $userId)
            ->where('estado', 'entregado')
            ->where('resena_descartada', false)
            ->whereDoesntHave('resenas', fn ($q) => $q->where('tipo', 'general'))
            ->with('detalles.producto:id,nombre')
            ->orderByDesc('updated_at')
            ->get();

        $pedidoIds = $pedidos->pluck('id')->values()->all();

        // Obtener productos ya reseñados para todos los pedidos en UN query (evita N+1).
        $resenadosMap = $this->resenas->productosResenadosPorPedidos($pedidoIds);

        return $pedidos->map(function (Pedido $pedido) use ($resenadosMap) {
            $yaResenados = $resenadosMap->get($pedido->id, collect())->toArray();

            $productos = $pedido->detalles
                ->filter(fn ($d) => $d->producto !== null)
                ->unique('producto_id')
                ->map(fn ($d) => [
                    'producto_id' => (int) $d->producto_id,
                    'nombre' => $d->producto->nombre,
                    'ya_resenado' => in_array((int) $d->producto_id, $yaResenados, true),
                ])
                ->values()
                ->all();

            // Tipo sugerido segun cantidad de productos unicos en el pedido:
            // - 1 solo producto => 'producto' (califica directamente ese producto)
            // - varios productos => 'general' (califica la experiencia del pedido completo)
            $tipoSugerido = count($productos) === 1 ? 'producto' : 'general';

            return [
                'pedido_id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'fecha' => $pedido->created_at?->toIso8601String(),
                'productos' => $productos,
                'tipo_sugerido' => $tipoSugerido,
            ];
        })->all();
    }

    /**
     * Registra la reseña general y/o las de producto de un pedido.
     *
     * @param array{calificacion: int, comentario?: string|null}|null $general
     * @param array<int, array{producto_id: int, calificacion: int, comentario?: string|null}> $productos
     * @return Collection<int, Resena>
     */
    public function resenar(int $userId, int $pedidoId, ?array $general, array $productos): Collection
    {
        $creadas = DB::transaction(function () use ($userId, $pedidoId, $general, $productos): Collection {
            /** @var Pedido|null $pedido */
            $pedido = Pedido::query()
                ->where('cliente_id', $userId)
                ->with('detalles:id,pedido_id,producto_id')
                ->find($pedidoId);

            if ($pedido === null) {
                throw ValidationException::withMessages([
                    'pedido_id' => ['El pedido no existe o no es tuyo.'],
                ]);
            }

            if ($pedido->estado !== 'entregado') {
                throw ValidationException::withMessages([
                    'pedido_id' => ['Solo podés reseñar un pedido que ya fue entregado.'],
                ]);
            }

            $creadas = new Collection();

            // Si el negocio revisa las reseñas antes de publicarlas (Configuración
            // → Reseñas), nacen 'oculta' y el admin las aprueba desde su panel.
            $estadoInicial = $this->configuracion->moderacionResenasActiva((int) $pedido->instancia_id)
                ? 'oculta'
                : 'publicada';

            // Reseña general (1 por pedido).
            if ($general !== null) {
                if ($this->resenas->existeGeneral($pedido->id)) {
                    throw ValidationException::withMessages([
                        'general' => ['Ya dejaste una reseña general de este pedido.'],
                    ]);
                }
                $creadas->push($this->resenas->crear([
                    'instancia_id' => $pedido->instancia_id,
                    'user_id' => $userId,
                    'producto_id' => null,
                    'pedido_id' => $pedido->id,
                    'tipo' => 'general',
                    'calificacion' => $general['calificacion'],
                    'comentario' => $general['comentario'] ?? null,
                    'estado' => $estadoInicial,
                ]));
            }

            // Reseñas de producto: solo productos comprados en ESTE pedido.
            if ($productos !== []) {
                $productosDelPedido = $pedido->detalles
                    ->pluck('producto_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();
                $yaResenados = $this->resenas->productosResenados($pedido->id);

                foreach ($productos as $p) {
                    $prodId = (int) $p['producto_id'];

                    if (! in_array($prodId, $productosDelPedido, true)) {
                        throw ValidationException::withMessages([
                            'productos' => ["No podés reseñar un producto que no compraste en este pedido."],
                        ]);
                    }
                    if (in_array($prodId, $yaResenados, true)) {
                        continue; // ya reseñado: se ignora en silencio (idempotente)
                    }

                    $creadas->push($this->resenas->crear([
                        'instancia_id' => $pedido->instancia_id,
                        'user_id' => $userId,
                        'producto_id' => $prodId,
                        'pedido_id' => $pedido->id,
                        'tipo' => 'producto',
                        'calificacion' => $p['calificacion'],
                        'comentario' => $p['comentario'] ?? null,
                        'estado' => $estadoInicial,
                    ]));
                }
            }

            return $creadas;
        });

        // Notificar a los admins de la instancia (fuera de la transaccion).
        try {
            $this->notificaciones->notificarResenaNueva($creadas);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de reseña nueva', ['error' => $e->getMessage()]);
        }

        return $creadas;
    }

    /** El cliente descarta el pedido de reseña: deja de insistir para ese pedido. */
    public function descartar(int $userId, int $pedidoId): void
    {
        $pedido = Pedido::query()->where('cliente_id', $userId)->find($pedidoId);

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'pedido_id' => ['El pedido no existe o no es tuyo.'],
            ]);
        }

        $pedido->update(['resena_descartada' => true]);
    }

    // ── Publico (ficha de producto) ──────────────────────────────────────────

    /** @return array{promedio: float, total: int, distribucion: array<int, int>} */
    public function resumenProducto(int $productoId): array
    {
        return $this->resenas->resumenProducto($productoId);
    }

    /** @return Collection<int, Resena> */
    public function opinionesProducto(int $productoId): Collection
    {
        return $this->resenas->visiblesDeProducto($productoId);
    }

    // ── Admin ────────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $filtros
     * @return Collection<int, Resena>|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listarAdmin(array $filtros, ?int $porPagina = null, int $pagina = 1)
    {
        return $this->resenas->listarAdmin($filtros, $porPagina, $pagina);
    }

    public function ocultar(int $id): Resena
    {
        return $this->cambiarEstado($id, 'oculta');
    }

    public function mostrar(int $id): Resena
    {
        return $this->cambiarEstado($id, 'publicada');
    }

    public function eliminar(int $id): void
    {
        $resena = $this->buscarOFallar($id);
        $this->resenas->eliminar($resena);
    }

    /** @return Collection<int, object> */
    public function statsPorProducto(): Collection
    {
        return $this->resenas->statsPorProducto();
    }

    private function cambiarEstado(int $id, string $estado): Resena
    {
        $resena = $this->buscarOFallar($id);

        return $this->resenas->actualizar($resena, ['estado' => $estado]);
    }

    private function buscarOFallar(int $id): Resena
    {
        $resena = $this->resenas->buscarPorId($id);

        if ($resena === null) {
            throw ValidationException::withMessages([
                'id' => ['La reseña no existe.'],
            ]);
        }

        return $resena;
    }
}

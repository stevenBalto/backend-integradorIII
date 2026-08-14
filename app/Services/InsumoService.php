<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Insumo\ActualizarInsumoDTO;
use App\DTOs\Insumo\CrearInsumoDTO;
use App\Models\Insumo;
use App\Models\InsumoMovimiento;
use App\Repositories\InsumoMovimientoRepository;
use App\Repositories\InsumoRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio del inventario de insumos (materia prima).
 *
 * Regla central: cantidad_actual NUNCA se edita por un update normal.
 * Solo cambia via registrarTomaFisica(), que deja rastro en insumo_movimientos.
 */
final class InsumoService
{
    public function __construct(
        private readonly InsumoRepository $insumos,
        private readonly InsumoMovimientoRepository $movimientos,
        private readonly NotificacionService $notificaciones,
    ) {}

    /** @return Collection<int, Insumo> */
    public function listarTodos(): Collection
    {
        return $this->insumos->listarTodos();
    }

    public function buscarPorId(int $id): Insumo
    {
        $insumo = $this->insumos->buscarPorId($id);

        if ($insumo === null) {
            throw ValidationException::withMessages([
                'id' => ['El insumo no existe.'],
            ]);
        }

        // Asegura que 'movimientos_count' este disponible para InsumoResource
        // (listarTodos() ya lo trae via withCount; esto cubre show/update/toma fisica).
        if (! array_key_exists('movimientos_count', $insumo->getAttributes())) {
            $insumo->loadCount('movimientos');
        }

        return $insumo;
    }

    public function crear(CrearInsumoDTO $dto): Insumo
    {
        return $this->insumos->crear($dto->toArray(), $this->resolverSucursalId($dto->sucursalId));
    }

    /**
     * El inventario es exclusivo de cada sede (no se comparte como productos/
     * ofertas/cupones). Un admin_sede NUNCA puede elegir la sede (se ignora
     * cualquier valor recibido y se fuerza la suya — anti sede-hopping, mismo
     * criterio que PerteneceASucursal). Un admin general SI debe indicarla.
     */
    private function resolverSucursalId(?int $sucursalIdSolicitado): int
    {
        $actor = Auth::user();

        if ($actor !== null && method_exists($actor, 'esAdminSede') && $actor->esAdminSede()) {
            if ($actor->sucursal_id === null) {
                throw ValidationException::withMessages([
                    'sucursal_id' => ['Tu usuario no tiene una sede asignada.'],
                ]);
            }

            return (int) $actor->sucursal_id;
        }

        if ($sucursalIdSolicitado === null) {
            throw ValidationException::withMessages([
                'sucursal_id' => ['Elegí a cuál sede pertenece este insumo.'],
            ]);
        }

        return $sucursalIdSolicitado;
    }

    public function actualizar(int $id, ActualizarInsumoDTO $dto): Insumo
    {
        $insumo = $this->buscarPorId($id);

        return $this->insumos->actualizar($insumo, $dto->toArray());
    }

    public function eliminar(int $id): void
    {
        $insumo = $this->buscarPorId($id);

        $this->insumos->eliminar($insumo);
    }

    /**
     * Registra una toma fisica: fija la cantidad contada como nueva cantidad_actual
     * del insumo y deja el ajuste auditado en insumo_movimientos.
     *
     * @return array{insumo: Insumo, movimiento: InsumoMovimiento}
     */
    public function registrarTomaFisica(int $insumoId, float $cantidadContada, ?string $nota, int $userId): array
    {
        $insumo = $this->buscarPorId($insumoId);

        $resultado = DB::transaction(function () use ($insumo, $cantidadContada, $nota, $userId): array {
            $cantidadAnterior = (float) $insumo->cantidad_actual;
            $diferencia = $cantidadContada - $cantidadAnterior;

            $insumoActualizado = $this->insumos->actualizarCantidad($insumo, $cantidadContada);

            $movimiento = $this->movimientos->crear([
                'insumo_id' => $insumo->id,
                'user_id' => $userId,
                'tipo' => 'toma_fisica',
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_nueva' => $cantidadContada,
                'diferencia' => $diferencia,
                'nota' => $nota,
            ]);

            return [
                'insumo' => $insumoActualizado,
                'movimiento' => $movimiento,
            ];
        });

        // Si tras la toma el insumo quedó en o bajo su minimo, avisar a los admins.
        try {
            if ($resultado['insumo']->bajoStock()) {
                $this->notificaciones->notificarStockBajo($resultado['insumo']);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de stock bajo', ['error' => $e->getMessage()]);
        }

        return $resultado;
    }

    /** @return Collection<int, InsumoMovimiento> */
    public function listarMovimientos(int $insumoId): Collection
    {
        // Valida que el insumo exista antes de listar su historial.
        $this->buscarPorId($insumoId);

        return $this->movimientos->listarPorInsumo($insumoId);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Extra\ActualizarExtraDTO;
use App\DTOs\Extra\CrearExtraDTO;
use App\Models\Extra;
use App\Models\Producto;
use App\Repositories\CategoriaRepository;
use App\Repositories\ExtraRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio de extras / acompañamientos.
 */
final class ExtraService
{
    public function __construct(
        private readonly ExtraRepository $extras,
        private readonly CategoriaRepository $categorias,
    ) {
    }

    /** @return Collection<int, Extra> */
    public function listarTodos(): Collection
    {
        return $this->extras->listarTodos();
    }

    /** @return Collection<int, Extra> Extras disponibles de una categoria. */
    public function listarPorCategoria(int $categoriaId): Collection
    {
        return $this->extras->listarPorCategoria($categoriaId);
    }

    public function buscarPorId(int $id): Extra
    {
        $extra = $this->extras->buscarPorId($id);

        if ($extra === null) {
            throw ValidationException::withMessages([
                'id' => ['El extra no existe.'],
            ]);
        }

        return $extra;
    }

    /** Como buscarPorId pero con los productos asignados puntualmente cargados. */
    public function buscarConAsignados(int $id): Extra
    {
        $extra = $this->extras->buscarConAsignados($id);

        if ($extra === null) {
            throw ValidationException::withMessages([
                'id' => ['El extra no existe.'],
            ]);
        }

        return $extra;
    }

    public function crear(CrearExtraDTO $dto): Extra
    {
        if (! $dto->esGeneral) {
            $this->validarCategoria($dto->categoriaId);
        }

        return $this->extras->crear($dto->toArray());
    }

    public function actualizar(int $id, ActualizarExtraDTO $dto): Extra
    {
        if (! $dto->esGeneral) {
            $this->validarCategoria($dto->categoriaId);
        }

        $extra = $this->buscarPorId($id);

        return $this->extras->actualizar($extra, $dto->toArray());
    }

    /**
     * Asigna puntualmente una extra (de categoria) a un producto especifico.
     * Una extra general ya aplica a todo, no admite asignacion puntual.
     */
    public function asignarAProducto(int $extraId, int $productoId): void
    {
        $extra = $this->buscarPorId($extraId);

        // findOrFail aplica el global scope de instancia: no se puede asignar a un
        // producto de otro tenant (aunque el exists del request lo dejara pasar).
        Producto::query()->findOrFail($productoId);

        if ($extra->es_general) {
            throw ValidationException::withMessages([
                'extra_id' => ['Esta extra ya es general, aplica a todos los productos automáticamente.'],
            ]);
        }

        $this->extras->asignarAProducto($extraId, $productoId);
    }

    /** Quita la asignacion puntual. Borrar una fila inexistente es un no-op inofensivo. */
    public function desasignarDeProducto(int $extraId, int $productoId): void
    {
        $this->extras->desasignarDeProducto($extraId, $productoId);
    }

    public function eliminar(int $id): void
    {
        $extra = $this->buscarPorId($id);

        if ($this->extras->estaReferenciado($id)) {
            throw ValidationException::withMessages([
                'id' => ['No se puede eliminar: este acompañamiento ya fue usado en algún pedido. Marcalo como no disponible en su lugar.'],
            ]);
        }

        $this->extras->eliminar($extra);
    }

    private function validarCategoria(?int $categoriaId): void
    {
        if ($categoriaId === null) {
            throw ValidationException::withMessages([
                'categoria_id' => ['La categoría es obligatoria si la extra no es general.'],
            ]);
        }

        if (! $this->categorias->existe($categoriaId)) {
            throw ValidationException::withMessages([
                'categoria_id' => ['La categoría seleccionada no existe.'],
            ]);
        }
    }
}

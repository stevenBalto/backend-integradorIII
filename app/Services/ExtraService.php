<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Extra\ActualizarExtraDTO;
use App\DTOs\Extra\CrearExtraDTO;
use App\Models\Extra;
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

    public function crear(CrearExtraDTO $dto): Extra
    {
        $this->validarCategoria($dto->categoriaId);

        return $this->extras->crear($dto->toArray());
    }

    public function actualizar(int $id, ActualizarExtraDTO $dto): Extra
    {
        $this->validarCategoria($dto->categoriaId);

        $extra = $this->buscarPorId($id);

        return $this->extras->actualizar($extra, $dto->toArray());
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

    private function validarCategoria(int $categoriaId): void
    {
        if (! $this->categorias->existe($categoriaId)) {
            throw ValidationException::withMessages([
                'categoria_id' => ['La categoría seleccionada no existe.'],
            ]);
        }
    }
}

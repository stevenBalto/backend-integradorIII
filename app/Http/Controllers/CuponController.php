<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Cupon\ActualizarCuponDTO;
use App\DTOs\Cupon\CrearCuponDTO;
use App\Http\Requests\Cupon\StoreCuponRequest;
use App\Http\Requests\Cupon\UpdateCuponRequest;
use App\Http\Requests\Cupon\ValidarCuponRequest;
use App\Http\Resources\CuponResource;
use App\Services\CloudinaryService;
use App\Services\CuponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints de administracion de cupones.
 */
final class CuponController extends Controller
{
    public function __construct(
        private readonly CuponService $cupones,
        private readonly CloudinaryService $cloudinary,
    ) {
    }

    /** GET /api/cupones — listado publico de cupones activos. */
    public function indexPublic(): JsonResponse
    {
        return CuponResource::collection($this->cupones->listarActivos())
            ->response();
    }

    /** GET /api/admin/cupones — listado completo para administracion. */
    public function indexAdmin(): JsonResponse
    {
        return CuponResource::collection($this->cupones->listarTodos())
            ->response();
    }

    /** GET /api/admin/cupones/{id} */
    public function show(int $id): CuponResource
    {
        return new CuponResource($this->cupones->buscarPorId($id));
    }

    /** POST /api/admin/cupones */
    public function store(StoreCuponRequest $request): JsonResponse
    {
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenCupon($request->file('imagen'))
            : null;

        $cupon = $this->cupones->crear(CrearCuponDTO::fromArray($request->validated()), $imagenUrl);

        return (new CuponResource($cupon))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/cupones/{id} (via POST + _method para poder mandar el archivo) */
    public function update(UpdateCuponRequest $request, int $id): CuponResource
    {
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenCupon($request->file('imagen'))
            : null;

        $cupon = $this->cupones->actualizar($id, ActualizarCuponDTO::fromArray($request->validated()), $imagenUrl);

        return new CuponResource($cupon);
    }

    /** DELETE /api/admin/cupones/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->cupones->eliminar($id);

        return response()->json(['message' => 'Cupon eliminado correctamente.']);
    }

    /**
     * POST /api/admin/cupones/validar — usado por el scanner QR del staff antes de
     * armar el pedido de mostrador: valida vigencia/usos y devuelve el descuento
     * estimado sobre un subtotal opcional (sin registrar el uso todavia).
     */
    public function validar(ValidarCuponRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $cupon = $this->cupones->buscarActivoPorCodigo($datos['codigo']);

        if ($cupon === null) {
            throw ValidationException::withMessages([
                'codigo' => ['Este cupón no existe, está vencido, inactivo o ya agotó sus usos.'],
            ]);
        }

        $subtotal = (float) ($datos['subtotal'] ?? 0);

        return (new CuponResource($cupon))->additional([
            'descuento_estimado' => $subtotal > 0 ? $this->cupones->calcularDescuento($cupon, $subtotal) : null,
        ])->response();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Oferta\ActualizarOfertaDTO;
use App\DTOs\Oferta\CrearOfertaDTO;
use App\Http\Requests\Oferta\StoreOfertaRequest;
use App\Http\Requests\Oferta\UpdateOfertaRequest;
use App\Http\Resources\OfertaResource;
use App\Services\CloudinaryService;
use App\Services\OfertaService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de administracion de ofertas.
 */
final class OfertaController extends Controller
{
    public function __construct(
        private readonly OfertaService $ofertas,
        private readonly CloudinaryService $cloudinary,
    ) {
    }

    /** GET /api/ofertas — listado publico de ofertas activas. */
    public function indexPublic(): JsonResponse
    {
        return OfertaResource::collection($this->ofertas->listarActivas())
            ->response();
    }

    /** GET /api/admin/ofertas — listado completo para administracion. */
    public function indexAdmin(): JsonResponse
    {
        return OfertaResource::collection($this->ofertas->listarTodos())
            ->response();
    }

    /** GET /api/admin/ofertas/{id} */
    public function show(int $id): OfertaResource
    {
        return new OfertaResource($this->ofertas->buscarPorId($id));
    }

    /** POST /api/admin/ofertas */
    public function store(StoreOfertaRequest $request): JsonResponse
    {
        // Imagen: archivo subido (→ Cloudinary) o una imagen por defecto del sistema (imagen_url string).
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenOferta($request->file('imagen'))
            : $request->input('imagen_url');

        $oferta = $this->ofertas->crear(CrearOfertaDTO::fromArray($request->validated()), $imagenUrl);

        return (new OfertaResource($oferta))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/ofertas/{id} (via POST + _method para poder mandar el archivo) */
    public function update(UpdateOfertaRequest $request, int $id): OfertaResource
    {
        // Imagen: archivo subido (→ Cloudinary), imagen por defecto (imagen_url string), o null = conservar la actual.
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenOferta($request->file('imagen'))
            : $request->input('imagen_url');

        $oferta = $this->ofertas->actualizar($id, ActualizarOfertaDTO::fromArray($request->validated()), $imagenUrl);

        return new OfertaResource($oferta);
    }

    /** DELETE /api/admin/ofertas/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->ofertas->eliminar($id);

        return response()->json(['message' => 'Oferta eliminada correctamente.']);
    }
}

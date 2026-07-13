<?php

declare(strict_types=1);

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

/**
 * Subida de imagenes a Cloudinary. Unico punto de contacto con el SDK.
 */
final class CloudinaryService
{
    private ?Cloudinary $cloudinary = null;

    /**
     * Conexion perezosa: no validar credenciales de Cloudinary hasta que
     * realmente se necesite subir una imagen. Evita que rutas de solo
     * lectura (ej. GET /productos publico) fallen por falta de config.
     */
    private function cliente(): Cloudinary
    {
        return $this->cloudinary ??= new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /** Sube la imagen y devuelve su secure_url. */
    public function subirImagenProducto(UploadedFile $archivo): string
    {
        $resultado = $this->cliente()->uploadApi()->upload($archivo->getRealPath(), [
            'folder' => 'rooster-pizza/productos',
        ]);

        return (string) $resultado['secure_url'];
    }
}

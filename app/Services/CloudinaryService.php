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
    private readonly Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
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
        $resultado = $this->cloudinary->uploadApi()->upload($archivo->getRealPath(), [
            'folder' => 'rooster-pizza/productos',
        ]);

        return (string) $resultado['secure_url'];
    }
}

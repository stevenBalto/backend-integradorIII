<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Normaliza la imagen que sube el usuario antes de guardarla en la BD.
 *
 * Por que normalizar y no guardar el archivo crudo:
 *   - Una foto de camara de celular pesa 3-8 MB y viene a 4000px. Como avatar
 *     se muestra a 88px: guardar el original seria desperdiciar espacio en la
 *     BD y ancho de banda en cada carga.
 *   - Re-encodear con GD tambien **descarta los metadatos EXIF**, que en una
 *     foto de celular incluyen GPS. Guardar eso seria filtrar la ubicacion del
 *     usuario dentro de un dato que el cree que es solo su cara.
 *
 * Usa GD, que es una extension de PHP ya habilitada — no un paquete Composer
 * nuevo (el proyecto los tiene prohibidos).
 */
final class FotoPerfilService
{
    /** Lado del cuadrado final, en px. */
    private const LADO = 512;

    /** Calidad JPEG de salida. 82 es el punto donde deja de notarse la perdida. */
    private const CALIDAD = 82;

    /** Tope del archivo que llega. El de la BD (3 MB) aplica al YA procesado. */
    private const MAX_BYTES_ENTRADA = 6 * 1024 * 1024;

    /**
     * Convierte el archivo subido en un JPEG cuadrado de 512px listo para la BD.
     *
     * @return array{contenido: string, mime: string, tamano_bytes: int}
     *
     * @throws ValidationException si la imagen no se puede leer o es demasiado grande
     */
    public function normalizar(UploadedFile $archivo): array
    {
        if ($archivo->getSize() > self::MAX_BYTES_ENTRADA) {
            throw ValidationException::withMessages([
                'foto' => ['La imagen no puede pesar más de 6 MB.'],
            ]);
        }

        $binario = @file_get_contents($archivo->getRealPath());

        if ($binario === false || $binario === '') {
            throw ValidationException::withMessages([
                'foto' => ['No pudimos leer la imagen.'],
            ]);
        }

        // imagecreatefromstring valida de verdad que sea una imagen: un .jpg
        // que en realidad es un PHP o un SVG con scripts falla aca. No confiar
        // en la extension ni en el mime que declara el cliente.
        $origen = @imagecreatefromstring($binario);

        if ($origen === false) {
            throw ValidationException::withMessages([
                'foto' => ['El archivo no es una imagen válida (usá JPG, PNG o WEBP).'],
            ]);
        }

        try {
            $origen = $this->corregirOrientacion($origen, $archivo->getRealPath());
            $destino = $this->recortarCuadradoYRedimensionar($origen);

            try {
                $contenido = $this->aJpeg($destino);
            } finally {
                imagedestroy($destino);
            }
        } finally {
            if (is_object($origen) || is_resource($origen)) {
                @imagedestroy($origen);
            }
        }

        return [
            'contenido' => $contenido,
            'mime' => 'image/jpeg',
            'tamano_bytes' => strlen($contenido),
        ];
    }

    /**
     * Aplica la rotacion que indica el EXIF.
     *
     * Sin esto las fotos tomadas con el celular en vertical salen acostadas: la
     * camara guarda el sensor tal cual y anota "girala 90°" en el EXIF, cosa
     * que GD ignora al leer el pixel crudo.
     *
     * @param  \GdImage  $imagen
     * @return \GdImage
     */
    private function corregirOrientacion($imagen, string $ruta)
    {
        if (! function_exists('exif_read_data')) {
            return $imagen;
        }

        // Un JPEG sin EXIF hace que exif_read_data emita warning: se silencia a
        // proposito, no tener EXIF es lo normal y no es un error.
        $exif = @exif_read_data($ruta);

        $orientacion = is_array($exif) && isset($exif['Orientation'])
            ? (int) $exif['Orientation']
            : 1;

        $grados = match ($orientacion) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($grados === 0) {
            return $imagen;
        }

        $rotada = imagerotate($imagen, (float) $grados, 0);

        if ($rotada === false) {
            return $imagen;
        }

        imagedestroy($imagen);

        return $rotada;
    }

    /**
     * Recorta el cuadrado central y lo escala a LADO x LADO.
     *
     * Se recorta al centro (en vez de deformar la foto a la fuerza) porque el
     * avatar se muestra siempre en un circulo/cuadrado: estirar una foto
     * vertical a cuadrada le achata la cara al usuario.
     *
     * @param  \GdImage  $origen
     * @return \GdImage
     */
    private function recortarCuadradoYRedimensionar($origen)
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $lado = min($ancho, $alto);

        $x = intdiv($ancho - $lado, 2);
        $y = intdiv($alto - $lado, 2);

        $destino = imagecreatetruecolor(self::LADO, self::LADO);

        // Fondo blanco: la salida es JPEG, que no tiene canal alfa. Sin esto,
        // un PNG con transparencia queda con el fondo en negro.
        $blanco = imagecolorallocate($destino, 255, 255, 255);
        imagefilledrectangle($destino, 0, 0, self::LADO, self::LADO, $blanco);

        imagecopyresampled(
            $destino, $origen,
            0, 0,
            $x, $y,
            self::LADO, self::LADO,
            $lado, $lado,
        );

        return $destino;
    }

    /**
     * @param  \GdImage  $imagen
     */
    private function aJpeg($imagen): string
    {
        ob_start();
        imagejpeg($imagen, null, self::CALIDAD);

        return (string) ob_get_clean();
    }
}

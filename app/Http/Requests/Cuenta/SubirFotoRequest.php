<?php

declare(strict_types=1);

namespace App\Http\Requests\Cuenta;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Subida de la foto de perfil.
 *
 * La regla `image` de Laravel ya rechaza lo que no sea imagen, pero igual el
 * FotoPerfilService vuelve a validar con imagecreatefromstring antes de
 * guardar: la validacion mira la cabecera del archivo, y un binario preparado
 * puede pasarla. La defensa real es que GD logre decodificarlo y lo
 * re-encodee.
 */
final class SubirFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                // 6144 KB = 6 MB. Coincide con MAX_BYTES_ENTRADA del service;
                // aca corta antes de leer el archivo entero a memoria.
                'max:6144',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Elegí una imagen.',
            'foto.image' => 'El archivo tiene que ser una imagen.',
            'foto.mimes' => 'Usá una imagen JPG, PNG o WEBP.',
            'foto.max' => 'La imagen no puede pesar más de 6 MB.',
        ];
    }
}

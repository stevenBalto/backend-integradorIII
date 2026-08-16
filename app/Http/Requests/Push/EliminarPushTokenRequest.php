<?php

declare(strict_types=1);

namespace App\Http\Requests\Push;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de la baja de un token de dispositivo (logout del cliente).
 */
class EliminarPushTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'El token del dispositivo es obligatorio.',
            'token.max' => 'El token no puede superar los 255 caracteres.',
        ];
    }
}

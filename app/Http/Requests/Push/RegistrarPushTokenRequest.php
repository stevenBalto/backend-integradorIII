<?php

declare(strict_types=1);

namespace App\Http\Requests\Push;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion del alta de un token de dispositivo para push (FCM).
 */
class RegistrarPushTokenRequest extends FormRequest
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
            'plataforma' => ['required', 'string', 'in:android,ios'],
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
            'plataforma.required' => 'La plataforma es obligatoria.',
            'plataforma.in' => 'La plataforma debe ser: android o ios.',
        ];
    }
}

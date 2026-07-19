<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de ajustes del Home. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateHomeConfigRequest extends FormRequest
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
            'oferta_hero_id' => ['nullable', 'integer', 'exists:ofertas,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'oferta_hero_id.exists' => 'La oferta seleccionada no existe.',
        ];
    }
}

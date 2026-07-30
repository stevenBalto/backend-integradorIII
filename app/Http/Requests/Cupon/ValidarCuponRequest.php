<?php

declare(strict_types=1);

namespace App\Http\Requests\Cupon;

use Illuminate\Foundation\Http\FormRequest;

/** Validacion de canje de cupon (scanner QR admin / checkout). */
class ValidarCuponRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:20'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del cupón es obligatorio.',
        ];
    }
}

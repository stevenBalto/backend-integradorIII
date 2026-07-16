<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedido;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de cambio de estado de pedido (admin).
 */
class CambiarEstadoPedidoRequest extends FormRequest
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
            'estado' => ['required', 'string', 'in:pendiente,en_proceso,listo,entregado,cancelado'],
            'comentario' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser: pendiente, en_proceso, listo, entregado o cancelado.',
            'comentario.max' => 'El comentario no puede superar los 200 caracteres.',
        ];
    }
}

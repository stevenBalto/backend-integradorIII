<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedido;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de reversion de estado de pedido (admin): deshacer a un estado anterior.
 */
class RevertirEstadoPedidoRequest extends FormRequest
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
        ];
    }
}

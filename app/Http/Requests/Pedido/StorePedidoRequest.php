<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedido;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de creacion de pedido por el cliente.
 */
class StorePedidoRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'modalidad' => ['required', 'string', 'in:para_llevar,comer_aqui'],
            'notas' => ['nullable', 'string', 'max:300'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.producto_tamano_id' => ['nullable', 'integer'],
            'items.*.extra_ids' => ['nullable', 'array'],
            'items.*.extra_ids.*' => ['integer'],
            'items.*.notas' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'modalidad.required' => 'La modalidad es obligatoria.',
            'modalidad.in' => 'La modalidad debe ser "para_llevar" o "comer_aqui".',
            'notas.max' => 'Las notas no pueden superar los 300 caracteres.',
            'items.required' => 'El pedido debe tener al menos un producto.',
            'items.min' => 'El pedido debe tener al menos un producto.',
            'items.*.producto_id.required' => 'Cada producto debe tener un ID.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
            'items.*.notas.max' => 'Las notas del producto no pueden superar los 200 caracteres.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de los ajustes generales del panel admin (por instancia).
 * Todos opcionales: se guardan solo las claves presentes.
 */
class GuardarAjustesRequest extends FormRequest
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
            'negocio_nombre' => ['sometimes', 'nullable', 'string', 'max:120'],
            'negocio_telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'negocio_direccion' => ['sometimes', 'nullable', 'string', 'max:200'],
            'negocio_sitio_web' => ['sometimes', 'nullable', 'string', 'max:120'],
            'horario_apertura' => ['sometimes', 'nullable', 'string', 'max:10'],
            'horario_cierre' => ['sometimes', 'nullable', 'string', 'max:10'],
            'iva_porcentaje' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'notif_nuevos_pedidos' => ['sometimes', 'boolean'],
            'notif_resenas_nuevas' => ['sometimes', 'boolean'],
            'notif_stock_bajo' => ['sometimes', 'boolean'],
        ];
    }
}

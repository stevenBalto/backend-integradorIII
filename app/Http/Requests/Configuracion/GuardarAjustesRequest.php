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
            'negocio_maps_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            // Operación
            'pedidos_activos' => ['sometimes', 'boolean'],
            'modalidad_comer_aqui' => ['sometimes', 'boolean'],
            'modalidad_para_llevar' => ['sometimes', 'boolean'],
            'pedido_monto_minimo' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            // Horario
            'horario_activo' => ['sometimes', 'boolean'],
            'horario_apertura' => ['sometimes', 'nullable', 'string', 'max:10'],
            'horario_cierre' => ['sometimes', 'nullable', 'string', 'max:10'],
            'cerrado_temporalmente' => ['sometimes', 'boolean'],

            // Roosters (puntos)
            'roosters_activo' => ['sometimes', 'boolean'],
            'roosters_porcentaje' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],

            // Reseñas
            'resenas_moderacion' => ['sometimes', 'boolean'],
            'resenas_umbral_destacado' => ['sometimes', 'nullable', 'numeric', 'between:1,5'],

            'notif_producto_nuevo' => ['sometimes', 'boolean'],
            'notif_cliente_nuevo' => ['sometimes', 'boolean'],
            'notif_usuario_nuevo' => ['sometimes', 'boolean'],
            'notif_nuevos_pedidos' => ['sometimes', 'boolean'],
            'notif_resenas_nuevas' => ['sometimes', 'boolean'],
            'notif_stock_bajo' => ['sometimes', 'boolean'],
        ];
    }
}

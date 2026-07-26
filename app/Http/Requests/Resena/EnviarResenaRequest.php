<?php

declare(strict_types=1);

namespace App\Http\Requests\Resena;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion del envio de reseñas de un pedido (general y/o por producto).
 */
class EnviarResenaRequest extends FormRequest
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
            'general' => ['nullable', 'array'],
            'general.calificacion' => ['required_with:general', 'integer', 'between:1,5'],
            'general.comentario' => ['nullable', 'string', 'max:500'],

            'productos' => ['nullable', 'array'],
            'productos.*.producto_id' => ['required', 'integer'],
            'productos.*.calificacion' => ['required', 'integer', 'between:1,5'],
            'productos.*.comentario' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Debe venir al menos una reseña (general o de algun producto). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $general = $this->input('general');
            $productos = $this->input('productos', []);
            if (empty($general) && empty($productos)) {
                $v->errors()->add('general', 'Dejá al menos una calificación (general o de un producto).');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'general.calificacion.between' => 'La calificación debe ser de 1 a 5 estrellas.',
            'general.calificacion.required_with' => 'Falta la calificación general.',
            'productos.*.calificacion.between' => 'La calificación debe ser de 1 a 5 estrellas.',
            'productos.*.producto_id.required' => 'Falta el producto de la reseña.',
        ];
    }
}

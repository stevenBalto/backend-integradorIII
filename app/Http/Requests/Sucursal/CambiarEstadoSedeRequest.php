<?php

declare(strict_types=1);

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cierre / reapertura de una sede. La autorizacion la aplica el middleware de ruta.
 */
class CambiarEstadoSedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza el booleano (puede llegar como "1"/"0"/"true"). */
    protected function prepareForValidation(): void
    {
        if ($this->has('activa')) {
            $this->merge(['activa' => filter_var($this->input('activa'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activa' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'activa.required' => 'Indicá si la sede queda abierta o cerrada.',
        ];
    }
}

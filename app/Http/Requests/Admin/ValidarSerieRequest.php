<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 5.7.3: validación del número de serie antes de asignarlo a una línea del POS.
 */
class ValidarSerieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear ventas');
    }

    public function rules(): array
    {
        return [
            'numero_serie_id' => ['required', 'integer', 'exists:numeros_serie,id'],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_serie_id.required' => 'Seleccioná el número de serie.',
            'numero_serie_id.integer' => 'El número de serie no es válido.',
            'numero_serie_id.exists' => 'El número de serie no existe.',
            'producto_id.required' => 'Cada línea necesita un producto.',
            'producto_id.integer' => 'El producto no es válido.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
        ];
    }
}

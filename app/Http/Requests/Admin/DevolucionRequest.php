<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear devoluciones') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'operacion_id' => ['required', 'uuid'],
            'motivo' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.venta_detalle_id' => ['required', 'integer', 'exists:detalle_ventas,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'operacion_id.required' => 'Falta la clave de operación de la devolución.',
            'operacion_id.uuid' => 'La clave de operación no es válida.',
            'motivo.required' => 'Indicá el motivo de la devolución.',
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
            'items.required' => 'Seleccioná al menos un producto a devolver.',
            'items.min' => 'Seleccioná al menos un producto a devolver.',
            'items.*.venta_detalle_id.exists' => 'Una de las líneas de venta no existe.',
            'items.*.cantidad.required' => 'Indicá la cantidad a devolver.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad a devolver debe ser mayor que cero.',
        ];
    }
}

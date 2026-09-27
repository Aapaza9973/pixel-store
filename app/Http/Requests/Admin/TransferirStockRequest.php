<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransferirStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editar productos');
    }

    public function rules(): array
    {
        return [
            'ubicacion_origen_id' => ['required', 'exists:ubicaciones,id'],
            'ubicacion_destino_id' => ['required', 'exists:ubicaciones,id', 'different:ubicacion_origen_id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ubicacion_origen_id.required' => 'Seleccione la ubicación de origen.',
            'ubicacion_origen_id.exists' => 'La ubicación de origen no existe.',
            'ubicacion_destino_id.required' => 'Seleccione la ubicación de destino.',
            'ubicacion_destino_id.exists' => 'La ubicación de destino no existe.',
            'ubicacion_destino_id.different' => 'El destino debe ser distinto del origen.',
            'cantidad.required' => 'Indique la cantidad a transferir.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor que cero.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crear cotizaciones');
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'nombre_cliente' => ['required', 'string', 'max:255'],
            'nit_ci' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'validez_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notas' => ['nullable', 'string'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['nullable', 'exists:productos,id'],
            'items.*.nombre' => ['required', 'string', 'max:255'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
            'items.required' => 'La cotización debe tener al menos un ítem.',
            'items.min' => 'La cotización debe tener al menos un ítem.',
            'items.*.nombre.required' => 'El nombre de cada ítem es obligatorio.',
            'items.*.precio_unitario.required' => 'El precio de cada ítem es obligatorio.',
            'items.*.cantidad.required' => 'La cantidad de cada ítem es obligatoria.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'validez_dias' => $this->integer('validez_dias') ?: 4,
            'descuento' => $this->input('descuento') !== null ? (float) $this->input('descuento') : 0,
        ]);
    }
}

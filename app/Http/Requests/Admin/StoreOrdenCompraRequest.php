<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de una orden de compra desde el panel (3.4.9).
 *
 * La orden nace en 'borrador'; el envío al proveedor y la recepción son
 * operaciones posteriores.
 */
class StoreOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('crear ordenes-compra');
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha_entrega' => ['nullable', 'date'],
            'impuestos' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Seleccioná un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'fecha_entrega.date' => 'La fecha de entrega no es válida.',
            'impuestos.min' => 'Los impuestos no pueden ser negativos.',
            'items.required' => 'La orden necesita al menos un ítem.',
            'items.min' => 'La orden necesita al menos un ítem.',
            'items.*.producto_id.required' => 'Cada línea necesita un producto.',
            'items.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'items.*.cantidad.required' => 'Cada línea necesita una cantidad.',
            'items.*.cantidad.min' => 'La cantidad de cada ítem debe ser al menos 1.',
            'items.*.precio_unitario.required' => 'Cada línea necesita un precio.',
            'items.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'impuestos' => $this->input('impuestos') !== null ? (float) $this->input('impuestos') : 0,
            'fecha_entrega' => $this->filled('fecha_entrega') ? $this->input('fecha_entrega') : null,
        ]);
    }
}

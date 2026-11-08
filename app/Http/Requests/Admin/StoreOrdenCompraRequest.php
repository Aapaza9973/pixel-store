<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')->where(fn ($query) => $query->where('activo', true)->whereIn('tipo', ['Mayorista', 'Importador']))],
            'fecha_emision' => ['required', 'date'],
            'fecha_entrega' => ['required', 'date', 'after_or_equal:fecha_emision'],
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
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_entrega.required' => 'La fecha estimada de entrega es obligatoria.',
            'fecha_entrega.date' => 'La fecha de entrega no es válida.',
            'fecha_entrega.after_or_equal' => 'La entrega no puede ser anterior a la emisión.',
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
            'fecha_emision' => $this->input('fecha_emision') ?: now()->toDateString(),
            'fecha_entrega' => $this->input('fecha_entrega'),
        ]);
    }
}

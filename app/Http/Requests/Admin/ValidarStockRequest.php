<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 5.4.2: revalidación de stock antes de confirmar la venta.
 */
class ValidarStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear ventas');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'No se recibieron productos para validar.',
            'items.min' => 'No se recibieron productos para validar.',
            'items.*.producto_id.required' => 'Cada línea necesita un producto.',
            'items.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'items.*.cantidad.required' => 'Cada línea necesita una cantidad.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser al menos 1.',
        ];
    }
}

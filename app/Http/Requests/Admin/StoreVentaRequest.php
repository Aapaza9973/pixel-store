<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirmación de venta desde el POS.
 *
 * El cliente envía el descuento como PORCENTAJE; el monto en bolivianos y el
 * total los recalcula el servidor a partir de los precios de la base.
 */
class StoreVentaRequest extends FormRequest
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
            'items.*.numero_serie_id' => ['nullable', 'integer', 'exists:numeros_serie,id'],
            'items.*.ubicacion_id' => ['nullable', 'integer', 'exists:ubicaciones,id'],

            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.metodo' => ['required', 'in:Efectivo,Tarjeta,Transferencia,QR,Otro'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],

            'meta' => ['sometimes', 'array'],
            'meta.cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'meta.vendedor_id' => ['nullable', 'integer', 'exists:users,id'],
            'meta.operacion_id' => ['nullable', 'string', 'max:64'],
            // Alias plano de meta.operacion_id (contrato del POS).
            'operacion_id' => ['nullable', 'string', 'max:64'],

            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'autorizado_por' => ['nullable', 'integer', 'exists:users,id'],
            // Autorización pendiente (HU-5.11) que cubre el descuento pedido.
            'autorizacion_id' => ['nullable', 'integer', 'exists:autorizaciones_descuento,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'La venta debe incluir al menos un producto.',
            'items.min' => 'La venta debe incluir al menos un producto.',
            'items.*.producto_id.required' => 'Cada línea necesita un producto.',
            'items.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'items.*.cantidad.required' => 'Cada línea necesita una cantidad.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser al menos 1.',
            'items.*.numero_serie_id.exists' => 'El número de serie seleccionado no existe.',
            'pagos.required' => 'La venta debe registrar al menos un pago.',
            'pagos.min' => 'La venta debe registrar al menos un pago.',
            'pagos.*.monto.required' => 'Cada pago necesita un monto.',
            'pagos.*.monto.min' => 'El monto de cada pago debe ser mayor que cero.',
            'pagos.*.metodo.required' => 'Cada pago necesita un método.',
            'pagos.*.metodo.in' => 'El método debe ser Efectivo, Tarjeta, Transferencia, QR u Otro.',
            'descuento_porcentaje.numeric' => 'El descuento debe ser un número.',
            'descuento_porcentaje.min' => 'El descuento no puede ser negativo.',
            'descuento_porcentaje.max' => 'El descuento no puede superar el 100 %.',
            'meta.cliente_id.exists' => 'El cliente seleccionado no existe.',
            'meta.vendedor_id.exists' => 'El vendedor seleccionado no existe.',
            'autorizado_por.exists' => 'El usuario autorizante no existe.',
            'autorizacion_id.integer' => 'La autorización de descuento no es válida.',
            'autorizacion_id.exists' => 'La autorización de descuento no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $meta = $this->input('meta');

        if (! is_array($meta)) {
            $meta = [];
        }

        if (! isset($meta['operacion_id']) && $this->filled('operacion_id')) {
            $meta['operacion_id'] = $this->input('operacion_id');
        }

        $this->merge([
            'meta' => $meta,
            'descuento_porcentaje' => $this->input('descuento_porcentaje') ?? 0,
        ]);
    }
}

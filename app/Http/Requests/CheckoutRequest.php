<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],

            'nombre' => ['required', 'string', 'max:255'],
            'nit_ci' => ['required', 'string', 'max:20'],
            'telefono' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'El carrito está vacío.',
            'items.min' => 'El carrito está vacío.',
            'items.max' => 'Un pedido no puede superar los 30 productos distintos.',
            'items.*.producto_id.required' => 'Uno de los productos del carrito no es válido.',
            'items.*.producto_id.exists' => 'Uno de los productos ya no está disponible.',
            'items.*.cantidad.required' => 'Falta la cantidad de uno de los productos.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor que cero.',
            'items.*.cantidad.max' => 'La cantidad máxima por producto es 99.',

            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 255 caracteres.',
            'nit_ci.required' => 'El NIT/CI es obligatorio.',
            'nit_ci.max' => 'El NIT/CI no puede superar 20 caracteres.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 7 caracteres.',
            'telefono.max' => 'El teléfono no puede superar 30 caracteres.',
            'telefono.regex' => 'El teléfono contiene caracteres no válidos.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'direccion.required' => 'La dirección de entrega es obligatoria.',
            'direccion.max' => 'La dirección no puede superar 255 caracteres.',
        ];
    }
}

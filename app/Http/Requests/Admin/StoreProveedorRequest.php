<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear proveedores');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20'],
            'contacto' => ['nullable', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+().\\s-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'in:Mayorista,Importador,Distribuidor,Local'],
            'condiciones' => ['nullable', 'string', 'max:2000'],
            'plazos_pago' => ['nullable', 'string', 'max:100'],
            'tiempo_entrega' => ['nullable', 'string', 'max:100'],
            'descuento_habitual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 7 caracteres.',
            'telefono.regex' => 'El teléfono contiene caracteres no válidos.',
            'tipo.required' => 'El tipo de proveedor es obligatorio.',
            'tipo.in' => 'El tipo debe ser Mayorista, Importador, Distribuidor o Local.',
            'descuento_habitual.numeric' => 'El descuento habitual debe ser un número.',
            'descuento_habitual.min' => 'El descuento habitual no puede ser negativo.',
            'descuento_habitual.max' => 'El descuento habitual no puede superar 100 %.',
            'telefono.max' => 'El teléfono no puede superar 30 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
        ]);
    }
}

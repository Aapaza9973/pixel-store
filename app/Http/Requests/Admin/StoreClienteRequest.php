<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear clientes');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clientes,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'nit_ci' => ['nullable', 'string', 'max:20', 'unique:clientes,nit_ci'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 255 caracteres.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Ya existe un cliente con ese email.',
            'telefono.max' => 'El teléfono no puede superar 30 caracteres.',
            'nit_ci.max' => 'El NIT/CI no puede superar 20 caracteres.',
            'nit_ci.unique' => 'Ya existe un cliente con ese NIT/CI.',
            'direccion.max' => 'La dirección no puede superar 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
        ]);
    }
}

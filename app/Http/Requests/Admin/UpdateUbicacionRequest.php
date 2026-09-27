<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editar ubicaciones');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:tienda,deposito'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'pasillo' => ['nullable', 'string', 'max:50'],
            'estante' => ['nullable', 'string', 'max:50'],
            'anaquel' => ['nullable', 'string', 'max:50'],
            'activa' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la ubicación es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'tipo.required' => 'El tipo de ubicación es obligatorio.',
            'tipo.in' => 'El tipo debe ser tienda o depósito.',
            'pasillo.max' => 'El pasillo no puede superar los 50 caracteres.',
            'estante.max' => 'El estante no puede superar los 50 caracteres.',
            'anaquel.max' => 'El anaquel no puede superar los 50 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activa' => $this->boolean('activa'),
        ]);
    }
}

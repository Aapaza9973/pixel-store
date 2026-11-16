<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNumeroSerieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('editar numeros-serie');
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'exists:productos,id'],
            'numero_serie' => ['required', 'string', 'max:100', 'unique:numeros_serie,numero_serie'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'fecha_ingreso' => ['nullable', 'date'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'Seleccione un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ese número de serie ya está registrado.',
            'numero_serie.max' => 'El número de serie no puede superar los 100 caracteres.',
            'costo_unitario.numeric' => 'El costo debe ser un número.',
            'costo_unitario.min' => 'El costo no puede ser negativo.',
            'fecha_ingreso.date' => 'La fecha de ingreso no es válida.',
            'ubicacion_id.exists' => 'La ubicación seleccionada no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('numero_serie')) {
            $this->merge(['numero_serie' => trim((string) $this->input('numero_serie'))]);
        }
    }
}

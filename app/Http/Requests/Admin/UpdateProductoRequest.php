<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editar productos');
    }

    public function rules(): array
    {
        $productoId = $this->route('producto')->id;

        return [
            'categoria_id' => ['required', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('productos', 'nombre')->ignore($productoId)],
            'descripcion' => ['nullable', 'string'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'umbral_alerta' => ['required', 'integer', 'min:0'],
            'maneja_numero_serie' => ['boolean'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('productos', 'sku')->ignore($productoId)],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'visible_catalogo' => ['boolean'],
            'atributos' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'maneja_numero_serie' => $this->boolean('maneja_numero_serie'),
            'visible_catalogo' => $this->boolean('visible_catalogo'),
        ]);
    }
}

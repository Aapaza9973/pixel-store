<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear productos');
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'nombre' => ['required', 'string', 'max:255', 'unique:productos,nombre'],
            'descripcion' => ['nullable', 'string'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'umbral_alerta' => ['required', 'integer', 'min:0'],
            'maneja_numero_serie' => ['boolean'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:productos,sku'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'visible_catalogo' => ['boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'atributos' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'marca_id.exists' => 'La marca seleccionada no existe.',
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'precio_unitario.required' => 'El precio es obligatorio.',
            'precio_unitario.numeric' => 'El precio debe ser un número.',
            'precio_unitario.min' => 'El precio no puede ser negativo.',
            'costo.numeric' => 'El costo debe ser un número.',
            'costo.min' => 'El costo no puede ser negativo.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'ubicacion_id.exists' => 'La ubicación seleccionada no existe.',
            'umbral_alerta.required' => 'El umbral de alerta es obligatorio.',
            'umbral_alerta.integer' => 'El umbral de alerta debe ser un número entero.',
            'umbral_alerta.min' => 'El umbral de alerta no puede ser negativo.',
            'sku.unique' => 'Ya existe un producto con ese SKU.',
            'sku.max' => 'El SKU no puede superar los 50 caracteres.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
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

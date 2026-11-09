<?php

namespace App\Http\Requests\Shop;

use App\DTOs\ProductoFiltros;
use Illuminate\Foundation\Http\FormRequest;

class ProductoFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'marca' => ['nullable', 'integer', 'exists:marcas,id'],
            'precio_min' => ['nullable', 'numeric', 'min:0'],
            'precio_max' => ['nullable', 'numeric', 'min:0', 'gte:precio_min'],
            'disponible' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'in:'.implode(',', ProductoFiltros::ORDENES)],
        ];
    }

    public function filtros(): ProductoFiltros
    {
        return ProductoFiltros::desdeArray($this->validated());
    }
}

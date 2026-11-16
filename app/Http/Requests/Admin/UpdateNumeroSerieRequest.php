<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\NumeroSerie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNumeroSerieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('editar numeros-serie');
    }

    public function rules(): array
    {
        /** @var NumeroSerie $numeroSerie */
        $numeroSerie = $this->route('numeroSerie');

        return [
            'estado' => ['required', Rule::in(NumeroSerie::ESTADOS)],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'garantia_hasta' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'costo_unitario.numeric' => 'El costo debe ser un número.',
            'garantia_hasta.date' => 'La fecha de garantía no es válida.',
            'ubicacion_id.exists' => 'La ubicación seleccionada no existe.',
        ];
    }
}

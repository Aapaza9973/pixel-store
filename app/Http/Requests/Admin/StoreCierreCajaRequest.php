<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\CierreCajaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cierre de caja de un turno (HU-6.1).
 *
 * Solo Admin y Cajero operan caja. No existe el permiso 'cerrar cajas' en el
 * RoleSeeder, y 'ver caja'/'crear caja' los tiene también el Vendedor, así que
 * la autorización se hace por rol.
 */
class StoreCierreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['Admin', 'Cajero']);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'turno' => ['required', Rule::in(CierreCajaService::TURNOS)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'montos' => ['required', 'array'],
            'montos.*' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha del cierre es obligatoria.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'turno.required' => 'El turno es obligatorio.',
            'turno.in' => 'El turno debe ser mañana, tarde o noche.',
            'user_id.exists' => 'El vendedor seleccionado no existe.',
            'montos.required' => 'Debe registrar el monto contado en caja.',
            'montos.*.numeric' => 'Los montos deben ser números.',
            'montos.*.min' => 'Los montos no pueden ser negativos.',
            'notas.max' => 'Las notas no pueden superar 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $montos = $this->input('montos');

        // Un campo vacío significa "no se contó nada de ese método".
        if (is_array($montos)) {
            $this->merge([
                'montos' => array_map(
                    fn ($monto) => ($monto === null || $monto === '') ? 0 : $monto,
                    $montos
                ),
            ]);
        }
    }
}

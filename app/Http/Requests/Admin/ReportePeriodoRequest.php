<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\ReporteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del reporte por período (HU-6.2).
 */
class ReportePeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ver reportes');
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'agrupacion' => ['nullable', Rule::in(array_keys(ReporteService::AGRUPACIONES))],
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'agrupacion.in' => 'La agrupación debe ser día, semana, mes o año.',
        ];
    }
}

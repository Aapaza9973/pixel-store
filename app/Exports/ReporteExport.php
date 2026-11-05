<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exportación Excel genérica de reportes (HU-6.5).
 */
class ReporteExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array<array-key, mixed>>  $filas
     * @param  array<int, string>  $encabezados
     */
    public function __construct(
        private readonly array $filas,
        private readonly array $encabezados,
    ) {}

    public function array(): array
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return $this->encabezados;
    }
}

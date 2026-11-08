<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Exportación Excel con presentación corporativa para los reportes (HU-6.5).
 * La clase recibe los datos ya calculados por los servicios del sistema.
 */
class ReporteExport implements FromArray, WithCustomStartCell, WithEvents, ShouldAutoSize, WithColumnFormatting, WithTitle
{
    /**
     * @param array<int, array<array-key, mixed>> $filas
     * @param array<int, string> $encabezados
     * @param array<int, array{0:string,1:mixed}> $resumen
     * @param array<string, string> $formatosColumnas
     */
    public function __construct(
        private readonly array $filas,
        private readonly array $encabezados,
        private readonly string $titulo = 'Reporte del sistema',
        private readonly string $subtitulo = '',
        private readonly array $resumen = [],
        private readonly array $formatosColumnas = [],
    ) {}

    public function array(): array
    {
        return $this->filas;
    }

    public function startCell(): string
    {
        // Las primeras cuatro filas se reservan para título, período y resumen.
        return 'A5';
    }

    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }

    public function columnFormats(): array
    {
        return $this->formatosColumnas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = Coordinate::stringFromColumnIndex(max(count($this->encabezados), 1));
                $ultimaFila = 5 + count($this->filas);

                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', mb_strtoupper($this->titulo));
                $sheet->mergeCells("A2:{$ultimaColumna}2");
                $sheet->setCellValue('A2', $this->subtitulo);
                $sheet->mergeCells("A3:{$ultimaColumna}3");
                $sheet->setCellValue('A3', 'Documento generado el '.now()->format('d/m/Y H:i'));

                // Resumen ejecutivo en bloques etiqueta/valor.
                foreach ($this->resumen as $indice => [$etiqueta, $valor]) {
                    $columnaEtiqueta = Coordinate::stringFromColumnIndex(($indice * 2) + 1);
                    $columnaValor = Coordinate::stringFromColumnIndex(($indice * 2) + 2);
                    $sheet->setCellValue($columnaEtiqueta.'4', $etiqueta);
                    $sheet->setCellValue($columnaValor.'4', $valor);
                    if (is_numeric($valor) && str_contains($etiqueta, '(Bs)')) {
                        $sheet->getStyle($columnaValor.'4')->getNumberFormat()
                            ->setFormatCode('"Bs" #,##0.00;[Red]-"Bs" #,##0.00');
                    }
                }

                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17365D']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '244061']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->getStyle("A3:{$ultimaColumna}3")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                if ($this->resumen !== []) {
                    $sheet->getStyle("A4:{$ultimaColumna}4")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '17365D']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF0F6']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D5DEE8']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension(4)->setRowHeight(24);
                }

                $sheet->getStyle("A5:{$ultimaColumna}5")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2874A6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '17365D']]],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(30);

                if ($ultimaFila >= 6) {
                    $sheet->getStyle("A6:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                        'font' => ['size' => 10, 'color' => ['rgb' => '263238']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D9E2F3']]],
                    ]);

                    for ($fila = 6; $fila <= $ultimaFila; $fila++) {
                        if (($fila - 6) % 2 === 1) {
                            $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('F3F6FA');
                        }
                    }

                    $sheet->getRowDimension(6)->setRowHeight(21);
                    $sheet->setAutoFilter("A5:{$ultimaColumna}{$ultimaFila}");
                }

                $sheet->freezePane('A6');
                $sheet->setShowGridlines(false);
                $sheet->getStyle("A1:{$ultimaColumna}".max($ultimaFila, 5))
                    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}

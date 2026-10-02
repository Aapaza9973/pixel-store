<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * SPIKE: prototipo de landing publica. NO es la home real.
 *
 * Spike descartable: landing pública de Pixel Store con datos demo.
 * Ver docs/rediseno/00-plan.md. Eliminar antes de produccion.
 *
 * No consulta la base de datos ni el panel admin. Existe únicamente para
 * validar `skills/frontend-design.md` de punta a punta. Se elimina al cerrar
 * el spike (o se promueve a controlador real del catálogo).
 */
class PreviewController extends Controller
{
    /**
     * Landing pública con seis productos destacados de ejemplo.
     */
    public function index(): View
    {
        // Datos demo hardcodeados a propósito (ver alcance del spike).
        $destacados = [
            [
                'categoria' => 'Tarjeta gráfica',
                'marca' => 'ASUS',
                'modelo' => 'GeForce RTX 4070 TUF OC',
                'specs' => '12 GB GDDR6X · 192-bit · 200 W',
                'sku' => 'GPU-RTX4070-TUF',
                'precio' => 4890.00,
                'stock' => 12,
            ],
            [
                'categoria' => 'Procesador',
                'marca' => 'AMD',
                'modelo' => 'Ryzen 7 7800X3D',
                'specs' => '8 núcleos · 16 hilos · 5,0 GHz · AM5',
                'sku' => 'CPU-R7-7800X3D',
                'precio' => 3250.00,
                'stock' => 5,
            ],
            [
                'categoria' => 'Almacenamiento',
                'marca' => 'Samsung',
                'modelo' => '990 PRO 2 TB',
                'specs' => 'NVMe PCIe 4.0 · 7.450 MB/s · M.2 2280',
                'sku' => 'SSD-990PRO-2TB',
                'precio' => 1690.00,
                'stock' => 24,
            ],
            [
                'categoria' => 'Memoria RAM',
                'marca' => 'Corsair',
                'modelo' => 'Vengeance RGB 32 GB',
                'specs' => 'DDR5-6000 · CL30 · 2×16 GB',
                'sku' => 'RAM-VENG-32D5',
                'precio' => 1140.00,
                'stock' => 3,
            ],
            [
                'categoria' => 'Placa madre',
                'marca' => 'ASUS',
                'modelo' => 'ROG Strix B650E-F',
                'specs' => 'AM5 · DDR5 · PCIe 5.0 · ATX',
                'sku' => 'MB-B650E-STRIX',
                'precio' => 2180.00,
                'stock' => 8,
            ],
            [
                'categoria' => 'Monitor',
                'marca' => 'LG',
                'modelo' => 'UltraGear 27GR93U',
                'specs' => '27" · 4K UHD · 144 Hz · IPS',
                'sku' => 'MON-27GR93U',
                'precio' => 3790.00,
                'stock' => 0,
            ],
        ];

        return view('preview.index', compact('destacados'));
    }
}

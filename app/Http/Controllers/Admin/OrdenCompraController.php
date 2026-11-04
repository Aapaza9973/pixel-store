<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Órdenes de compra: salida en PDF (3.4.11-3.4.12).
 */
class OrdenCompraController extends Controller
{
    /**
     * Genera el PDF A4 de una orden de compra.
     *
     * El permiso es el `view` de OrdenCompraPolicy: quien puede ver la orden en
     * el panel puede imprimirla; no se agrega un permiso nuevo.
     */
    public function pdf(OrdenCompra $orden): Response
    {
        $this->authorize('view', $orden);

        $orden->load(['proveedor', 'usuario', 'detalles.producto']);

        $pdf = Pdf::loadView('admin.compras.pdf', ['orden' => $orden])
            ->setPaper('a4', 'portrait');

        return $pdf->download("orden-compra-{$orden->numero}.pdf");
    }
}

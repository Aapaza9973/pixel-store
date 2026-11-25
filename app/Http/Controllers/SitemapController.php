<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Response;

/**
 * Sitemap público para buscadores.
 *
 * Solo incluye lo que un visitante anónimo puede ver: la home, el catálogo y
 * las fichas de productos visibles. Nada del panel.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $productos = Producto::query()
            ->visible()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', ['productos' => $productos])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

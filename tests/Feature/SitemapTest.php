<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_sitemap_lista_los_productos_visibles(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría Sitemap', 'tipo' => 'Componente']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto Sitemap',
            'precio_unitario' => 500,
            'stock' => 2,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('catalogo.index'), false);
        $response->assertSee(route('catalogo.producto', $producto->slug), false);
    }

    public function test_el_sitemap_no_expone_el_panel(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertDontSee('/admin', false);
    }

    public function test_robots_apunta_al_sitemap_y_bloquea_el_panel(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }
}

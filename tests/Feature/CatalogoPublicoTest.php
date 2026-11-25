<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La tienda es pública: un visitante sin cuenta ni sesión tiene que poder ver
 * el catálogo, la ficha de un producto y el checkout, sin nada del panel.
 */
class CatalogoPublicoTest extends TestCase
{
    use DatabaseTransactions;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create(['nombre' => 'Categoría Pública', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Pública']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Monitor Público 27"',
            'precio_unitario' => 1850,
            'stock' => 6,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);
    }

    public function test_la_home_es_publica_y_muestra_la_tienda(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Ver catálogo');
        $response->assertSee('Monitor Público 27"');
        $response->assertSee('Bs 1.850,00');
    }

    public function test_la_home_no_muestra_nada_del_panel(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Punto de Venta');
        $response->assertDontSee('Cerrar sesión');
        $response->assertDontSee('Dashboard');
    }

    public function test_el_catalogo_es_publico_y_usa_el_layout_de_tienda(): void
    {
        $response = $this->get(route('catalogo.index'));

        $response->assertOk();
        $response->assertSee('Monitor Público 27"');
        $response->assertSee('carritoPublico()', false);
        $response->assertDontSee('Punto de Venta');
        $response->assertDontSee('Cerrar sesión');
    }

    public function test_la_ficha_de_producto_es_publica_y_expone_datos_estructurados(): void
    {
        $response = $this->get(route('catalogo.producto', $this->producto->slug));

        $response->assertOk();
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"priceCurrency":"BOB"', false);
        $response->assertSee('"availability":"https://schema.org/InStock"', false);
        $response->assertDontSee('Punto de Venta');
    }

    public function test_el_checkout_es_publico(): void
    {
        $response = $this->get(route('catalogo.checkout'));

        $response->assertOk();
        $response->assertSee('Datos de entrega');
        $response->assertSee('name="nit_ci"', false);
    }

    public function test_un_producto_oculto_no_se_publica(): void
    {
        $oculto = Producto::create([
            'categoria_id' => $this->producto->categoria_id,
            'nombre' => 'Producto Oculto',
            'precio_unitario' => 100,
            'stock' => 3,
            'umbral_alerta' => 1,
            'visible_catalogo' => false,
        ]);

        $this->get(route('catalogo.producto', $oculto->slug))->assertNotFound();
        $this->get(route('home'))->assertDontSee('Producto Oculto');
    }
}

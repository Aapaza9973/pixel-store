<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use DatabaseTransactions;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create(['nombre' => 'Categoría Catálogo', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Catálogo']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Fuente Catálogo 850W',
            'slug' => 'fuente-catalogo-850w',
            'descripcion' => 'Fuente modular 80+ Gold',
            'precio_unitario' => 850,
            'stock' => 4,
            'umbral_alerta' => 2,
            'visible_catalogo' => true,
        ]);
    }

    public function test_invitado_puede_ver_la_ficha_publica(): void
    {
        $response = $this->get(route('catalogo.producto', $this->producto->slug));

        $response->assertOk();
        $response->assertSee('Fuente Catálogo 850W');
        $response->assertSee('850,00');
    }

    public function test_producto_oculto_del_catalogo_devuelve_404(): void
    {
        $this->producto->update(['visible_catalogo' => false]);

        $response = $this->get(route('catalogo.producto', $this->producto->slug));

        $response->assertNotFound();
    }

    public function test_slug_inexistente_devuelve_404(): void
    {
        $response = $this->get(route('catalogo.producto', 'producto-que-no-existe'));

        $response->assertNotFound();
    }

    public function test_producto_agotado_muestra_estado_agotado(): void
    {
        $this->producto->update(['stock' => 0]);

        $response = $this->get(route('catalogo.producto', $this->producto->slug));

        $response->assertOk();
        $response->assertSee('Agotado');
    }
}

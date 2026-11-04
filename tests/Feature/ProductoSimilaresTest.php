<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductoSimilaresTest extends TestCase
{
    use DatabaseTransactions;

    private Categoria $categoria;

    private Categoria $otraCategoria;

    private Producto $base;

    private ProductoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(ProductoService::class);

        $this->categoria = Categoria::create(['nombre' => 'Categoría Similares', 'tipo' => 'Componente']);
        $this->otraCategoria = Categoria::create(['nombre' => 'Categoría Ajena', 'tipo' => 'Componente']);

        $this->base = $this->crearProducto('Base Similares', 1000, $this->categoria->id);
    }

    private function crearProducto(string $nombre, float $precio, int $categoriaId, bool $visible = true): Producto
    {
        return Producto::create([
            'categoria_id' => $categoriaId,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::random(6),
            'precio_unitario' => $precio,
            'stock' => 5,
            'umbral_alerta' => 1,
            'visible_catalogo' => $visible,
        ]);
    }

    public function test_devuelve_productos_de_la_misma_categoria_en_rango_de_precio(): void
    {
        $cercano = $this->crearProducto('Similar Cercano', 1050, $this->categoria->id);
        $lejanoEnRango = $this->crearProducto('Similar Lejano', 1200, $this->categoria->id);

        // Fuera del ±30 % (rango 700-1300) y de otra categoría: no deben salir.
        $fueraDeRango = $this->crearProducto('Similar Caro', 1500, $this->categoria->id);
        $otraCategoria = $this->crearProducto('Similar Otra Categoría', 1000, $this->otraCategoria->id);

        $similares = $this->servicio->similares($this->base);
        $ids = $similares->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$cercano->id, $lejanoEnRango->id], $ids);
        $this->assertNotContains($fueraDeRango->id, $ids);
        $this->assertNotContains($otraCategoria->id, $ids);

        // Ordenado por cercanía de precio, no por id.
        $this->assertSame($cercano->id, (int) $similares->first()->id);
    }

    public function test_excluye_el_producto_actual_y_los_inactivos(): void
    {
        $activo = $this->crearProducto('Similar Activo', 980, $this->categoria->id);
        $inactivo = $this->crearProducto('Similar Oculto', 1020, $this->categoria->id, visible: false);

        $ids = $this->servicio->similares($this->base)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertNotContains($this->base->id, $ids);
        $this->assertNotContains($inactivo->id, $ids);
        $this->assertContains($activo->id, $ids);
    }

    public function test_ficha_muestra_la_seccion_de_productos_similares(): void
    {
        $this->crearProducto('Similar Visible 950', 950, $this->categoria->id);
        $this->crearProducto('Similar Fuera De Rango', 1500, $this->categoria->id);

        $response = $this->get(route('catalogo.producto', $this->base->slug));

        $response->assertOk();
        $response->assertSee('Productos similares');
        $response->assertSee('Similar Visible 950');
        $response->assertSee('950,00');
        $response->assertDontSee('Similar Fuera De Rango');
        $response->assertDontSee('se publican en HU-2.5');
    }
}

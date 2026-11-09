<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogoIndexTest extends TestCase
{
    use DatabaseTransactions;

    private Categoria $categoriaComponentes;

    private Categoria $categoriaPerifericos;

    private Marca $marcaAlfa;

    private Marca $marcaBeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoriaComponentes = Categoria::create(['nombre' => 'Componentes', 'tipo' => 'Componente']);
        $this->categoriaPerifericos = Categoria::create(['nombre' => 'Periféricos', 'tipo' => 'Periférico']);

        $this->marcaAlfa = Marca::create(['nombre' => 'Alfa Tech']);
        $this->marcaBeta = Marca::create(['nombre' => 'Beta Hardware']);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearProducto(array $cambios = []): Producto
    {
        static $secuencia = 0;
        $secuencia++;

        return Producto::create(array_merge([
            'categoria_id' => $this->categoriaComponentes->id,
            'marca_id' => $this->marcaAlfa->id,
            'nombre' => 'Producto de catálogo '.$secuencia,
            'slug' => 'producto-catalogo-'.$secuencia,
            'descripcion' => 'Producto de prueba del listado público',
            'precio_unitario' => 100,
            'stock' => 3,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ], $cambios));
    }

    public function test_invitado_ve_el_catalogo_con_productos_visibles(): void
    {
        $this->crearProducto(['nombre' => 'Fuente Visible 850W', 'slug' => 'fuente-visible-850w']);
        $this->crearProducto(['nombre' => 'Producto Oculto Del Catálogo', 'slug' => 'producto-oculto', 'visible_catalogo' => false]);
        $this->crearProducto([
            'nombre' => 'Producto Agotado Oculto',
            'slug' => 'producto-agotado',
            'stock' => 0,
            'ocultar_si_sin_stock' => true,
        ]);

        $response = $this->get(route('catalogo.index'));

        $response->assertOk();
        $response->assertSee('Fuente Visible 850W');
        $response->assertDontSee('Producto Oculto Del Catálogo');
        $response->assertDontSee('Producto Agotado Oculto');
    }

    public function test_filtros_de_categoria_y_marca_funcionan(): void
    {
        $this->crearProducto(['nombre' => 'Placa Madre Alfa', 'slug' => 'placa-madre-alfa', 'precio_unitario' => 900]);
        $this->crearProducto([
            'nombre' => 'Teclado Beta',
            'slug' => 'teclado-beta',
            'categoria_id' => $this->categoriaPerifericos->id,
            'marca_id' => $this->marcaBeta->id,
            'precio_unitario' => 250,
        ]);
        $this->crearProducto([
            'nombre' => 'Mouse Alfa',
            'slug' => 'mouse-alfa',
            'categoria_id' => $this->categoriaPerifericos->id,
            'precio_unitario' => 120,
        ]);

        $porCategoria = $this->get(route('catalogo.index', ['categoria_id' => $this->categoriaPerifericos->id]));

        $porCategoria->assertOk();
        $porCategoria->assertSee('Teclado Beta');
        $porCategoria->assertSee('Mouse Alfa');
        $porCategoria->assertDontSee('Placa Madre Alfa');

        $porMarca = $this->get(route('catalogo.index', ['marca_id' => $this->marcaBeta->id]));

        $porMarca->assertOk();
        $porMarca->assertSee('Teclado Beta');
        $porMarca->assertDontSee('Mouse Alfa');
        $porMarca->assertDontSee('Placa Madre Alfa');
    }

    public function test_ordenamiento_por_precio_ascendente(): void
    {
        $this->crearProducto(['nombre' => 'Precio Alto', 'slug' => 'precio-alto', 'precio_unitario' => 1500]);
        $this->crearProducto(['nombre' => 'Precio Bajo', 'slug' => 'precio-bajo', 'precio_unitario' => 90]);
        $this->crearProducto(['nombre' => 'Precio Medio', 'slug' => 'precio-medio', 'precio_unitario' => 480]);

        $ascendente = $this->get(route('catalogo.index', ['orden' => 'precio_asc']));

        $ascendente->assertOk();
        $ascendente->assertSeeInOrder(['Precio Bajo', 'Precio Medio', 'Precio Alto']);

        $descendente = $this->get(route('catalogo.index', ['orden' => 'precio_desc']));

        $descendente->assertOk();
        $descendente->assertSeeInOrder(['Precio Alto', 'Precio Medio', 'Precio Bajo']);
    }

    public function test_filtros_de_precio_funcionan(): void
    {
        $this->crearProducto(['nombre' => 'Barato Extremo', 'slug' => 'barato-extremo', 'precio_unitario' => 90]);
        $this->crearProducto(['nombre' => 'Rango Medio', 'slug' => 'rango-medio', 'precio_unitario' => 480]);
        $this->crearProducto(['nombre' => 'Caro Extremo', 'slug' => 'caro-extremo', 'precio_unitario' => 1500]);

        $response = $this->get(route('catalogo.index', ['precio_min' => 200, 'precio_max' => 1000]));

        $response->assertOk();
        $response->assertSee('Rango Medio');
        $response->assertDontSee('Barato Extremo');
        $response->assertDontSee('Caro Extremo');
    }
}

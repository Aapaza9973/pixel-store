<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AtributoTecnico;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\ValorAtributo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * docs/13 #2 y docs/14 #4: filtros por atributos EAV y búsqueda difusa
 * (pg_trgm + unaccent) del catálogo público.
 */
class CatalogoFiltrosTest extends TestCase
{
    use DatabaseTransactions;

    private Categoria $categoria;

    private AtributoTecnico $socket;

    private AtributoTecnico $capacidad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoria = Categoria::create(['nombre' => 'Componentes EAV', 'tipo' => 'Componente']);

        $this->socket = AtributoTecnico::create([
            'nombre' => 'Socket',
            'tipo_dato' => 'enum',
            'categoria_id' => $this->categoria->id,
            'es_filtrable' => true,
            'orden' => 1,
        ]);

        $this->capacidad = AtributoTecnico::create([
            'nombre' => 'Capacidad',
            'tipo_dato' => 'string',
            'unidad' => 'GB',
            'categoria_id' => $this->categoria->id,
            'es_filtrable' => true,
            'orden' => 2,
        ]);

        ValorAtributo::create(['atributo_id' => $this->socket->id, 'valor' => 'AM5', 'orden' => 1]);
        ValorAtributo::create(['atributo_id' => $this->socket->id, 'valor' => 'LGA1700', 'orden' => 2]);
    }

    private function crearProducto(string $nombre, string $slug): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria->id,
            'marca_id' => Marca::create(['nombre' => 'Marca '.$slug])->id,
            'nombre' => $nombre,
            'slug' => $slug,
            'precio_unitario' => 500,
            'stock' => 3,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function asignar(Producto $producto, AtributoTecnico $atributo, array $datos): void
    {
        ProductoAtributo::create(array_merge([
            'producto_id' => $producto->id,
            'atributo_id' => $atributo->id,
        ], $datos));
    }

    public function test_filtra_por_valor_de_enum(): void
    {
        $am5 = $this->crearProducto('Placa AM5 Filtro', 'placa-am5-filtro');
        $lga = $this->crearProducto('Placa LGA Filtro', 'placa-lga-filtro');

        $this->asignar($am5, $this->socket, [
            'valor_enum_id' => ValorAtributo::where('valor', 'AM5')->firstOrFail()->id,
        ]);
        $this->asignar($lga, $this->socket, [
            'valor_enum_id' => ValorAtributo::where('valor', 'LGA1700')->firstOrFail()->id,
        ]);

        $response = $this->get(route('catalogo.index', [
            'categoria_id' => $this->categoria->id,
            'atributos' => [$this->socket->id => 'AM5'],
        ]));

        $response->assertOk();
        $response->assertSee('Placa AM5 Filtro');
        $response->assertDontSee('Placa LGA Filtro');
    }

    public function test_filtra_por_valor_de_texto(): void
    {
        $treintaYDos = $this->crearProducto('Memoria 32 GB Filtro', 'memoria-32-filtro');
        $dieciseis = $this->crearProducto('Memoria 16 GB Filtro', 'memoria-16-filtro');

        $this->asignar($treintaYDos, $this->capacidad, ['valor_string' => '32 GB']);
        $this->asignar($dieciseis, $this->capacidad, ['valor_string' => '16 GB']);

        $response = $this->get(route('catalogo.index', [
            'atributos' => [$this->capacidad->id => '32'],
        ]));

        $response->assertOk();
        $response->assertSee('Memoria 32 GB Filtro');
        $response->assertDontSee('Memoria 16 GB Filtro');
    }

    public function test_atributo_inexistente_o_no_filtrable_se_ignora(): void
    {
        $this->crearProducto('Producto Visible EAV', 'producto-visible-eav');

        $this->socket->update(['es_filtrable' => false]);

        $response = $this->get(route('catalogo.index', [
            'atributos' => [
                999999 => 'lo que sea',
                $this->socket->id => 'AM5',
            ],
        ]));

        $response->assertOk();
        $response->assertSee('Producto Visible EAV');
    }

    public function test_busqueda_difusa_tolera_errores_de_tipeo(): void
    {
        $this->crearProducto('Monitor Gamer 27 Pulgadas', 'monitor-gamer-27');
        $this->crearProducto('Teclado Mecanico RGB', 'teclado-mecanico-rgb');

        $response = $this->get(route('catalogo.index', ['buscar' => 'monitr gamer 27']));

        $response->assertOk();
        $response->assertSee('Monitor Gamer 27 Pulgadas');
        $response->assertDontSee('Teclado Mecanico RGB');
    }

    public function test_ficha_muestra_las_especificaciones_del_producto(): void
    {
        $placa = $this->crearProducto('Placa Ficha Atributos', 'placa-ficha-atributos');

        $this->asignar($placa, $this->socket, [
            'valor_enum_id' => ValorAtributo::where('valor', 'AM5')->firstOrFail()->id,
        ]);
        $this->asignar($placa, $this->capacidad, ['valor_string' => '128 GB']);

        $response = $this->get(route('catalogo.producto', $placa->slug));

        $response->assertOk();
        $response->assertSee('Especificaciones técnicas');
        $response->assertSee('Socket');
        $response->assertSee('AM5');
        $response->assertSee('128 GB');
        $response->assertDontSee('se publica cuando el catálogo técnico esté completo');
    }

    public function test_busqueda_ignora_tildes(): void
    {
        $this->crearProducto('Memória RAM Corsair', 'memoria-ram-corsair');
        $this->crearProducto('Fuente de Poder', 'fuente-poder');

        $response = $this->get(route('catalogo.index', ['buscar' => 'memoria']));

        $response->assertOk();
        $response->assertSee('Memória RAM Corsair');
        $response->assertDontSee('Fuente de Poder');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductoFilterTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Categoria $categoriaA;

    private Categoria $categoriaB;

    private Marca $marcaA;

    private Marca $marcaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->categoriaA = Categoria::create(['nombre' => 'Procesadores', 'tipo' => 'Componente']);
        $this->categoriaB = Categoria::create(['nombre' => 'Laptops', 'tipo' => 'Equipo']);
        $this->marcaA = Marca::create(['nombre' => 'AMD']);
        $this->marcaB = Marca::create(['nombre' => 'Intel']);

        $this->crearProducto([
            'categoria_id' => $this->categoriaA->id, 'marca_id' => $this->marcaA->id,
            'nombre' => 'AMD Ryzen 5 5600X', 'sku' => 'CPU-AMD-5600X',
            'precio_unitario' => 1450, 'stock' => 24, 'umbral_alerta' => 5,
        ]);

        $this->crearProducto([
            'categoria_id' => $this->categoriaA->id, 'marca_id' => $this->marcaB->id,
            'nombre' => 'Intel Core i5-12400F', 'sku' => 'CPU-INT-12400F',
            'precio_unitario' => 1350, 'stock' => 2, 'umbral_alerta' => 5,
        ]);

        $this->crearProducto([
            'categoria_id' => $this->categoriaA->id, 'marca_id' => $this->marcaA->id,
            'nombre' => 'AMD Ryzen 9 5900X', 'sku' => 'CPU-AMD-5900X',
            'precio_unitario' => 3000, 'stock' => 0, 'umbral_alerta' => 5,
        ]);

        $this->crearProducto([
            'categoria_id' => $this->categoriaB->id, 'marca_id' => $this->marcaB->id,
            'nombre' => 'HP Pavilion 15', 'sku' => 'LAP-HP-15',
            'precio_unitario' => 6500, 'stock' => 30, 'umbral_alerta' => 3,
        ]);

        $this->crearProducto([
            'categoria_id' => $this->categoriaB->id, 'marca_id' => $this->marcaB->id,
            'nombre' => 'Lenovo IdeaPad 3', 'sku' => 'LAP-LEN-IP3',
            'precio_unitario' => 5200, 'stock' => 30, 'umbral_alerta' => 3,
        ]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearProducto(array $cambios = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $this->categoriaA->id,
            'marca_id' => $this->marcaA->id,
            'nombre' => 'Producto '.uniqid(),
            'descripcion' => 'Descripción de prueba',
            'precio_unitario' => 1000,
            'costo' => 800,
            'stock' => 10,
            'umbral_alerta' => 5,
            'sku' => 'SKU-'.uniqid(),
            'codigo_barras' => (string) random_int(100000000, 999999999),
            'visible_catalogo' => true,
        ], $cambios));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function nombresFiltrados(array $params): \Illuminate\Support\Collection
    {
        $response = $this->actingAs($this->admin)->get(route('admin.productos.index', $params));

        $response->assertOk();

        return $response->viewData('productos')->pluck('nombre');
    }

    public function test_buscar_por_nombre(): void
    {
        $nombres = $this->nombresFiltrados(['buscar' => 'Ryzen']);

        $this->assertTrue($nombres->contains('AMD Ryzen 5 5600X'));
        $this->assertTrue($nombres->contains('AMD Ryzen 9 5900X'));
        $this->assertFalse($nombres->contains('HP Pavilion 15'));
    }

    public function test_buscar_por_sku(): void
    {
        $nombres = $this->nombresFiltrados(['buscar' => 'CPU-AMD-5600X']);

        $this->assertSame(['AMD Ryzen 5 5600X'], $nombres->values()->all());
    }

    public function test_filtrar_por_categoria(): void
    {
        $nombres = $this->nombresFiltrados(['categoria_id' => $this->categoriaA->id]);

        $this->assertCount(3, $nombres);
        $this->assertFalse($nombres->contains('HP Pavilion 15'));
        $this->assertFalse($nombres->contains('Lenovo IdeaPad 3'));
    }

    public function test_filtrar_por_marca(): void
    {
        $nombres = $this->nombresFiltrados(['marca_id' => $this->marcaA->id]);

        $this->assertCount(2, $nombres);
        $this->assertTrue($nombres->contains('AMD Ryzen 5 5600X'));
        $this->assertTrue($nombres->contains('AMD Ryzen 9 5900X'));
        $this->assertFalse($nombres->contains('Intel Core i5-12400F'));
    }

    public function test_filtrar_por_rango_de_precio(): void
    {
        $nombres = $this->nombresFiltrados(['precio_min' => 1000, 'precio_max' => 2000]);

        $this->assertCount(2, $nombres);
        $this->assertTrue($nombres->contains('AMD Ryzen 5 5600X'));
        $this->assertTrue($nombres->contains('Intel Core i5-12400F'));
        $this->assertFalse($nombres->contains('AMD Ryzen 9 5900X'));
    }

    public function test_filtrar_por_disponibilidad_agotado(): void
    {
        $nombres = $this->nombresFiltrados(['disponibilidad' => 'agotado']);

        $this->assertSame(['AMD Ryzen 9 5900X'], $nombres->values()->all());
    }

    public function test_filtrar_por_disponibilidad_bajo_stock(): void
    {
        $nombres = $this->nombresFiltrados(['disponibilidad' => 'bajo_stock']);

        $this->assertCount(2, $nombres);
        $this->assertTrue($nombres->contains('Intel Core i5-12400F'));
        $this->assertTrue($nombres->contains('AMD Ryzen 9 5900X'));
        $this->assertFalse($nombres->contains('AMD Ryzen 5 5600X'));
    }

    public function test_ordenar_por_precio_ascendente(): void
    {
        $nombres = $this->nombresFiltrados(['orden' => 'precio_asc']);

        $this->assertSame([
            'Intel Core i5-12400F',
            'AMD Ryzen 5 5600X',
            'AMD Ryzen 9 5900X',
            'Lenovo IdeaPad 3',
            'HP Pavilion 15',
        ], $nombres->values()->all());
    }

    public function test_ordenar_por_nombre_descendente(): void
    {
        $nombres = $this->nombresFiltrados(['orden' => 'nombre_desc']);

        $this->assertSame([
            'Lenovo IdeaPad 3',
            'Intel Core i5-12400F',
            'HP Pavilion 15',
            'AMD Ryzen 9 5900X',
            'AMD Ryzen 5 5600X',
        ], $nombres->values()->all());
    }

    public function test_filtros_se_preservan_en_paginacion(): void
    {
        for ($i = 0; $i < 16; $i++) {
            $this->crearProducto([
                'categoria_id' => $this->categoriaA->id,
                'nombre' => 'Extra Ryzen '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'sku' => 'CPU-EXTRA-'.$i,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.productos.index', [
            'categoria_id' => $this->categoriaA->id,
            'orden' => 'precio_asc',
        ]));

        $response->assertOk();

        $productos = $response->viewData('productos');

        $this->assertGreaterThan(1, $productos->lastPage());
        $this->assertStringContainsString(
            'categoria_id='.$this->categoriaA->id,
            $productos->url(2)
        );
        $this->assertStringContainsString('orden=precio_asc', $productos->url(2));
    }
}

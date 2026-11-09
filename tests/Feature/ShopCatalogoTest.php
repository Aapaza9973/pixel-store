<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Usa DB directa para no depender de factories; funciona con tu schema pgsql de tests. */
class ShopCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private int $cat;
    private int $marcaA;
    private int $marcaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = DB::table('categorias')->insertGetId(['nombre' => 'Procesadores', 'tipo' => 'Componente']);
        $this->marcaA = DB::table('marcas')->insertGetId(['nombre' => 'AMD']);
        $this->marcaB = DB::table('marcas')->insertGetId(['nombre' => 'Intel']);
    }

    private function producto(array $o = []): int
    {
        return DB::table('productos')->insertGetId($o + [
            'categoria_id' => $this->cat, 'marca_id' => $this->marcaA, 'nombre' => 'Prod '.uniqid(),
            'precio_unitario' => 100, 'stock' => 5, 'visible_catalogo' => true,
        ]);
    }

    public function test_catalogo_es_publico_y_filtra_por_marca_y_disponibilidad(): void
    {
        $this->producto(['nombre' => 'Ryzen Visible']);
        $this->producto(['nombre' => 'Ryzen Agotado', 'stock' => 0]);
        $this->producto(['nombre' => 'Core Otra Marca', 'marca_id' => $this->marcaB]);

        $this->get(route('shop.index', ['marca' => $this->marcaA, 'disponible' => 1]))
            ->assertOk()->assertSee('Ryzen Visible')
            ->assertDontSee('Ryzen Agotado')->assertDontSee('Core Otra Marca');
    }

    public function test_respeta_visible_catalogo_y_ocultar_sin_stock(): void
    {
        $this->producto(['nombre' => 'Producto Oculto', 'visible_catalogo' => false]);
        $this->producto(['nombre' => 'Producto Sin Stock Oculto', 'stock' => 0, 'ocultar_si_sin_stock' => true]);

        $this->get(route('shop.index'))->assertOk()
            ->assertDontSee('Producto Oculto')->assertDontSee('Producto Sin Stock Oculto');
    }

    public function test_filtra_por_rango_de_precio_y_valida_rango_invalido(): void
    {
        $this->producto(['nombre' => 'Barato', 'precio_unitario' => 50]);
        $this->producto(['nombre' => 'Caro', 'precio_unitario' => 900]);

        $this->get(route('shop.index', ['precio_max' => 100]))->assertSee('Barato')->assertDontSee('Caro');
        $this->get(route('shop.index', ['precio_min' => 500, 'precio_max' => 100]))->assertSessionHasErrors('precio_max');
    }

    public function test_detalle_acepta_id_cuando_no_hay_slug(): void
    {
        $id = $this->producto(['nombre' => 'Sin Slug']);

        $this->get(route('shop.show', $id))->assertOk()->assertSee('Sin Slug');
    }

    public function test_pedido_se_registra_pendiente_con_token_y_no_toca_stock(): void
    {
        $id = $this->producto(['precio_unitario' => 100, 'stock' => 3]);

        $this->post(route('cart.store', $id), ['cantidad' => 2]);
        $this->post(route('checkout.store'), ['nombre' => 'Juan Pérez', 'telefono' => '70000000'])->assertRedirect();

        $pedido = DB::table('pedidos')->first();
        $this->assertSame('Pendiente', $pedido->estado);
        $this->assertSame(48, strlen($pedido->token));
        $this->assertEquals(200, $pedido->total);
        $this->assertDatabaseHas('pedido_items', ['pedido_id' => $pedido->id, 'producto_id' => $id, 'cantidad' => 2]);
        $this->assertSame(3, (int) DB::table('productos')->where('id', $id)->value('stock'));
    }

    public function test_carrito_limita_la_cantidad_al_stock(): void
    {
        $id = $this->producto(['stock' => 2]);

        $this->post(route('cart.store', $id), ['cantidad' => 10]);

        $this->get(route('cart.index'))->assertSee('value="2"', false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * HU-2.8.4: ocultar del catálogo automáticamente cuando no hay stock.
 */
class CatalogoStockOcultoTest extends TestCase
{
    use DatabaseTransactions;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create(['nombre' => 'Categoría Stock Oculto', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Stock Oculto']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Placa Stock Oculto',
            'slug' => 'placa-stock-oculto',
            'precio_unitario' => 700,
            'stock' => 0,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
            'ocultar_si_sin_stock' => true,
        ]);
    }

    public function test_producto_con_ocultar_si_sin_stock_no_aparece_en_catalogo(): void
    {
        $this->get(route('catalogo.producto', $this->producto->slug))
            ->assertNotFound();

        $this->assertFalse(
            Producto::visible()->whereKey($this->producto->id)->exists(),
            'El scope visible no debe devolver un producto agotado con el flag activo.'
        );
    }

    public function test_producto_vuelve_al_reactivar_stock(): void
    {
        $this->producto->update(['stock' => 5]);

        $this->get(route('catalogo.producto', $this->producto->slug))
            ->assertOk()
            ->assertSee('Placa Stock Oculto');

        $this->assertTrue(Producto::visible()->whereKey($this->producto->id)->exists());
    }
}

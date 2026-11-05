<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductoAlternativasTest extends TestCase
{
    use DatabaseTransactions;

    private Categoria $categoria;

    private Producto $base;

    private ProductoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(ProductoService::class);

        $this->categoria = Categoria::create(['nombre' => 'Categoría Alternativas', 'tipo' => 'Componente']);

        $this->base = $this->crearProducto('Base Alternativas', 1000, $this->categoria->id);
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

    public function test_alternativas_devuelve_misma_categoria_fuera_de_rango(): void
    {
        $caro = $this->crearProducto('Alternativa Cara', 1500, $this->categoria->id);
        $barato = $this->crearProducto('Alternativa Barata', 400, $this->categoria->id);

        // Dentro del ±30 % (700-1300): son similares, no alternativas.
        $this->crearProducto('Cercano', 1100, $this->categoria->id);

        $otraCategoria = Categoria::create(['nombre' => 'Otra Alternativas', 'tipo' => 'Componente']);
        $this->crearProducto('Otra Categoría', 1500, $otraCategoria->id);

        $ids = $this->servicio->alternativas($this->base)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$caro->id, $barato->id], $ids);
    }

    public function test_alternativas_respeta_el_limite_y_excluye_el_actual(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->crearProducto("Alternativa {$i}", 1500 + $i * 10, $this->categoria->id);
        }

        $alternativas = $this->servicio->alternativas($this->base, 4);

        $this->assertCount(4, $alternativas);
        $this->assertFalse($alternativas->contains('id', $this->base->id));
    }

    public function test_ficha_muestra_alternativas_y_cards_clickeables(): void
    {
        $alternativa = $this->crearProducto('Alternativa Visible', 1600, $this->categoria->id);

        $response = $this->get(route('catalogo.producto', $this->base->slug));

        $response->assertOk();
        $response->assertSee('Alternativas');
        $response->assertSee(route('catalogo.producto', $alternativa->slug), false);
    }
}

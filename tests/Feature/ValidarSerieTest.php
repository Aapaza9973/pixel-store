<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-5.7.3: el POS valida una serie antes de asignarla a la línea.
 */
class ValidarSerieTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $producto;

    private Producto $otroProducto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Validar Serie', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'SSD Validar Serie',
            'precio_unitario' => 500,
            'stock' => 3,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);

        $this->otroProducto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'SSD Otro Producto',
            'precio_unitario' => 450,
            'stock' => 2,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);
    }

    private function serie(Producto $producto, string $estado = 'disponible'): NumeroSerie
    {
        return NumeroSerie::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-VAL-'.Str::upper(Str::random(8)),
            'estado' => $estado,
            'fecha_ingreso' => now(),
        ]);
    }

    public function test_serie_valida_devuelve_200(): void
    {
        $serie = $this->serie($this->producto);

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.validar-serie'), [
                'numero_serie_id' => $serie->id,
                'producto_id' => $this->producto->id,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('id', $serie->id)
            ->assertJsonPath('numero_serie', $serie->numero_serie);
    }

    public function test_serie_inexistente_devuelve_422(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.validar-serie'), [
                'numero_serie_id' => 999999,
                'producto_id' => $this->producto->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_serie_id');
    }

    public function test_serie_ya_vendida_devuelve_422(): void
    {
        $serie = $this->serie($this->producto, 'vendido');

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.validar-serie'), [
                'numero_serie_id' => $serie->id,
                'producto_id' => $this->producto->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El número de serie ya fue vendido.');
    }

    public function test_serie_de_otro_producto_devuelve_422(): void
    {
        $serie = $this->serie($this->otroProducto);

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.validar-serie'), [
                'numero_serie_id' => $serie->id,
                'producto_id' => $this->producto->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El número de serie no corresponde al producto.');
    }
}

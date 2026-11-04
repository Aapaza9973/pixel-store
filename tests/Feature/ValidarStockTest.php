<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ValidarStockTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Validar Stock', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor Validar Stock',
            'precio_unitario' => 1000,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);
    }

    public function test_valida_stock_suficiente_devuelve_200(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.validar-stock'),
            [
                'items' => [
                    ['producto_id' => $this->producto->id, 'cantidad' => 3],
                ],
            ]
        );

        $response->assertOk();
        $response->assertExactJson(['ok' => true]);
    }

    public function test_valida_stock_insuficiente_devuelve_422_con_detalle(): void
    {
        $this->producto->update(['stock' => 1]);

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.validar-stock'),
            [
                'items' => [
                    ['producto_id' => $this->producto->id, 'cantidad' => 2],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('errores.0.producto_id', $this->producto->id);
        $response->assertJsonPath('errores.0.nombre', 'Monitor Validar Stock');
        $response->assertJsonPath('errores.0.stock_actual', 1);
        $response->assertJsonPath('errores.0.solicitado', 2);
    }
}

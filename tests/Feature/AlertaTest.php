<?php

namespace Tests\Feature;

use App\Models\AlertaStock;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AlertaTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Alerta', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Alerta']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Producto Alerta Test',
            'precio_unitario' => 100,
            'costo' => 80,
            'stock' => 0,
            'umbral_alerta' => 5,
            'sku' => 'SKU-ALERTA-001',
            'visible_catalogo' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearAlerta(array $cambios = []): AlertaStock
    {
        return AlertaStock::create(array_merge([
            'producto_id' => $this->producto->id,
            'tipo' => 'bajo_stock',
            'mensaje' => 'Producto Producto Alerta Test tiene 0 unidades (umbral: 5)',
            'leida' => false,
        ], $cambios));
    }

    public function test_admin_puede_ver_bandeja_de_alertas(): void
    {
        $alerta = $this->crearAlerta();

        $response = $this->actingAs($this->admin)->get(route('alertas.index'));

        $response->assertOk();
        $response->assertSee('Alertas de stock');
        $response->assertSee($this->producto->nombre);
        $response->assertSee($alerta->mensaje);
    }

    public function test_alertas_no_leidas_aparecen_primero(): void
    {
        $leida = $this->crearAlerta(['leida' => true]);
        $noLeida = $this->crearAlerta(['leida' => false]);

        $response = $this->actingAs($this->admin)->get(route('alertas.index'));

        $response->assertOk();

        $alertas = $response->viewData('alertas');

        $this->assertSame($noLeida->id, $alertas->first()->id);
        $this->assertSame($leida->id, $alertas->last()->id);
    }

    public function test_marcar_alerta_como_leida(): void
    {
        $alerta = $this->crearAlerta(['leida' => false]);

        $response = $this->actingAs($this->admin)->patch(route('alertas.leida', $alerta));

        $response->assertRedirect();
        $this->assertTrue($alerta->fresh()->leida);
    }

    public function test_marcar_todas_las_alertas_como_leidas(): void
    {
        $this->crearAlerta(['leida' => false]);
        $this->crearAlerta(['leida' => false]);
        $leida = $this->crearAlerta(['leida' => true]);

        $response = $this->actingAs($this->admin)->post(route('alertas.marcar-todas'));

        $response->assertRedirect();
        $this->assertSame(0, AlertaStock::where('leida', false)->count());
        $this->assertTrue($leida->fresh()->leida);
    }

    public function test_eliminar_alerta(): void
    {
        $alerta = $this->crearAlerta();

        $response = $this->actingAs($this->admin)->delete(route('alertas.destroy', $alerta));

        $response->assertRedirect();
        $this->assertDatabaseMissing('alertas_stock', ['id' => $alerta->id]);
    }

    public function test_contador_de_no_leidas_aparece_en_el_layout(): void
    {
        $this->crearAlerta(['leida' => false]);
        $this->crearAlerta(['leida' => false]);
        $this->crearAlerta(['leida' => true]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('2 alertas sin leer');
    }

    public function test_vendedor_tambien_puede_ver_alertas(): void
    {
        $this->crearAlerta();

        $response = $this->actingAs($this->vendedor)->get(route('alertas.index'));

        $response->assertOk();
    }

    public function test_vendedor_no_puede_marcar_alerta_como_leida(): void
    {
        $alerta = $this->crearAlerta(['leida' => false]);

        $response = $this->actingAs($this->vendedor)->patch(route('alertas.leida', $alerta));

        $response->assertForbidden();
        $this->assertFalse($alerta->fresh()->leida);
    }
}

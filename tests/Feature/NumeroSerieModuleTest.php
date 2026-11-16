<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NumeroSerieModuleTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    /**
     * El proyecto no tiene ProductoFactory: los tests crean el producto a mano.
     */
    private function crearProducto(): Producto
    {
        static $secuencia = 0;
        $secuencia++;

        $categoria = Categoria::firstOrCreate(
            ['nombre' => 'Series Test'],
            ['tipo' => 'Componente']
        );

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto serie '.$secuencia,
            'slug' => 'producto-serie-'.$secuencia,
            'precio_unitario' => 100,
            'stock' => 1,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);
    }

    public function test_admin_puede_ver_el_listado_de_series(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.series.index'));

        $response->assertOk();
        $response->assertSee('Números de Serie');
        $response->assertSee('+ Registrar Serie');
    }

    public function test_admin_puede_registrar_una_serie(): void
    {
        $producto = $this->crearProducto();

        $response = $this->actingAs($this->admin)->post(route('admin.series.store'), [
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-TEST-001',
            'costo_unitario' => 100,
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $serie = NumeroSerie::where('numero_serie', 'SN-TEST-001')->first();

        $this->assertNotNull($serie);
        $this->assertSame('disponible', $serie->estado);
        $response->assertRedirect(route('admin.series.show', $serie));
    }

    public function test_numero_de_serie_no_se_puede_repetir(): void
    {
        $producto = $this->crearProducto();
        NumeroSerie::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-DUP-001',
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.series.store'), [
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-DUP-001',
        ]);

        $response->assertSessionHasErrors('numero_serie');
    }

    public function test_admin_puede_cambiar_el_estado_de_una_serie(): void
    {
        $producto = $this->crearProducto();
        $serie = NumeroSerie::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-EDIT-001',
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.series.update', $serie), [
            'estado' => 'defectuoso',
            'notas' => 'No enciende',
        ]);

        $response->assertRedirect(route('admin.series.show', $serie));
        $this->assertSame('defectuoso', $serie->fresh()->estado);
    }

    public function test_vendedor_puede_consultar_pero_no_crear_series(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('admin.series.index'))
            ->assertOk();

        $this->actingAs($this->vendedor)
            ->get(route('admin.series.create'))
            ->assertForbidden();
    }

    public function test_busqueda_por_numero_de_serie(): void
    {
        $producto = $this->crearProducto();
        NumeroSerie::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'SN-BUSCAR-999',
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.series.index', ['buscar' => 'SN-BUSCAR-999']));

        $response->assertOk();
        $response->assertSee('SN-BUSCAR-999');
    }
}

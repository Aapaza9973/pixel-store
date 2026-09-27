<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_ve_metricas_reales_del_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Dashboard', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Dashboard']);
        $ubicacion = Ubicacion::create(['nombre' => 'Tienda Dashboard', 'tipo' => 'tienda', 'activa' => true]);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Producto Crítico Dashboard',
            'precio_unitario' => 100,
            'costo' => 50,
            'stock' => 2,
            'umbral_alerta' => 5,
            'sku' => 'DASH-001',
            'visible_catalogo' => true,
        ]);

        StockUbicacion::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacion->id,
            'cantidad' => 2,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Productos totales');
        $response->assertSee('Valor del inventario');
        $response->assertSee('Stock por categoría');
        $response->assertSee('Resumen por ubicación');
        $response->assertSee('Productos críticos');
        $response->assertSee('Tienda Dashboard');
        $response->assertSee('Producto Crítico Dashboard');
        $response->assertSee('Categoría Dashboard');
    }

    public function test_dashboard_muestra_mensaje_si_no_hay_criticos(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Todo el inventario está saludable.');
    }
}

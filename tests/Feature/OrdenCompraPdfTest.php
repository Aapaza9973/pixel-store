<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\OrdenCompraService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrdenCompraPdfTest extends TestCase
{
    use DatabaseTransactions;

    private User $inventario;

    private OrdenCompra $orden;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->inventario = User::where('email', 'inventario@pixelstore.com')->firstOrFail();

        $proveedor = Proveedor::create([
            'nombre' => 'Andes Hardware',
            'tipo' => 'Mayorista',
            'descuento_habitual' => 0,
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Categoría PDF Órdenes',
            'tipo' => 'Periférico',
        ]);

        $teclado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado PDF',
            'precio_unitario' => 200,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $mouse = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse PDF',
            'precio_unitario' => 100,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $this->orden = app(OrdenCompraService::class)->crear($proveedor, $this->inventario, [
            ['producto_id' => $teclado->id, 'cantidad' => 2, 'precio_unitario' => 150.50],
            ['producto_id' => $mouse->id, 'cantidad' => 1, 'precio_unitario' => 99.99],
        ], [
            'impuestos' => 40,
            'notas' => 'Reposición de periféricos',
        ]);
    }

    public function test_pdf_se_genera_correctamente(): void
    {
        $response = $this->actingAs($this->inventario)
            ->get(route('admin.compras.pdf', $this->orden));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_no_permite_acceso_sin_permiso(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->actingAs($vendedor)
            ->get(route('admin.compras.pdf', $this->orden))
            ->assertForbidden();
    }
}

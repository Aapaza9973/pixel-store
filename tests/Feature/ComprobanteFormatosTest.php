<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * HU-5.12.2: comprobante en ticket de 80 mm y en hoja A4.
 */
class ComprobanteFormatosTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Formatos', 'tipo' => 'Componente']);

        $monitor = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor Formatos',
            'precio_unitario' => 300,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);

        $this->venta = Venta::create([
            'fecha' => now(),
            'user_id' => $this->vendedor->id,
            'total' => 600,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        DetalleVenta::create([
            'venta_id' => $this->venta->id,
            'producto_id' => $monitor->id,
            'cantidad' => 2,
            'precio_unitario' => 300,
        ]);

        Pago::create([
            'venta_id' => $this->venta->id,
            'monto' => 600,
            'metodo' => 'Efectivo',
            'estado' => 'Completado',
        ]);
    }

    public function test_comprobante_ticket_usa_papel_de_80mm(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.comprobante', $this->venta));

        $response->assertOk();
        $response->assertSee('size: 80mm auto', false);
        $response->assertSee('#'.str_pad((string) $this->venta->id, 6, '0', STR_PAD_LEFT));
        $response->assertSee('600.00');
    }

    public function test_comprobante_a4_usa_hoja_a4(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.comprobante', [
                'venta' => $this->venta,
                'formato' => 'a4',
            ]));

        $response->assertOk();
        $response->assertSee('size: A4', false);
        $response->assertSee('PIXEL STORE');
        $response->assertSee('600.00');
        // El formato A4 no debe renderizar el ticket térmico.
        $response->assertDontSee('size: 80mm auto', false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FacturaElectronica;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FacturacionTest extends TestCase
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

    public function test_admin_emite_una_factura_para_una_venta_cobrada(): void
    {
        $venta = $this->crearVenta('Pagado');

        $response = $this->actingAs($this->admin)->post(route('admin.facturacion.store'), [
            'venta_id' => $venta->id,
        ]);

        $factura = FacturaElectronica::where('venta_id', $venta->id)->first();

        $this->assertNotNull($factura);
        $this->assertSame(49, strlen($factura->cuf));
        $this->assertStringStartsWith('F-', $factura->numero_factura);
        $response->assertRedirect(route('admin.facturacion.show', $factura));
    }

    public function test_no_se_factura_una_venta_pendiente(): void
    {
        $venta = $this->crearVenta('Pendiente');

        $this->actingAs($this->admin)
            ->post(route('admin.facturacion.store'), ['venta_id' => $venta->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('facturas_electronicas', 0);
    }

    public function test_no_se_emite_dos_veces_la_misma_factura(): void
    {
        $venta = $this->crearVenta('Pagado');

        $this->actingAs($this->admin)->post(route('admin.facturacion.store'), ['venta_id' => $venta->id]);
        $this->actingAs($this->admin)
            ->post(route('admin.facturacion.store'), ['venta_id' => $venta->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('facturas_electronicas', 1);
    }

    public function test_admin_anula_una_factura(): void
    {
        $venta = $this->crearVenta('Pagado');
        $this->actingAs($this->admin)->post(route('admin.facturacion.store'), ['venta_id' => $venta->id]);

        $factura = FacturaElectronica::where('venta_id', $venta->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.facturacion.anular', $factura), ['motivo' => 'Error de datos'])
            ->assertRedirect(route('admin.facturacion.show', $factura));

        $factura->refresh();
        $this->assertSame('Anulada', $factura->estado);
        $this->assertSame('Error de datos', $factura->motivo_anulacion);
    }

    public function test_vendedor_no_accede_a_facturacion(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('admin.facturacion.index'))
            ->assertForbidden();
    }

    private function crearVenta(string $estado): Venta
    {
        return Venta::create([
            'fecha' => now(),
            'total' => 250,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'puntos_canjeados' => 0,
            'estado' => $estado,
            'origen' => 'pos',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CotizacionTest extends TestCase
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'nombre_cliente' => 'Cliente Demo',
            'validez_dias' => 4,
            'descuento' => 0,
            'items' => [
                ['producto_id' => null, 'nombre' => 'Memoria RAM 16GB', 'precio_unitario' => 450, 'cantidad' => 2],
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearCotizacion(array $overrides = []): Cotizacion
    {
        return Cotizacion::create(array_merge([
            'numero' => 'COT-'.now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => $this->admin->id,
            'nombre_cliente' => 'Cliente Test',
            'subtotal' => 900,
            'descuento' => 0,
            'total' => 900,
            'validez_dias' => 4,
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(4),
            'estado' => 'Vigente',
        ], $overrides));
    }

    public function test_admin_puede_crear_cotizacion(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.cotizaciones.store'), $this->datosValidos());

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('cotizaciones', [
            'nombre_cliente' => 'Cliente Demo',
            'estado' => 'Vigente',
            'total' => 900.00,
        ]);
    }

    public function test_vendedor_puede_crear_cotizacion(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('admin.cotizaciones.store'), $this->datosValidos());

        $response->assertRedirect();
        $this->assertDatabaseHas('cotizaciones', ['nombre_cliente' => 'Cliente Demo']);
    }

    public function test_cliente_no_puede_crear_cotizacion(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('Cliente');

        $response = $this->actingAs($cliente)
            ->post(route('admin.cotizaciones.store'), $this->datosValidos());

        $response->assertForbidden();
    }

    public function test_pdf_se_genera(): void
    {
        $cotizacion = $this->crearCotizacion();
        $cotizacion->items()->create(['nombre' => 'Item 1', 'precio_unitario' => 900, 'cantidad' => 1]);

        $response = $this->actingAs($this->admin)->get(route('admin.cotizaciones.pdf', $cotizacion));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_admin_puede_anular_cotizacion(): void
    {
        $cotizacion = $this->crearCotizacion();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.cotizaciones.anular', $cotizacion));

        $response->assertRedirect();
        $this->assertSame('Anulada', $cotizacion->fresh()->estado);
    }

    public function test_convertir_cotizacion_a_venta(): void
    {
        $categoria = Categoria::create(['nombre' => 'Memorias '.uniqid(), 'tipo' => 'Componente']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'RAM 16GB '.uniqid(),
            'precio_unitario' => 450,
            'stock' => 10,
            'umbral_alerta' => 5,
        ]);

        $cotizacion = $this->crearCotizacion();
        $cotizacion->items()->create([
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio_unitario' => 450,
            'cantidad' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.cotizaciones.convertir', $cotizacion));

        $response->assertRedirect();
        $this->assertSame('Convertida', $cotizacion->fresh()->estado);
        $this->assertDatabaseHas('ventas', ['cotizacion_id' => $cotizacion->id]);
    }
}

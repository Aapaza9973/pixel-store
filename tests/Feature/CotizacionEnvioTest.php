<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\CotizacionEnviada;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CotizacionEnvioTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
    }

    public function test_envia_la_cotizacion_por_correo(): void
    {
        Mail::fake();
        $cotizacion = $this->crearCotizacion();

        $this->actingAs($this->admin)
            ->post(route('admin.cotizaciones.enviar', $cotizacion), ['email' => 'cliente@correo.com'])
            ->assertSessionHas('success');

        Mail::assertSent(CotizacionEnviada::class, fn (CotizacionEnviada $mail): bool => $mail->hasTo('cliente@correo.com'));
    }

    public function test_rechaza_un_correo_invalido(): void
    {
        Mail::fake();
        $cotizacion = $this->crearCotizacion();

        $this->actingAs($this->admin)
            ->post(route('admin.cotizaciones.enviar', $cotizacion), ['email' => 'no-es-correo'])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    private function crearCotizacion(): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'numero' => 'COT-TEST-'.random_int(1000, 9999),
            'user_id' => $this->admin->id,
            'nombre_cliente' => 'Cliente de prueba',
            'nit_ci' => '1234567',
            'telefono' => '70000000',
            'email' => 'cliente@correo.com',
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
            'validez_dias' => 4,
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(4),
            'estado' => 'Vigente',
        ]);

        CotizacionItem::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre' => 'Producto de prueba',
            'precio_unitario' => 100,
            'cantidad' => 1,
        ]);

        return $cotizacion;
    }
}

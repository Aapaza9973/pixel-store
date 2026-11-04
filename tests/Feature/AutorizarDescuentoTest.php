<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AutorizacionDescuento;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutorizarDescuentoTest extends TestCase
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

        // El limiter es por vendedor autenticado: se limpia entre tests.
        RateLimiter::clear('autorizar-descuento:'.$this->vendedor->id);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function payload(array $cambios = []): array
    {
        return array_merge([
            'email' => $this->admin->email,
            'password' => 'password',
            'operacion_id' => (string) Str::uuid(),
            'descuento_porcentaje' => 15,
            'monto_descuento' => 150,
            'motivo' => 'Cliente frecuente',
        ], $cambios);
    }

    public function test_autoriza_con_credenciales_admin_validas(): void
    {
        $payload = $this->payload();

        $response = $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.autorizar-descuento'), $payload);

        $response->assertCreated();
        $response->assertJsonStructure(['autorizacion_id', 'admin' => ['id', 'name'], 'operacion_id']);
        $response->assertJsonPath('operacion_id', $payload['operacion_id']);
        $response->assertJsonPath('admin.id', $this->admin->id);

        $autorizacion = AutorizacionDescuento::firstOrFail();

        $this->assertSame($this->admin->id, $autorizacion->admin_id);
        $this->assertSame($this->vendedor->id, $autorizacion->vendedor_id);
        $this->assertSame($payload['operacion_id'], $autorizacion->operacion_id);
        $this->assertNull($autorizacion->venta_id);
        $this->assertNotNull($autorizacion->ip);
        $this->assertSame('150.00', $autorizacion->monto_descuento);
        $this->assertSame('15.00', $autorizacion->descuento_porcentaje);

        // El admin autoriza, no inicia sesión: el vendedor sigue autenticado.
        $this->assertSame($this->vendedor->id, Auth::id());
        $this->assertTrue($autorizacion->admin->is($this->admin));
        $this->assertTrue($autorizacion->vendedor->is($this->vendedor));
    }

    public function test_rechaza_credenciales_invalidas(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.autorizar-descuento'),
            $this->payload(['password' => 'incorrecta'])
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('Credenciales incorrectas', (string) $response->json('message'));

        $this->assertDatabaseCount('autorizaciones_descuento', 0);
        $this->assertSame($this->vendedor->id, Auth::id());
    }

    public function test_rechaza_usuario_sin_rol_admin(): void
    {
        // Credenciales válidas de un Vendedor: autentican, pero no autorizan.
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.autorizar-descuento'),
            $this->payload(['email' => $this->vendedor->email])
        );

        $response->assertForbidden();
        $this->assertStringContainsString('no tiene permisos', (string) $response->json('message'));

        $this->assertDatabaseCount('autorizaciones_descuento', 0);
    }

    public function test_rate_limit_bloquea_tras_5_intentos(): void
    {
        $url = route('admin.ventas.autorizar-descuento');
        $payload = $this->payload(['password' => 'incorrecta']);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->actingAs($this->vendedor)
                ->postJson($url, $payload)
                ->assertStatus(422);
        }

        $this->actingAs($this->vendedor)
            ->postJson($url, $payload)
            ->assertStatus(429);

        $this->assertDatabaseCount('autorizaciones_descuento', 0);
    }
}

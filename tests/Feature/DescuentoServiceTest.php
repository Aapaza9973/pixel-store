<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Services\DescuentoService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DescuentoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DescuentoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->service = app(DescuentoService::class);
    }

    public function test_vendedor_puede_aplicar_descuento_dentro_del_limite(): void
    {
        $vendedor = Role::where('name', 'Vendedor')->firstOrFail();

        $this->assertSame('10.00', $vendedor->descuento_maximo);
        $this->assertTrue($this->service->validar($vendedor, 100.0, 1000.0));
    }

    public function test_vendedor_no_puede_aplicar_descuento_sobre_el_limite(): void
    {
        $this->assertFalse($this->service->validar('Vendedor', 150.0, 1000.0));
    }

    public function test_admin_puede_aplicar_cualquier_descuento(): void
    {
        $this->assertTrue($this->service->validar('Admin', 1000.0, 1000.0));
    }

    public function test_cajero_solo_puede_aplicar_hasta_5(): void
    {
        $this->assertFalse($this->service->validar('Cajero', 60.0, 1000.0));
        $this->assertTrue($this->service->validar('Cajero', 50.0, 1000.0));
    }

    public function test_descuento_cero_siempre_valido(): void
    {
        $this->assertTrue($this->service->validar('Cajero', 0.0, 1000.0));
        $this->assertTrue($this->service->validar('Inventario', 0.0, 0.0));
    }
}

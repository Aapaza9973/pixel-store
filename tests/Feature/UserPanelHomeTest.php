<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserPanelHomeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_panel_home_es_dashboard(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->assertSame('/dashboard', $admin->panelHome());
    }

    public function test_inventario_panel_home_es_inventario(): void
    {
        $inventario = User::where('email', 'inventario@pixelstore.com')->firstOrFail();

        $this->assertSame('/admin/inventario', $inventario->panelHome());
    }

    public function test_vendedor_panel_home_es_dashboard(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->assertSame('/dashboard', $vendedor->panelHome());
    }

    public function test_cajero_panel_home_es_dashboard(): void
    {
        $cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();

        $this->assertSame('/dashboard', $cajero->panelHome());
    }
}

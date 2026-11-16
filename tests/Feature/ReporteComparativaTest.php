<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReporteComparativaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_ve_la_comparativa_mensual(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.reportes.comparativa'))
            ->assertOk()
            ->assertSee('Comparativa mensual')
            ->assertSee('Variación');
    }

    public function test_vendedor_sin_permiso_de_reportes_no_accede(): void
    {
        $this->seed(RoleSeeder::class);
        $cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();

        $this->actingAs($cajero)
            ->get(route('admin.reportes.comparativa'))
            ->assertForbidden();
    }
}

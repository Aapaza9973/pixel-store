<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EncuestaTest extends TestCase
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

    public function test_el_formulario_publico_es_accesible_sin_cuenta(): void
    {
        $this->get(route('encuesta.create'))
            ->assertOk()
            ->assertSee('Encuesta de satisfacción');
    }

    public function test_cualquiera_puede_enviar_una_respuesta(): void
    {
        $response = $this->post(route('encuesta.store'), [
            'atencion' => 5,
            'catalogo' => 4,
            'recomendacion' => 5,
            'comentario' => 'Muy buen catálogo',
        ]);

        $response->assertRedirect(route('encuesta.gracias'));
        $this->assertDatabaseHas('encuestas', [
            'atencion' => 5,
            'catalogo' => 4,
            'recomendacion' => 5,
        ]);
    }

    public function test_la_calificacion_debe_estar_entre_1_y_5(): void
    {
        $response = $this->post(route('encuesta.store'), [
            'atencion' => 9,
            'catalogo' => 0,
            'recomendacion' => 3,
        ]);

        $response->assertSessionHasErrors(['atencion', 'catalogo']);
        $this->assertDatabaseCount('encuestas', 0);
    }

    public function test_admin_ve_los_resultados(): void
    {
        $this->post(route('encuesta.store'), [
            'atencion' => 4,
            'catalogo' => 4,
            'recomendacion' => 5,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.encuestas.index'))
            ->assertOk()
            ->assertSee('Encuestas')
            ->assertSee('Recomendación');
    }

    public function test_vendedor_no_accede_a_los_resultados(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('admin.encuestas.index'))
            ->assertForbidden();
    }
}

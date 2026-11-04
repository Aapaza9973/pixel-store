<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Producto;
use App\Models\User;
use App\Services\FavoritoService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class FavoritoTest extends TestCase
{
    use DatabaseTransactions;

    private Categoria $categoria;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoria = Categoria::create(['nombre' => 'Categoría Favoritos', 'tipo' => 'Componente']);
        $this->producto = $this->crearProducto('Producto Favorito', 1000);
    }

    private function crearProducto(string $nombre, float $precio, bool $visible = true): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::random(6),
            'precio_unitario' => $precio,
            'stock' => 5,
            'umbral_alerta' => 1,
            'visible_catalogo' => $visible,
        ]);
    }

    public function test_toggle_anonimo_guarda_por_session_token(): void
    {
        $primera = $this->postJson(route('catalogo.favoritos.toggle', $this->producto));

        $primera->assertOk()->assertJson(['favorito' => true]);
        $primera->assertCookie('favoritos_token');

        $token = $primera->getCookie('favoritos_token')->getValue();

        $this->assertDatabaseHas('favoritos', [
            'producto_id' => $this->producto->id,
            'session_token' => $token,
            'user_id' => null,
        ]);

        // El cliente de tests no arrastra cookies entre requests, y los requests
        // JSON solo las envían con `withCredentials`.
        $segunda = $this->withCredentials()->withCookie('favoritos_token', $token)
            ->postJson(route('catalogo.favoritos.toggle', $this->producto));

        $segunda->assertOk()->assertJson(['favorito' => false]);

        $this->assertDatabaseMissing('favoritos', [
            'producto_id' => $this->producto->id,
            'session_token' => $token,
        ]);
    }

    public function test_toggle_autenticado_guarda_por_user_id(): void
    {
        $user = User::factory()->create(['activo' => true]);

        $response = $this->actingAs($user)
            ->postJson(route('catalogo.favoritos.toggle', $this->producto));

        $response->assertOk()->assertJson(['favorito' => true]);

        $this->assertDatabaseHas('favoritos', [
            'producto_id' => $this->producto->id,
            'user_id' => $user->id,
            'session_token' => null,
        ]);
    }

    public function test_merge_de_favoritos_al_loguearse(): void
    {
        $token = Str::random(40);
        $user = User::factory()->create(['activo' => true]);

        app(FavoritoService::class)->toggle($this->producto, null, $token);

        request()->cookies->set('favoritos_token', $token);

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('favoritos', [
            'producto_id' => $this->producto->id,
            'user_id' => $user->id,
        ]);
        $this->assertSame(0, Favorito::query()->where('session_token', $token)->count());
    }

    public function test_pagina_favoritos_muestra_los_productos_del_usuario(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $visible = $this->crearProducto('Favorito Visible 2026', 1200);
        $oculto = $this->crearProducto('Favorito Oculto 2026', 1300, visible: false);

        Favorito::create(['user_id' => $user->id, 'producto_id' => $visible->id]);
        Favorito::create(['user_id' => $user->id, 'producto_id' => $oculto->id]);

        $response = $this->actingAs($user)->get(route('catalogo.favoritos.index'));

        $response->assertOk();
        $response->assertSee('Favorito Visible 2026');
        $response->assertDontSee('Favorito Oculto 2026');
    }
}

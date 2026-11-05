<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductoImagenTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Imágenes', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Imágenes']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Monitor Imágenes',
            'precio_unitario' => 1200,
            'stock' => 3,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);
    }

    public function test_admin_puede_subir_imagen(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image('monitor.jpg')]]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $imagen = ProductoImagen::where('producto_id', $this->producto->id)->firstOrFail();

        $this->assertTrue($imagen->es_principal);
        Storage::disk('public')->assertExists($imagen->path);
    }

    public function test_solo_la_primera_imagen_es_principal(): void
    {
        $this->actingAs($this->admin)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image('uno.jpg')]]
        );

        $this->actingAs($this->admin)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image('dos.jpg'), UploadedFile::fake()->image('tres.jpg')]]
        );

        $this->assertSame(1, ProductoImagen::where('producto_id', $this->producto->id)->where('es_principal', true)->count());
        $this->assertSame(3, ProductoImagen::where('producto_id', $this->producto->id)->count());
    }

    public function test_vendedor_no_puede_subir_imagen(): void
    {
        $response = $this->actingAs($this->vendedor)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image('monitor.jpg')]]
        );

        $response->assertForbidden();
        $this->assertSame(0, ProductoImagen::where('producto_id', $this->producto->id)->count());
    }

    public function test_admin_puede_eliminar_imagen(): void
    {
        $this->actingAs($this->admin)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image('borrar.jpg')]]
        );

        $imagen = ProductoImagen::where('producto_id', $this->producto->id)->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(
            route('admin.productos.imagenes.destroy', [$this->producto, $imagen])
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('producto_imagenes', ['id' => $imagen->id]);
        Storage::disk('public')->assertMissing($imagen->path);
    }
}

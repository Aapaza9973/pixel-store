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

class ProductoImagenOptimizadaTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Optimizada', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Optimizada']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Monitor Optimizado',
            'slug' => 'monitor-optimizado',
            'precio_unitario' => 1500,
            'stock' => 2,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);
    }

    private function subir(string $nombre, int $ancho, int $alto): ProductoImagen
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.productos.imagenes.store', $this->producto),
            ['imagenes' => [UploadedFile::fake()->image($nombre, $ancho, $alto)]]
        );

        $response->assertSessionHasNoErrors();

        return ProductoImagen::where('producto_id', $this->producto->id)->latest('id')->firstOrFail();
    }

    public function test_imagen_redimensionada_a_max_1200px(): void
    {
        $imagen = $this->subir('grande.jpg', 3000, 2000);

        Storage::disk('public')->assertExists($imagen->path);

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($imagen->path));

        $this->assertLessThanOrEqual(1200, $ancho);
        $this->assertLessThanOrEqual(1200, $alto);
        // Mantiene la proporción original (3:2).
        $this->assertSame(1200, $ancho);
        $this->assertSame(800, $alto);
    }

    public function test_thumbnail_generado(): void
    {
        $imagen = $this->subir('con-thumb.jpg', 1600, 1200);

        $this->assertStringContainsString('/thumbs/', $imagen->pathThumbnail());
        Storage::disk('public')->assertExists($imagen->pathThumbnail());

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($imagen->pathThumbnail()));

        $this->assertLessThanOrEqual(400, $ancho);
        $this->assertLessThanOrEqual(400, $alto);
    }

    public function test_catalogo_muestra_galeria(): void
    {
        $primera = ProductoImagen::create([
            'producto_id' => $this->producto->id,
            'path' => 'productos/'.$this->producto->id.'/uno.jpg',
            'alt' => 'Vista frontal',
            'orden' => 1,
            'es_principal' => true,
        ]);

        $segunda = ProductoImagen::create([
            'producto_id' => $this->producto->id,
            'path' => 'productos/'.$this->producto->id.'/dos.jpg',
            'alt' => 'Vista lateral',
            'orden' => 2,
            'es_principal' => false,
        ]);

        $response = $this->get(route('catalogo.producto', $this->producto->slug));

        $response->assertOk();
        $response->assertSee($primera->path, false);
        $response->assertSee($segunda->path, false);
    }
}

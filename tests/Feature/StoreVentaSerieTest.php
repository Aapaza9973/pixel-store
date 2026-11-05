<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-5.6.7: un producto que maneja número de serie no puede venderse sin uno.
 */
class StoreVentaSerieTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $serializado;

    private Producto $simple;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Serie Obligatoria', 'tipo' => 'Componente']);

        $this->serializado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'SSD Serie Obligatoria',
            'precio_unitario' => 500,
            'stock' => 3,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);

        $this->simple = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse Serie Obligatoria',
            'precio_unitario' => 100,
            'stock' => 5,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => false,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function payload(array $items, float $monto): array
    {
        return [
            'items' => $items,
            'pagos' => [
                ['metodo' => 'Efectivo', 'monto' => $monto],
            ],
            'meta' => [
                'operacion_id' => (string) Str::uuid(),
            ],
        ];
    }

    public function test_venta_rechaza_producto_requiere_serie_sin_serie(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                ['producto_id' => $this->serializado->id, 'cantidad' => 1],
            ], 500)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items.0.numero_serie_id');
        // La clave del error es plana ("items.0.numero_serie_id"), no anidada.
        $response->assertJsonFragment([
            'items.0.numero_serie_id' => ['Seleccioná el número de serie del producto.'],
        ]);

        $this->assertSame(0, Venta::count());
        $this->assertSame(3, (int) $this->serializado->fresh()->stock);
    }

    public function test_venta_permite_producto_sin_requiere_serie_sin_serie(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                ['producto_id' => $this->simple->id, 'cantidad' => 1],
            ], 100)
        );

        $response->assertCreated();
        $response->assertJsonPath('venta.estado', 'Pagado');

        $this->assertSame(1, Venta::count());
        $this->assertSame(4, (int) $this->simple->fresh()->stock);
    }

    public function test_venta_permite_requiere_serie_con_serie(): void
    {
        $serie = NumeroSerie::create([
            'producto_id' => $this->serializado->id,
            'numero_serie' => 'SN-SERIE-'.Str::upper(Str::random(8)),
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                [
                    'producto_id' => $this->serializado->id,
                    'cantidad' => 1,
                    'numero_serie_id' => $serie->id,
                ],
            ], 500)
        );

        $response->assertCreated();
        $response->assertJsonPath('venta.estado', 'Pagado');

        $serie->refresh();
        $this->assertSame('vendido', $serie->estado);
        $this->assertNotNull($serie->venta_id);
        $this->assertSame(2, (int) $this->serializado->fresh()->stock);
    }
}

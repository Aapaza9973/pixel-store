<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\SerieNoDisponibleException;
use App\Models\Categoria;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\NumeroSerieService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NumeroSerieServiceTest extends TestCase
{
    use DatabaseTransactions;

    private NumeroSerieService $service;

    private Producto $producto;

    private Producto $otroProducto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NumeroSerieService::class);

        $categoria = Categoria::create(['nombre' => 'Categoría Series', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Notebook Series',
            'precio_unitario' => 5000,
            'stock' => 2,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);

        $this->otroProducto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Notebook Series Pro',
            'precio_unitario' => 7000,
            'stock' => 1,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearSerie(array $cambios = []): NumeroSerie
    {
        return NumeroSerie::create(array_merge([
            'producto_id' => $this->producto->id,
            'numero_serie' => 'SN-'.uniqid(),
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ], $cambios));
    }

    public function test_sn_valido_retorna_numero_serie(): void
    {
        $serie = $this->crearSerie();

        $resultado = $this->service->validarParaVenta($serie->id, $this->producto->id);

        $this->assertSame($serie->id, $resultado->id);
    }

    public function test_sn_inexistente_lanza_excepcion(): void
    {
        $this->expectException(SerieNoDisponibleException::class);
        $this->expectExceptionMessage('El número de serie no existe.');

        $this->service->validarParaVenta(999999, $this->producto->id);
    }

    public function test_sn_de_otro_producto_lanza_excepcion(): void
    {
        $serie = $this->crearSerie(['producto_id' => $this->otroProducto->id]);

        $this->expectException(SerieNoDisponibleException::class);
        $this->expectExceptionMessage('El número de serie no corresponde al producto.');

        $this->service->validarParaVenta($serie->id, $this->producto->id);
    }

    public function test_sn_ya_vendido_lanza_excepcion(): void
    {
        $serie = $this->crearSerie(['estado' => 'vendido']);

        $this->expectException(SerieNoDisponibleException::class);
        $this->expectExceptionMessage('El número de serie ya fue vendido.');

        $this->service->validarParaVenta($serie->id, $this->producto->id);
    }

    public function test_sn_con_venta_id_lanza_excepcion(): void
    {
        // Estado disponible pero con una venta asociada: no debe venderse de nuevo.
        $usuario = User::create([
            'name' => 'Cajero Series',
            'email' => 'series@pixelstore.com',
            'password' => bcrypt('password'),
            'activo' => true,
        ]);

        $venta = Venta::create([
            'user_id' => $usuario->id,
            'total' => 0,
            'estado' => 'Pagado',
        ]);

        $serie = $this->crearSerie(['venta_id' => $venta->id]);

        $this->expectException(SerieNoDisponibleException::class);
        $this->expectExceptionMessage('El número de serie ya fue vendido.');

        $this->service->validarParaVenta($serie->id, $this->producto->id);
    }
}

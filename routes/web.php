<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\CierreCajaController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\DevolucionController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\OrdenCompraController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\ProductoImagenController;
use App\Http\Controllers\Admin\ProveedorController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\UbicacionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ValidarStockController;
use App\Http\Controllers\Admin\VentaController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// SPIKE: prototipo de landing publica. NO es la home real.
// Ver docs/rediseno/00-plan.md. Eliminar antes de produccion.
Route::get('/preview', [PreviewController::class, 'index'])->name('preview');

// Catálogo público (Sprint 2). Layout público definitivo: Día 2+.
Route::get('/producto/{slug}', [CatalogoController::class, 'show'])->name('catalogo.producto');

// Favoritos (2.7.3-2.7.4): sirven para visitantes y usuarios autenticados.
Route::post('/favoritos/{producto}', [FavoritoController::class, 'toggle'])->name('catalogo.favoritos.toggle');
Route::get('/favoritos', [FavoritoController::class, 'index'])->name('catalogo.favoritos.index');

Route::middleware(['auth', 'verified', 'user.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/session/extend', function (Request $request) {
        $lifetime = (int) config('session.lifetime', 120);
        $request->session()->save();

        return response()->json([
            'status' => 'extended',
            'lifetime' => $lifetime,
            'expires_at' => now()->addMinutes($lifetime)->toIso8601String(),
            'csrf_token' => csrf_token(),
        ]);
    })->name('session.extend');

    // Admin
    Route::middleware('user.has.role')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('categorias', CategoriaController::class);
            Route::resource('productos', ProductoController::class);
            Route::post('productos/{producto}/transferir-stock', [ProductoController::class, 'transferirStock'])
                ->name('productos.transferir-stock');
            Route::post('movimientos/salida', [\App\Http\Controllers\Admin\MovimientoController::class, 'registrarSalida'])
                ->name('movimientos.salida');
            Route::resource('ubicaciones', UbicacionController::class)
                ->parameters(['ubicaciones' => 'ubicacion']);
            Route::resource('usuarios', UserController::class)
                ->parameters(['usuarios' => 'user']);
            Route::post('usuarios/{user}/desactivar', [UserController::class, 'desactivar'])
                ->name('usuarios.desactivar');
            Route::post('usuarios/{user}/activar', [UserController::class, 'activar'])
                ->name('usuarios.activar');
            Route::post('usuarios/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('usuarios.reset-password');
            Route::get('usuarios/{user}/historial', [UserController::class, 'historial'])
                ->name('usuarios.historial');

            Route::get('ventas/nueva', [VentaController::class, 'create'])
                ->name('ventas.create');

            Route::post('ventas', [VentaController::class, 'store'])
                ->name('ventas.store');

            // 5.4.2: el POS revalida stock antes de confirmar.
            Route::post('ventas/validar-stock', ValidarStockController::class)
                ->name('ventas.validar-stock');

            // HU-5.11: el vendedor pide que un admin autorice el descuento.
            Route::post('ventas/autorizar-descuento', [VentaController::class, 'autorizarDescuento'])
                ->middleware('throttle:autorizar-descuento')
                ->name('ventas.autorizar-descuento');

            Route::get('ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])
                ->name('ventas.comprobante');

            // Debe declararse antes de ventas/{venta}: si no, "buscar-productos" cae en show.
            Route::get('ventas/buscar-productos', [VentaController::class, 'buscarProductos'])
                ->middleware('throttle:60,1')
                ->name('ventas.buscar-productos');

            Route::get('ventas/{venta}', [VentaController::class, 'show'])
                ->name('ventas.show');

            Route::post('ventas/{venta}/devoluciones', [DevolucionController::class, 'store'])
                ->name('ventas.devoluciones.store');

            Route::get('productos/{producto}/series', [VentaController::class, 'series'])
                ->name('productos.series');

            // Debe declararse antes del resource: si no, "buscar" cae en show.
            Route::get('clientes/buscar', [ClienteController::class, 'buscar'])
                ->name('clientes.buscar');

            Route::resource('clientes', ClienteController::class)
                ->parameters(['clientes' => 'cliente']);

            Route::resource('proveedores', ProveedorController::class)
                ->parameters(['proveedores' => 'proveedor']);

            // HU-6.1: cierre de caja por turno y reporte diario.
            Route::get('caja', [CierreCajaController::class, 'index'])
                ->name('caja.index');
            Route::get('caja/crear', [CierreCajaController::class, 'create'])
                ->name('caja.create');
            Route::post('caja', [CierreCajaController::class, 'store'])
                ->name('caja.store');
            Route::get('caja/reporte/{fecha?}', [CierreCajaController::class, 'reporte'])
                ->name('caja.reporte');

            // HU-6.2: reportes agregados por período.
            Route::get('reportes/periodo', [ReporteController::class, 'periodo'])
                ->name('reportes.periodo');

            // 3.4.11-3.4.12: PDF de la orden de compra.
            Route::get('compras/{orden}/pdf', [OrdenCompraController::class, 'pdf'])
                ->name('compras.pdf');

            Route::post('productos/{producto}/imagenes', [ProductoImagenController::class, 'store'])
                ->name('productos.imagenes.store');
            Route::delete('productos/{producto}/imagenes/{imagen}', [ProductoImagenController::class, 'destroy'])
                ->name('productos.imagenes.destroy');

            Route::middleware('inventario.access')->group(function () {
                Route::get('inventario', [InventarioController::class, 'index'])
                    ->name('inventario.index');
            });
        });
        Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    });

    // Alertas de stock
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::patch('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida'])->name('alertas.leida');
    Route::post('/alertas/marcar-todas', [AlertaController::class, 'marcarTodas'])->name('alertas.marcar-todas');
    Route::delete('/alertas/{alerta}', [AlertaController::class, 'destroy'])->name('alertas.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

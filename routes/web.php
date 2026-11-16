<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\CierreCajaController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\CotizacionController;
use App\Http\Controllers\Admin\DevolucionController;
use App\Http\Controllers\Admin\FacturaController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\NumeroSerieController;
use App\Http\Controllers\Admin\OrdenCompraController;
use App\Http\Controllers\Admin\PedidoWebController;
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
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\PagoPedidoController;
use App\Http\Controllers\PedidoPublicoController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;


if ($shopDomain = config('shop.domain')) {
    Route::domain($shopDomain)->group(function () {
        Route::get('/', [ShopController::class, 'home']);
    });
}

Route::get('/', function () {
    return redirect()->route('login');
});

// SPIKE: prototipo de landing publica. NO es la home real.
// Ver docs/rediseno/00-plan.md. Eliminar antes de produccion.
Route::get('/preview', [PreviewController::class, 'index'])->name('preview');

// Catálogo público (Sprint 2). Layout público definitivo: Día 2+.
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/producto/{slug}', [CatalogoController::class, 'show'])->name('catalogo.producto');

// Checkout público: crea el pedido como venta Pendiente (origen web).
Route::get('/catalogo/checkout', [CheckoutController::class, 'index'])->name('catalogo.checkout');
Route::post('/catalogo/checkout', [CheckoutController::class, 'store'])->name('catalogo.checkout.store');
Route::get('/catalogo/gracias/{venta}', [CheckoutController::class, 'gracias'])
    ->middleware('signed')
    ->name('catalogo.gracias');

// Favoritos (2.7.3-2.7.4): sirven para visitantes y usuarios autenticados.
Route::post('/favoritos/{producto}', [FavoritoController::class, 'toggle'])->name('catalogo.favoritos.toggle');
Route::get('/favoritos', [FavoritoController::class, 'index'])->name('catalogo.favoritos.index');

// HU-3.5: encuesta anónima (sin cuenta, sin IP, sin usuario).
Route::get('/encuesta', [EncuestaController::class, 'create'])->name('encuesta.create');
Route::post('/encuesta', [EncuestaController::class, 'store'])->name('encuesta.store');
Route::get('/encuesta/gracias', [EncuestaController::class, 'gracias'])->name('encuesta.gracias');

// HU-3.3: enlaces del correo del pedido. El token es la única credencial.
Route::get('/pedido/{venta}/confirmar/{token}', [PedidoPublicoController::class, 'confirmar'])
    ->name('pedido.confirmar');
Route::get('/pedido/{venta}/cancelar/{token}', [PedidoPublicoController::class, 'cancelar'])
    ->name('pedido.cancelar');

// HU-3.4: pago en línea (modo simulación hasta integrar Stripe/PayPal).
Route::get('/pedido/{venta}/pagar/{token}', [PagoPedidoController::class, 'show'])
    ->name('pedido.pagar.form');
Route::post('/pedido/{venta}/pagar/{token}', [PagoPedidoController::class, 'store'])
    ->name('pedido.pagar');

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
                ->middleware('can:editar productos')
                ->name('movimientos.salida');
            Route::resource('ubicaciones', UbicacionController::class)
                ->parameters(['ubicaciones' => 'ubicacion']);
            Route::get('usuarios/export', [UserController::class, 'export'])
                ->name('usuarios.export');

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

            // Historial general (POS + web). Se declara antes de ventas/{venta}.
            Route::get('ventas', [VentaController::class, 'index'])
                ->name('ventas.index');

            Route::get('ventas/nueva', [VentaController::class, 'create'])
                ->name('ventas.create');

            Route::post('ventas', [VentaController::class, 'store'])
                ->name('ventas.store');

            // 5.4.2: el POS revalida stock antes de confirmar.
            Route::post('ventas/validar-stock', ValidarStockController::class)
                ->name('ventas.validar-stock');

            // 5.7.3: el POS valida la serie antes de asignarla a la línea.
            Route::post('ventas/validar-serie', [VentaController::class, 'validarSerie'])
                ->name('ventas.validar-serie');

            // HU-5.11: el vendedor pide que un admin autorice el descuento.
            Route::post('ventas/autorizar-descuento', [VentaController::class, 'autorizarDescuento'])
                ->middleware('throttle:autorizar-descuento')
                ->name('ventas.autorizar-descuento');

            Route::get('ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])
                ->name('ventas.comprobante');

            // Pedidos del catálogo público: cobro y cancelación con reingreso.
            Route::get('pedidos-web', [PedidoWebController::class, 'index'])
                ->name('pedidos-web.index');
            Route::get('pedidos-web/{venta}', [PedidoWebController::class, 'show'])
                ->name('pedidos-web.show');
            Route::post('pedidos-web/{venta}/pagar', [PedidoWebController::class, 'pagar'])
                ->name('pedidos-web.pagar');
            Route::post('pedidos-web/{venta}/cancelar', [PedidoWebController::class, 'cancelar'])
                ->name('pedidos-web.cancelar');

            // Debe declararse antes de ventas/{venta}: si no, "buscar-productos" cae en show.
            Route::get('ventas/buscar-productos', [VentaController::class, 'buscarProductos'])
                ->middleware('throttle:60,1')
                ->name('ventas.buscar-productos');

            Route::get('ventas/{venta}', [VentaController::class, 'show'])
                ->name('ventas.show');

            Route::post('ventas/{venta}/devoluciones', [DevolucionController::class, 'store'])
                ->name('ventas.devoluciones.store');

            // Anulación de una venta cobrada, con reingreso de stock (solo Admin).
            Route::post('ventas/{venta}/anular', [VentaController::class, 'anular'])
                ->name('ventas.anular');

            Route::get('productos/{producto}/series', [VentaController::class, 'series'])
                ->name('productos.series');

            // Debe declararse antes del resource: si no, "buscar" cae en show.
            Route::get('clientes/buscar', [ClienteController::class, 'buscar'])
                ->name('clientes.buscar');

            Route::resource('clientes', ClienteController::class)
                ->parameters(['clientes' => 'cliente']);

            Route::resource('proveedores', ProveedorController::class)
                ->parameters(['proveedores' => 'proveedor']);

            // HU Fase 2.1: cotizaciones formales.
            // Solo las acciones implementadas: `edit`, `update` y `destroy` no
            // existen en el controlador y cualquier enlace a ellas daba 500.
            Route::resource('cotizaciones', CotizacionController::class)
                ->parameters(['cotizaciones' => 'cotizacion'])
                ->only(['index', 'create', 'store', 'show']);

            // Trazabilidad de hardware (HU-2.2b): CRUD y consulta de garantía.
            Route::resource('series', NumeroSerieController::class)
                ->parameters(['series' => 'numeroSerie'])
                ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

            // HU-2.1b: envío de la cotización por correo.
            Route::post('cotizaciones/{cotizacion}/enviar', [CotizacionController::class, 'enviar'])
                ->name('cotizaciones.enviar');

            // HU-2.4b: bandeja de devoluciones con aprobación/rechazo.
            Route::get('devoluciones', [DevolucionController::class, 'index'])
                ->name('devoluciones.index');
            Route::post('devoluciones/{venta}/solicitar', [DevolucionController::class, 'solicitar'])
                ->name('devoluciones.solicitar');
            Route::post('devoluciones/{devolucion}/aprobar', [DevolucionController::class, 'aprobar'])
                ->name('devoluciones.aprobar');
            Route::post('devoluciones/{devolucion}/rechazar', [DevolucionController::class, 'rechazar'])
                ->name('devoluciones.rechazar');
            Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])
                ->name('cotizaciones.pdf');
            Route::post('cotizaciones/{cotizacion}/convertir', [CotizacionController::class, 'convertir'])
                ->name('cotizaciones.convertir');
            Route::post('cotizaciones/{cotizacion}/anular', [CotizacionController::class, 'anular'])
                ->name('cotizaciones.anular');

            // HU-4.3.3: comparativa mensual de ventas.
            Route::get('reportes/comparativa', [ReporteController::class, 'comparativa'])
                ->name('reportes.comparativa');

            // HU-4.1: facturación electrónica (emisión simulada).
            Route::get('facturacion', [FacturaController::class, 'index'])
                ->name('facturacion.index');
            Route::post('facturacion', [FacturaController::class, 'store'])
                ->name('facturacion.store');
            Route::get('facturacion/{factura}', [FacturaController::class, 'show'])
                ->name('facturacion.show');
            Route::post('facturacion/{factura}/anular', [FacturaController::class, 'anular'])
                ->name('facturacion.anular');

            // HU-4.4: consulta y exportación de auditoría.
            Route::get('auditoria', [AuditoriaController::class, 'index'])
                ->name('auditoria.index');
            Route::get('auditoria/export', [AuditoriaController::class, 'export'])
                ->name('auditoria.export');

            // HU-3.5: resultados internos de la encuesta anónima.
            Route::get('encuestas', [\App\Http\Controllers\Admin\EncuestaController::class, 'index'])
                ->name('encuestas.index');

            // HU-6.1: cierre de caja por turno y reporte diario.
            Route::get('caja', [CierreCajaController::class, 'index'])
                ->name('caja.index');
            Route::get('caja/crear', [CierreCajaController::class, 'create'])
                ->name('caja.create');
            Route::post('caja', [CierreCajaController::class, 'store'])
                ->name('caja.store');
            Route::get('caja/reporte/{fecha?}', [CierreCajaController::class, 'reporte'])
                ->name('caja.reporte');
            Route::get('caja/{cierre}/pdf', [CierreCajaController::class, 'pdf'])
                ->name('caja.pdf');

            // HU-6.2: reportes agregados por período.
            Route::get('reportes/periodo', [ReporteController::class, 'periodo'])
                ->name('reportes.periodo');
            Route::get('reportes/excel', [ReporteController::class, 'exportarExcel'])
                ->name('reportes.excel');
            Route::get('reportes/vendedor', [ReporteController::class, 'vendedor'])
                ->middleware('can:ver reportes')
                ->name('reportes.vendedor');
            Route::get('reportes/categoria', [ReporteController::class, 'categoria'])
                ->middleware('can:ver reportes')
                ->name('reportes.categoria');
            Route::get('reportes/metodos', [ReporteController::class, 'metodos'])
                ->middleware('can:ver reportes')
                ->name('reportes.metodos');

            // HU-6.4: utilidad, ranking y antigüedad de inventario.
            Route::get('reportes/indicadores', [ReporteController::class, 'indicadores'])
                ->middleware('can:ver reportes')
                ->name('reportes.indicadores');

            // HU-6.5: exportación PDF de cualquier reporte (?tipo=).
            Route::get('reportes/pdf', [ReporteController::class, 'pdf'])
                ->middleware('can:ver reportes')
                ->name('reportes.pdf');

            // 3.4.9-3.4.10: listado, alta, detalle y recepción de órdenes.
            Route::get('compras', [OrdenCompraController::class, 'index'])
                ->name('compras.index');

            Route::get('compras/crear', [OrdenCompraController::class, 'create'])
                ->name('compras.create');

            Route::get('compras/{orden}/vista-previa', [OrdenCompraController::class, 'preview'])
                ->name('compras.preview');

            Route::post('compras/{orden}/emitir', [OrdenCompraController::class, 'emitir'])
                ->name('compras.emitir');

            // UI del sprint: "Enviar Orden" desde el detalle (misma lógica que emitir).
            Route::post('compras/{orden}/enviar', [OrdenCompraController::class, 'enviar'])
                ->name('compras.enviar');

            Route::post('compras', [OrdenCompraController::class, 'store'])
                ->name('compras.store');

            Route::get('compras/{orden}', [OrdenCompraController::class, 'show'])
                ->name('compras.show');

            Route::post('compras/{orden}/recepciones', [OrdenCompraController::class, 'recibir'])
                ->name('compras.recibir');

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
                Route::get('inventario/kpis', [InventarioController::class, 'kpis'])
                    ->name('inventario.kpis');
            });
        });
        Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    });

    // Alertas de stock
    Route::patch('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida'])->name('alertas.leida');
    Route::post('/alertas/marcar-todas', [AlertaController::class, 'marcarTodas'])->name('alertas.marcar-todas');
    Route::delete('/alertas/{alerta}', [AlertaController::class, 'destroy'])->name('alertas.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

     //  RUTAS PÚBLICAS DE LA TIENDA Y PROCESO DE COMPRA (E-COMMERCE)
    Route::get('/tienda', [ShopController::class, 'home'])->name('shop.home');   
     Route::get('/catalogo', [ShopController::class, 'index'])->name('shop.index');
        Route::get('/catalogo/producto/{ref}', [ShopController::class, 'show'])->name('shop.show');

    Route::prefix('carrito')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/agregar/{producto}', [CartController::class, 'store'])->whereNumber('producto')->name('store');
        Route::patch('/{producto}', [CartController::class, 'update'])->whereNumber('producto')->name('update');
        Route::delete('/{producto}', [CartController::class, 'destroy'])->whereNumber('producto')->name('destroy');
    });

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'create'])->name('create');
        Route::post('/', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/gracias/{token}', [CheckoutController::class, 'success'])->where('token', '[A-Za-z0-9]{48}')->name('success');
    });
require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\UbicacionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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
            ->parameters(['usuarios' => 'usuario']);
        Route::post('usuarios/{usuario}/desactivar', [UserController::class, 'desactivar'])
            ->name('usuarios.desactivar');
        Route::post('usuarios/{usuario}/activar', [UserController::class, 'activar'])
            ->name('usuarios.activar');
        Route::post('usuarios/{usuario}/reset-password', [UserController::class, 'resetPassword'])
            ->name('usuarios.reset-password');
        Route::get('usuarios/{usuario}/historial', [UserController::class, 'historial'])
            ->name('usuarios.historial');
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

<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'user.active'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Extender sesión (usado por el modal de inactividad)
    Route::post('/session/extend', function (Request $request) {
        $lifetime = (int) config('session.lifetime', 120);

        // Guardar la sesión ahora fuerza a Laravel a reescribir su
        // `last_activity` en el backend (y en el driver de archivo, su mtime)
        // y a reemitir la cookie con una expiración renovada en esta misma
        // respuesta. Es lo que realmente extiende la vida de la sesión.
        //
        // A propósito NO usamos regenerate(): rotaría el id de sesión y, con
        // él, el token CSRF, dejando inservibles el meta tag y los formularios
        // ya renderizados (el siguiente POST fallaría con 419).
        $request->session()->save();

        return response()->json([
            'status'     => 'extended',
            'lifetime'   => $lifetime,
            'expires_at' => now()->addMinutes($lifetime)->toIso8601String(),
            'csrf_token' => csrf_token(),
        ]);
    })->name('session.extend');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

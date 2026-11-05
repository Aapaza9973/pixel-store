<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        // Rechazar la reutilización de las últimas 5 contraseñas (HU-1.6).
        $recientes = $user->passwordHistories()
            ->latest('id')
            ->limit(5)
            ->pluck('password_hash');

        foreach ($recientes as $hash) {
            if (Hash::check($validated['password'], $hash)) {
                return back()->withErrors([
                    'password' => 'La nueva contraseña no puede ser igual a ninguna de tus últimas 5 contraseñas.',
                ], 'updatePassword');
            }
        }

        // Conservar la contraseña actual en el historial antes de reemplazarla.
        $user->passwordHistories()->create([
            'password_hash' => $user->password,
        ]);

        // Mantener únicamente las últimas 5 contraseñas.
        $idsAntiguos = $user->passwordHistories()
            ->latest('id')
            ->skip(5)
            ->pluck('id');

        if ($idsAntiguos->isNotEmpty()) {
            $user->passwordHistories()->whereIn('id', $idsAntiguos)->delete();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user->notify(new PasswordChanged);

        return back()->with('status', 'password-updated');
    }
}

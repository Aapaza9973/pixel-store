<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogAuditoria;
use App\Models\User;

class AuditoriaService
{
    /**
     * Registra un inicio de sesión exitoso.
     */
    public function registrarLoginExitoso(User $user): LogAuditoria
    {
        $ip = request()->ip();

        return LogAuditoria::create([
            'user_id' => $user->id,
            'accion' => 'login',
            'modelo' => User::class,
            'modelo_id' => $user->id,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'email' => $user->email,
                'ip' => $ip,
            ],
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registra el cierre de sesión de un usuario.
     */
    public function registrarLogout(?User $user): ?LogAuditoria
    {
        $ip = request()->ip();

        return LogAuditoria::create([
            'user_id' => $user?->id,
            'accion' => 'logout',
            'modelo' => $user !== null ? User::class : null,
            'modelo_id' => $user?->id,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'email' => $user?->email,
                'ip' => $ip,
            ],
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registra un intento fallido de autenticación.
     */
    public function registrarIntentoFallido(string $email, string $ip): LogAuditoria
    {
        return LogAuditoria::create([
            'user_id' => null,
            'accion' => 'login_fallido',
            'modelo' => null,
            'modelo_id' => null,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'email' => $email,
                'ip' => $ip,
            ],
            'ip' => $ip !== '' ? $ip : request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registra un cambio de roles en la auditoría.
     *
     * `syncRoles()` no dispara eventos de Eloquent, por lo que este método
     * cubre la subtarea 1.1.15.6 (cambio de rol queda en `logs_auditoria`).
     *
     * @param  array<int, string>  $rolesAntes
     * @param  array<int, string>  $rolesDespues
     */
    public function registrarCambioRoles(User $user, array $rolesAntes, array $rolesDespues): void
    {
        $ip = request()->ip();

        try {
            LogAuditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'cambiar_roles_user',
                'modelo' => User::class,
                'modelo_id' => $user->id,
                'datos_anteriores' => ['roles' => $rolesAntes],
                'datos_nuevos' => ['roles' => $rolesDespues],
                'ip' => $ip,
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Registra en `logs_auditoria` los cambios CRUD sobre usuarios.
 *
 * Se registra en `AppServiceProvider::boot()` (Laravel 11 no usa EventServiceProvider).
 * Patrón de referencia: `skills/hu-development.md` §3.9 OBSERVER.
 */
class UserObserver
{
    /**
     * Atributos que JAMÁS deben persistirse en auditoría (credenciales y tokens).
     *
     * @var list<string>
     */
    private const BLOQUEADOS = ['password', 'remember_token'];

    /**
     * Registra la creación de un usuario con sus datos finales (sin credenciales).
     */
    public function created(User $user): void
    {
        $this->registrar(
            'crear_user',
            $user,
            null,
            Arr::except($user->getAttributes(), self::BLOQUEADOS)
        );
    }

    /**
     * Registra únicamente el diff real de la edición.
     *
     * Sin cambios (o si el único cambio es una credencial) no genera registro,
     * para no ensuciar la auditoría con eventos sin valor.
     */
    public function updated(User $user): void
    {
        $cambios = Arr::except($user->getChanges(), self::BLOQUEADOS);

        if ($cambios === []) {
            return;
        }

        // Solo las claves que cambiaron, con su valor anterior.
        $anteriores = Arr::only($user->getOriginal(), array_keys($cambios));

        $this->registrar('editar_user', $user, $anteriores, $cambios);
    }

    /**
     * Registra la eliminación conservando el estado previo del usuario.
     */
    public function deleted(User $user): void
    {
        $this->registrar(
            'eliminar_user',
            $user,
            Arr::except($user->getOriginal(), self::BLOQUEADOS),
            null
        );
    }

    /**
     * Escribe el registro de auditoría.
     *
     * @param  array<string, mixed>|null  $datosAnteriores  estado previo (null si no aplica)
     * @param  array<string, mixed>|null  $datosNuevos  estado nuevo (null si no aplica)
     */
    private function registrar(string $accion, User $user, ?array $datosAnteriores, ?array $datosNuevos): void
    {
        if (! auth()->check()) {
            return;
        }

        // Auditoría best-effort: un fallo al registrarla nunca debe romper el CRUD.
        try {
            LogAuditoria::create([
                'user_id' => auth()->id(),
                'accion' => $accion,
                'modelo' => User::class,
                'modelo_id' => $user->id,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

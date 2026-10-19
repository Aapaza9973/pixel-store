<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {}

    /**
     * Maneja el evento de login fallido.
     */
    public function handle(Failed $event): void
    {
        try {
            $user = $event->user instanceof User ? $event->user : null;
            $loginIntentado = (string) ($event->credentials['email'] ?? $user?->email ?? request()->input('email', ''));
            $ip = (string) (request()->ip() ?? '');

            $this->auditoriaService->registrarIntentoFallido($loginIntentado, $ip);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

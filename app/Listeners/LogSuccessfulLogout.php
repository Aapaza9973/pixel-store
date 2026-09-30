<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {}

    /**
     * Maneja el evento de cierre de sesión.
     */
    public function handle(Logout $event): void
    {
        try {
            $user = $event->user instanceof User ? $event->user : null;
            $this->auditoriaService->registrarLogout($user);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {}

    /**
     * Maneja el evento de login exitoso.
     */
    public function handle(Login $event): void
    {
        try {
            if ($event->user instanceof User) {
                $this->auditoriaService->registrarLoginExitoso($event->user);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

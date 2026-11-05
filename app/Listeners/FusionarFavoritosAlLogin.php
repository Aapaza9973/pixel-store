<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\FavoritoService;
use Illuminate\Auth\Events\Login;

/**
 * 2.7.5: al loguearse, los favoritos guardados por cookie de visitante pasan
 * al usuario. El token se lee de la cookie de la request en curso.
 */
class FusionarFavoritosAlLogin
{
    public function __construct(
        private readonly FavoritoService $favoritoService
    ) {}

    public function handle(Login $event): void
    {
        $token = request()->cookie('favoritos_token');

        if (! $event->user instanceof User || ! is_string($token) || $token === '') {
            return;
        }

        try {
            $this->favoritoService->fusionar($token, $event->user);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

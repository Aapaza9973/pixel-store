<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Favorito;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 2.7.2-2.7.5: favoritos de usuarios autenticados y de visitantes anónimos.
 *
 * Un dueño es un user() o un session_token (cookie del visitante). La base
 * garantiza con índices únicos parciales que no haya duplicados por dueño.
 */
class FavoritoService
{
    /**
     * Alterna el favorito del producto para el dueño indicado.
     *
     * @return bool true si quedó guardado, false si se quitó.
     */
    public function toggle(Producto $producto, ?User $user, ?string $sessionToken): bool
    {
        $atributos = $this->atributosDueno($user, $sessionToken);

        return DB::transaction(function () use ($producto, $atributos) {
            $existente = Favorito::query()
                ->where($atributos)
                ->where('producto_id', $producto->id)
                ->first();

            if ($existente !== null) {
                $existente->delete();

                return false;
            }

            // firstOrCreate absorbe la carrera si dos requests llegan a la vez:
            // el índice único parcial lo rechaza en el segundo insert.
            Favorito::firstOrCreate($atributos + ['producto_id' => $producto->id]);

            return true;
        });
    }

    /**
     * Productos visibles que el dueño tiene como favoritos.
     *
     * @return Collection<int, Producto>
     */
    public function listar(?User $user, ?string $sessionToken): Collection
    {
        // Un visitante sin cookie no tiene dueño todavía: lista vacía, no error.
        if ($user === null && ($sessionToken === null || $sessionToken === '')) {
            return new Collection;
        }

        $atributos = $this->atributosDueno($user, $sessionToken);

        $ids = Favorito::query()
            ->where($atributos)
            ->pluck('producto_id');

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Producto::query()
            ->visible()
            ->whereIn('id', $ids)
            ->with(['marca', 'imagenesGaleria'])
            ->get();
    }

    /**
     * IDs de los productos favoritos del dueño, para pintar el estado inicial
     * de los corazones sin una consulta por tarjeta.
     *
     * @return array<int, int>
     */
    public function ids(?User $user, ?string $sessionToken): array
    {
        if ($user === null && ($sessionToken === null || $sessionToken === '')) {
            return [];
        }

        return Favorito::query()
            ->where($this->atributosDueno($user, $sessionToken))
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Pasa los favoritos anónimos de la sesión al usuario que acaba de loguearse.
     *
     * @return int cantidad de favoritos incorporados al usuario.
     */
    public function fusionar(string $sessionToken, User $user): int
    {
        return DB::transaction(function () use ($sessionToken, $user) {
            $deSesion = Favorito::query()
                ->where('session_token', $sessionToken)
                ->get();

            $incorporados = 0;

            foreach ($deSesion as $favorito) {
                $creado = Favorito::firstOrCreate([
                    'user_id' => $user->id,
                    'producto_id' => $favorito->producto_id,
                ]);

                if ($creado->wasRecentlyCreated) {
                    $incorporados++;
                }

                $favorito->delete();
            }

            return $incorporados;
        });
    }

    /**
     * @return array<string, int|string>
     */
    private function atributosDueno(?User $user, ?string $sessionToken): array
    {
        if ($user !== null) {
            return ['user_id' => $user->id];
        }

        if ($sessionToken !== null && $sessionToken !== '') {
            return ['session_token' => $sessionToken];
        }

        throw new InvalidArgumentException(
            'Un favorito necesita un usuario autenticado o un session_token.'
        );
    }
}

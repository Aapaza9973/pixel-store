<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\FavoritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 2.7.3-2.7.4: favoritos de la ficha y del listado de favoritos.
 *
 * Un usuario autenticado se identifica por su id; un visitante, por la cookie
 * `favoritos_token` (64 caracteres como máximo en la base).
 */
class FavoritoController extends Controller
{
    private const COOKIE = 'favoritos_token';

    private const COOKIE_MINUTOS = 60 * 24 * 365;

    public function __construct(
        private readonly FavoritoService $favoritoService
    ) {}

    public function toggle(Request $request, Producto $producto): JsonResponse
    {
        $token = $this->tokenDeVisita($request);

        $esFavorito = $this->favoritoService->toggle($producto, $request->user(), $token);

        return response()->json(['favorito' => $esFavorito])
            ->withCookie(Cookie::make(self::COOKIE, $token, self::COOKIE_MINUTOS));
    }

    public function index(Request $request): View
    {
        $productos = $this->favoritoService->listar($request->user(), $request->cookie(self::COOKIE));
        $favoritosIds = $productos->pluck('id')->map(fn ($id) => (int) $id)->all();

        return view('catalogo.favoritos', compact('productos', 'favoritosIds'));
    }

    /**
     * Token de visitante: reusa el de la cookie o genera uno nuevo.
     * Para usuarios autenticados devuelve el existente solo para no pisar la cookie.
     */
    private function tokenDeVisita(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        return Str::random(40);
    }
}

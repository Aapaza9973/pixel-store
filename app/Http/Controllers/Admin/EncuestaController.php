<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Resultados anónimos de la encuesta (HU-3.5). Permiso `ver encuestas`.
 */
class EncuestaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('ver encuestas'), 403);

        $promedios = Encuesta::query()
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(atencion) as atencion'),
                DB::raw('AVG(catalogo) as catalogo'),
                DB::raw('AVG(recomendacion) as recomendacion'),
            ])
            ->first();

        $encuestas = Encuesta::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.encuestas.index', compact('promedios', 'encuestas'));
    }
}

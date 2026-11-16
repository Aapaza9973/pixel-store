<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Encuesta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Encuesta anónima de satisfacción (HU-3.5).
 *
 * No se guarda IP, usuario ni cookie: solo las respuestas y un comentario.
 */
class EncuestaController extends Controller
{
    public function create(): View
    {
        return view('publico.encuesta');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'atencion' => ['required', 'integer', 'between:1,5'],
            'catalogo' => ['required', 'integer', 'between:1,5'],
            'recomendacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ], [
            'atencion.required' => 'Califica la atención recibida.',
            'catalogo.required' => 'Califica el catálogo.',
            'recomendacion.required' => 'Indica cuánto nos recomendarías.',
            'atencion.between' => 'La calificación debe estar entre 1 y 5.',
            'catalogo.between' => 'La calificación debe estar entre 1 y 5.',
            'recomendacion.between' => 'La calificación debe estar entre 1 y 5.',
        ]);

        Encuesta::create($datos);

        return redirect()
            ->route('encuesta.gracias')
            ->with('success', 'Gracias por respondernos.');
    }

    public function gracias(): View
    {
        return view('publico.encuesta-gracias');
    }
}

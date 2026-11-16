@extends('layouts.app')

@section('title', 'Encuestas')
@section('subtitle', 'Resultados anónimos de satisfacción')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Respuestas</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">{{ (int) ($promedios->total ?? 0) }}</p>
        </div>

        @foreach ([
            'Atención' => $promedios->atencion ?? null,
            'Catálogo' => $promedios->catalogo ?? null,
            'Recomendación' => $promedios->recomendacion ?? null,
        ] as $etiqueta => $valor)
            <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">{{ $etiqueta }}</p>
                <p class="mt-2 font-headline-md text-headline-md tabular-nums text-secondary">
                    {{ $valor !== null ? number_format((float) $valor, 1, '.', ',') : '—' }}
                    <span class="font-body-sm text-body-sm text-outline">/ 5</span>
                </p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Atención</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Catálogo</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Recomendación</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Promedio</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($encuestas as $encuesta)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $encuesta->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-on-surface">{{ $encuesta->atencion }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-on-surface">{{ $encuesta->catalogo }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-on-surface">{{ $encuesta->recomendacion }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-secondary">{{ number_format($encuesta->promedio(), 1, '.', ',') }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ $encuesta->comentario ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">Todavía no hay respuestas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($encuestas->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $encuestas->links() }}</div>
        @endif
    </div>
</div>
@endsection

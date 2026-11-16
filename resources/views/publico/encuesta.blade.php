@extends('layouts.app')

@section('title', 'Encuesta de satisfacción')
@section('subtitle', 'Tu opinión nos ayuda a mejorar')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('encuesta.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Son tres preguntas y es totalmente anónima: no guardamos tu nombre, correo ni IP.
            </p>

            <div class="mt-5 space-y-5">
                @foreach ([
                    'atencion' => '¿Cómo calificás la atención recibida?',
                    'catalogo' => '¿Qué tan claro y completo te pareció el catálogo?',
                    'recomendacion' => '¿Cuánto nos recomendarías a un amigo?',
                ] as $campo => $pregunta)
                    <div>
                        <label for="{{ $campo }}" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                            {{ $pregunta }} *
                        </label>
                        <select name="{{ $campo }}" id="{{ $campo }}" required
                                class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                            <option value="">Elegí una opción</option>
                            @for ($nota = 5; $nota >= 1; $nota--)
                                <option value="{{ $nota }}" {{ (string) old($campo) === (string) $nota ? 'selected' : '' }}>
                                    {{ $nota }} — {{ $nota === 5 ? 'Excelente' : ($nota === 1 ? 'Muy malo' : '') }}
                                </option>
                            @endfor
                        </select>
                        @error($campo)
                            <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div>
                    <label for="comentario" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Comentario (opcional)</label>
                    <textarea name="comentario" id="comentario" rows="4"
                              class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">{{ old('comentario') }}</textarea>
                    @error('comentario')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <button type="submit"
                class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
            Enviar respuesta
        </button>
    </form>
</div>
@endsection

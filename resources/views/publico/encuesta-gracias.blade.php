@extends('layouts.app')

@section('title', 'Gracias')
@section('subtitle', 'Encuesta enviada')

@section('content')
<div class="mx-auto max-w-xl space-y-5 text-center">
    <div class="rounded-obsidian-xl border border-emerald-500/25 bg-emerald-500/10 p-6">
        <h1 class="font-headline-md text-headline-md text-on-surface">Gracias por tu tiempo</h1>
        <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
            Registramos tu respuesta de forma anónima.
        </p>
    </div>

    <a href="{{ route('catalogo.index') }}"
       class="inline-flex items-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
        Volver al catálogo
    </a>
</div>
@endsection

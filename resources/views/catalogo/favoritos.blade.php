@extends('layouts.app')

@section('title', 'Mis favoritos')
@section('subtitle', 'Catálogo público')

@section('content')
<div class="mx-auto max-w-5xl space-y-8">

    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ url('/') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <span class="text-on-surface">Favoritos</span>
    </nav>

    <div class="flex items-end justify-between gap-4 border-b border-outline-variant/30 pb-4">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Mis favoritos</h1>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                Productos que guardaste para volver a verlos.
            </p>
        </div>

        <span class="font-label-md text-label-md tabular-nums text-outline">
            {{ $productos->count() }} {{ $productos->count() === 1 ? 'producto' : 'productos' }}
        </span>
    </div>

    @if ($productos->isEmpty())
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-10 text-center backdrop-blur-xl">
            <p class="font-body-md text-body-md text-on-surface-variant">
                Todavía no guardaste favoritos.
            </p>
            <p class="mt-1 font-body-sm text-body-sm text-outline">
                Tocá el corazón en cualquier producto del catálogo para guardarlo acá.
            </p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($productos as $producto)
                @include('catalogo._card-producto', ['producto' => $producto])
            @endforeach
        </div>
    @endif
</div>
@endsection

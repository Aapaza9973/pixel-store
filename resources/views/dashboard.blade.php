
@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Panel de control · ' . now()->translatedFormat('l, d \\d\\e F \\d\\e Y'))

@section('content')
<div class="space-y-6">

    {{-- Bienvenida --}}
    <div class="bg-gradient-to-r from-blue-600/10 via-blue-500/5 to-transparent border border-blue-500/20 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">
                    ¡Bienvenido, {{ auth()->user()->name }}!
                </h2>
                <p class="text-slate-400 mt-1">
                    Has iniciado sesión como <span class="text-blue-400 font-medium">{{ auth()->user()->getRoleNames()->first() }}</span>.
                </p>
            </div>
            <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                 alt="Pixel Store"
                 class="w-16 h-16 rounded opacity-80">
        </div>
    </div>

    {{-- Métricas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Productos</p>
                    <p class="text-2xl font-bold text-white mt-1">0</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Categorías</p>
                    <p class="text-2xl font-bold text-white mt-1">0</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Stock bajo</p>
                    <p class="text-2xl font-bold text-amber-400 mt-1">0</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Ventas hoy</p>
                    <p class="text-2xl font-bold text-white mt-1">Bs 0</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Accesos rápidos --}}
    <x-card title="Accesos rápidos" subtitle="Operaciones frecuentes del sistema">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @can('ver categorias')
                <a href="{{ route('admin.categorias.index') }}"
                   class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition">
                    <div class="w-9 h-9 rounded bg-blue-500/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-300">Categorías</span>
                </a>
            @endcan

            @can('crear categorias')
                <a href="{{ route('admin.categorias.create') }}"
                   class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition">
                    <div class="w-9 h-9 rounded bg-emerald-500/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-300">Nueva categoría</span>
                </a>
            @endcan
        </div>
    </x-card>

</div>
@endsection

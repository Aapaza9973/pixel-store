@props(['type' => 'info', 'message' => ''])

@php
$colors = [
    'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
    'error'   => 'bg-error/10 border-error/30 text-error',
    'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
    'info'    => 'bg-secondary/10 border-secondary/30 text-secondary',
];
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     x-transition
     class="mx-6 mt-4 rounded-obsidian-lg border px-4 py-3 {{ $colors[$type] }}">
    <div class="flex items-center justify-between">
        <p class="text-body-sm font-medium">{{ $message }}</p>
        <button @click="show = false" class="text-current opacity-60 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

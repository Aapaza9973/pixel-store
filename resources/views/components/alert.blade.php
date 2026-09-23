@props(['type' => 'info', 'message' => ''])

@php
$colors = [
    'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
    'error'   => 'bg-red-500/10 border-red-500/30 text-red-400',
    'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
    'info'    => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
];
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     x-transition
     class="mx-6 mt-4 border rounded-lg px-4 py-3 {{ $colors[$type] }}">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium">{{ $message }}</p>
        <button @click="show = false" class="text-current opacity-60 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

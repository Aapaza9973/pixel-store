@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'bg-slate-900 border border-slate-800 rounded-xl overflow-hidden']) }}>
    @if ($title)
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-white">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</div>

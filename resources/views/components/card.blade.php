@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl']) }}>
    @if ($title)
        <div class="flex items-center justify-between border-b border-outline-variant/30 px-5 py-4">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="mt-0.5 font-body-sm text-body-sm text-outline">{{ $subtitle }}</p>
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

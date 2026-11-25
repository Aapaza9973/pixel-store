@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline']) }}>
    {{ $value ?? $slot }}
</label>

@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-on-surface placeholder:text-outline shadow-sm transition focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 disabled:cursor-not-allowed disabled:opacity-60']) }}>

<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-outline hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60 focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:cursor-not-allowed disabled:opacity-40']) }}>
    {{ $slot }}
</button>

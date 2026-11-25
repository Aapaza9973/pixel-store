<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-obsidian-lg border border-error/40 bg-error/10 px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-error transition hover:bg-error/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-error/60 focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:cursor-not-allowed disabled:opacity-40']) }}>
    {{ $slot }}
</button>

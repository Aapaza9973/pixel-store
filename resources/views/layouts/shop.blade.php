<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tienda de computadoras')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Tema "Obsidian Cyber Grid" */
        :root { --obs:#090b0f; --panel:#11151c; --line:#1e2733; --cyan:#22d3ee; --text:#e6edf3; --muted:#8b98a9; }
        body { background-color: var(--obs); color: var(--text); font-family: Inter, system-ui, sans-serif;
               background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px);
               background-size: 48px 48px; background-attachment: fixed; }
        .font-display { font-family: 'Chakra Petch', sans-serif; }
        .panel { background: var(--panel); border: 1px solid var(--line); }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.55rem 1rem; font-weight:600;
               background: var(--cyan); color:#021014; border-radius:.25rem; transition: filter .15s; }
        .btn:hover { filter: brightness(1.12); }
        .btn:disabled { opacity:.4; cursor:not-allowed; }
        .btn-ghost { background:transparent; color:var(--text); border:1px solid var(--line); }
        .btn-ghost:hover { border-color: var(--cyan); }
        .input { width:100%; background:#0b0f15; border:1px solid var(--line); border-radius:.25rem; padding:.5rem .7rem; color:var(--text); }
        .input:focus, .btn:focus-visible, a:focus-visible { outline:2px solid var(--cyan); outline-offset:2px; }
        .muted { color: var(--muted); }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-20 border-b" style="background:rgba(9,11,15,.92);border-color:var(--line);backdrop-filter:blur(6px)">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
        <a href="{{ route('shop.index') }}" class="font-display text-xl font-bold tracking-wide">Tu<span style="color:var(--cyan)">Tienda</span> PC</a>
        <form action="{{ route('shop.index') }}" method="GET" class="hidden flex-1 md:block md:max-w-md">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar procesador, placa, RAM…" class="input" aria-label="Buscar productos">
        </form>
        <nav class="flex items-center gap-4 text-sm">
            <a href="{{ route('shop.index') }}" class="hover:underline">Catálogo</a>
            <a href="{{ route('cart.index') }}" class="btn btn-ghost">Carrito <span class="font-display" style="color:var(--cyan)">{{ $cartCount ?? 0 }}</span></a>
        </nav>
    </div>
</header>

<main class="mx-auto max-w-7xl px-4 py-8">
    @if (session('ok'))
        <div role="status" class="panel mb-6 border-l-4 px-4 py-3" style="border-left-color:var(--cyan)">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="panel mb-6 border-l-4 px-4 py-3" style="border-left-color:#f87171">{{ session('error') }}</div>
    @endif
    @yield('content')
</main>

<footer class="border-t py-6 text-center text-sm muted" style="border-color:var(--line)">© {{ date('Y') }} Tu Tienda PC</footer>
</body>
</html>

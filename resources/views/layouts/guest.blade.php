<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Pixel Store') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|geist:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-body-md text-on-surface antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-10">
        <a href="{{ route('login') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                 alt="Pixel Store"
                 class="h-10 w-10 rounded">
            <span class="font-headline-sm text-headline-sm tracking-tight text-on-surface">PIXEL STORE</span>
        </a>

        <div class="w-full max-w-md rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl sm:p-8">
            {{ $slot }}
        </div>

        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
            &copy; {{ date('Y') }} Pixel Store · La Paz, Bolivia
        </p>
    </div>
</body>
</html>

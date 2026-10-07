<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Pixel Store') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-200 antialiased bg-slate-950">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden rounded-xl">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-slate-600">
            &copy; {{ date('Y') }} Pixel Store · La Paz, Bolivia
        </p>
    </div>
</body>
</html>


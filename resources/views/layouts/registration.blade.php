<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Tournament Registration') — KONSONTHEGO</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=archivo-black:400|manrope:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ktg-black text-white antialiased">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -left-24 top-0 h-72 w-72 rounded-full bg-ktg-green/20 blur-3xl"></div>
            <div class="absolute -right-16 top-40 h-80 w-80 rounded-full bg-ktg-lime/15 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-ktg-green/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-6 flex items-center justify-center sm:mb-8">
                <img
                    src="{{ asset('images/konsonthego-logo.png') }}"
                    alt="KONSONTHEGO"
                    class="h-34 w-auto drop-shadow-lg sm:h-36 lg:h-44"
                >
            </header>

            @isset($step)
                @include('registration.partials.progress')
            @endisset

            @if (session('warning'))
                <div class="mb-4 rounded-2xl border border-ktg-lime/40 bg-ktg-lime/10 px-4 py-3 text-sm text-ktg-lime">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <main class="flex-1">
                @yield('content')
            </main>

            <footer class="mt-10 pb-4 text-center text-xs text-white/40">
                KONSONTHEGO Tournament Registration - AOG Creatives
            </footer>
        </div>
    </body>
</html>

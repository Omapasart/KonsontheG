<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Login — KONSONTHEGO</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=archivo-black:400|manrope:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ktg-black text-white antialiased">
        <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4">
            <img src="{{ asset('images/konsonthego-logo.png') }}" alt="KONSONTHEGO" class="mx-auto mb-8 h-20 w-auto">
            <h1 class="text-center font-display text-3xl uppercase italic text-ktg-lime">Admin Login</h1>
            <p class="mt-2 text-center text-sm text-white/50">Authorized tournament administrators only</p>

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6">
                @csrf
                @if ($errors->any())
                    <div class="rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="field-input">
                </div>
                <div>
                    <label for="password" class="field-label">Password</label>
                    <input id="password" type="password" name="password" required class="field-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-white/70">
                    <input type="checkbox" name="remember" class="rounded">
                    Remember me
                </label>
                <button type="submit" class="btn-primary w-full">Sign In</button>
            </form>
        </div>
    </body>
</html>

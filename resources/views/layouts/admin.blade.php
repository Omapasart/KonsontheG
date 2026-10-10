<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Admin') — KONSONTHEGO</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=archivo-black:400|manrope:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    </head>
    <body class="min-h-screen bg-ktg-black text-white antialiased">
        <div class="lg:flex">
            <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/70 lg:hidden"></div>

            <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-[#101010] pointer-events-none transition-transform lg:static lg:translate-x-0 lg:pointer-events-auto">
                <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
                    <img src="{{ asset('images/konsonthego-logo.png') }}" alt="KONSONTHEGO" class="h-10 w-10 shrink-0 object-contain">
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-sm uppercase italic leading-none text-ktg-lime">Admin</p>
                        <p class="mt-1 text-xs leading-snug text-white/50">Tournament Manager</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.applicants.index') }}" class="nav-link {{ request()->routeIs('admin.applicants.*') && ! request()->filled('registration_status') && ! request()->filled('payment_status') && ! request()->filled('slot_status') && request('status') !== 'withdrawn' ? 'nav-link-active' : '' }}">Applicants</a>
                    <a href="{{ route('admin.applicants.index', ['registration_status' => 'pending']) }}" class="nav-link {{ request('registration_status') === 'pending' ? 'nav-link-active' : '' }}">Pending Review</a>
                    <a href="{{ route('admin.applicants.index', ['payment_status' => 'pending']) }}" class="nav-link {{ request('payment_status') === 'pending' && request('registration_status') === null ? 'nav-link-active' : '' }}">Payment Verification</a>
                    <a href="{{ route('admin.applicants.index', ['registration_status' => 'approved']) }}" class="nav-link {{ request('registration_status') === 'approved' ? 'nav-link-active' : '' }}">Approved</a>
                    <a href="{{ route('admin.applicants.index', ['registration_status' => 'rejected']) }}" class="nav-link {{ request('registration_status') === 'rejected' ? 'nav-link-active' : '' }}">Rejected</a>
                    <a href="{{ route('admin.waiting-list') }}" class="nav-link {{ request()->routeIs('admin.waiting-list') ? 'nav-link-active' : '' }}">Waiting List</a>
                </nav>

                <div class="border-t border-white/10 px-3 py-4 text-sm">
                    <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'nav-link-active' : '' }}">Admin Profile</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="nav-link w-full text-left">Logout</button>
                    </form>
                </div>
            </aside>

            <div class="min-h-screen flex-1 lg:pl-0">
                <header class="sticky top-0 z-20 flex items-center justify-between border-b border-white/10 bg-ktg-black/90 px-4 py-3 backdrop-blur lg:px-8">
                    <button type="button" id="sidebar-toggle" class="rounded-lg border border-white/15 px-3 py-2 text-sm font-bold uppercase tracking-widest lg:hidden">Menu</button>
                    <h1 class="font-display text-lg uppercase italic text-ktg-lime sm:text-xl">@yield('heading', 'Dashboard')</h1>
                    <p class="hidden text-sm text-white/50 sm:block">{{ auth()->user()->name }}</p>
                </header>

                <div class="px-4 py-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-4 rounded-2xl border border-ktg-green/40 bg-ktg-green/10 px-4 py-3 text-sm text-green-200">{{ session('success') }}</div>
                    @endif
                    @if (session('warning'))
                        <div class="mb-4 rounded-2xl border border-ktg-lime/40 bg-ktg-lime/10 px-4 py-3 text-sm text-ktg-lime">{{ session('warning') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </div>
        </div>

        <div id="lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 p-4">
            <button type="button" id="lightbox-close" class="absolute right-4 top-4 rounded-full bg-white/10 px-4 py-2 text-sm font-bold uppercase tracking-widest">Close</button>
            <div class="flex max-h-full max-w-5xl flex-col items-center gap-4">
                <img id="lightbox-image" src="" alt="Preview" class="max-h-[80vh] max-w-full object-contain transition-transform">
                <div class="flex gap-3">
                    <button type="button" id="lightbox-zoom-out" class="rounded-full border border-white/20 px-4 py-2 text-sm">-</button>
                    <button type="button" id="lightbox-zoom-in" class="rounded-full border border-white/20 px-4 py-2 text-sm">+</button>
                    <a id="lightbox-download" href="#" class="rounded-full bg-ktg-lime px-4 py-2 text-sm font-bold uppercase text-ktg-black">Download</a>
                </div>
            </div>
        </div>
    </body>
</html>

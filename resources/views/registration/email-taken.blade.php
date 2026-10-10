@extends('layouts.registration')

@section('title', 'Email Already Registered')

@section('content')
    <section class="mx-auto max-w-2xl text-center">
        <div class="rounded-3xl border border-red-400/40 bg-red-500/10 px-6 py-10 shadow-2xl sm:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-red-200">Registration Unsuccessful</p>
            <h1 class="mt-4 font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Email Already Registered</h1>
            <p class="mt-6 text-base leading-relaxed text-white/80 sm:text-lg">
                This email address has already been used for a KONSONTHEGO tournament registration.
            </p>
            <p class="mt-4 text-base leading-relaxed text-white/80 sm:text-lg">
                Only one registration is allowed per email address.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register.personal') }}" class="btn-primary">Use Another Email</a>
                <form method="POST" action="{{ route('register.start-again') }}">
                    @csrf
                    <button type="submit" class="btn-ghost">Return to Welcome</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.registration')

@section('title', 'Registration Confirmation')

@section('content')
    <section class="mx-auto max-w-2xl text-center">
        <div class="rounded-3xl border border-ktg-lime/30 bg-white/5 px-6 py-10 shadow-2xl sm:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-ktg-lime">You are registered</p>
            <h1 class="mt-4 font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Thank You for Your Interest in KONSONTHEGO!</h1>
            <p class="mt-6 text-base leading-relaxed text-white/75 sm:text-lg">
                Thank you for your interest in the KONSONTHEGO Tournament. Your registration has been successfully submitted.
            </p>
            <p class="mt-4 text-base leading-relaxed text-white/75 sm:text-lg">
                A registration confirmation email has been sent to <strong class="text-ktg-lime">{{ $completed['email'] }}</strong>.
            </p>
            <p class="mt-4 text-base leading-relaxed text-white/75 sm:text-lg">
                Your GCash payment proof will be reviewed within <strong class="text-ktg-lime">{{ $hours }} hours</strong>.
            </p>
            <p class="mt-4 text-sm text-white/60">
                Please make sure to check your inbox and spam/junk folder.
            </p>

            <div class="mt-8 rounded-2xl bg-ktg-black/60 px-4 py-5 ring-1 ring-ktg-lime/40">
                <p class="text-xs uppercase tracking-widest text-white/50">Registration No.</p>
                <p class="mt-1 font-display text-2xl text-ktg-lime sm:text-3xl">{{ $completed['number'] }}</p>
                <p class="mt-2 text-sm text-white/55">{{ $completed['name'] }}</p>
            </div>
        </div>
    </section>
@endsection

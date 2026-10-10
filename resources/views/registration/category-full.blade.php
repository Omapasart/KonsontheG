@extends('layouts.registration')

@section('title', $status['level']->label().' Category Full')

@section('content')
    <section class="mx-auto max-w-2xl text-center">
        <div class="rounded-3xl border border-red-400/40 bg-red-500/10 px-6 py-10 shadow-2xl sm:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-red-200">Category Full</p>
            <h1 class="mt-4 font-display text-3xl uppercase italic tracking-tight sm:text-5xl">{{ strtoupper($status['level']->label()) }} CATEGORY IS FULL</h1>
            <p class="mt-6 text-base leading-relaxed text-white/80 sm:text-lg">
                The {{ $status['capacity'] }} tournament slots and {{ $status['waiting_capacity'] }} waiting-list slots for {{ $status['level']->label() }} have already been filled.
            </p>
            <div class="mt-8">
                <a href="{{ route('register.level') }}" class="btn-primary">Choose Another Category</a>
            </div>
        </div>
    </section>
@endsection

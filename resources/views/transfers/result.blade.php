@extends('layouts.registration')

@section('title', $title)

@section('content')
    <section class="mx-auto max-w-2xl text-center">
        <div class="rounded-3xl border border-ktg-lime/30 bg-white/5 px-6 py-10 shadow-2xl sm:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-ktg-lime">Category Transfer Request</p>
            <h1 class="mt-4 font-display text-3xl uppercase italic tracking-tight sm:text-4xl">{{ $title }}</h1>
            <p class="mt-6 text-base leading-relaxed text-white/75">{{ $message }}</p>
            @isset($registration)
                <p class="mt-4 text-sm uppercase tracking-widest text-white/60">
                    Category: {{ $registration->entry_level->label() }}
                    · Slot: {{ $registration->slotLabel() }}
                </p>
            @endisset
        </div>
    </section>
@endsection

@extends('layouts.registration')

@section('title', 'Tournament Experience')

@section('content')
    <section class="mx-auto max-w-2xl">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Have you participated in a tournament before?</h1>
        <p class="mt-3 text-center text-white/60">Select one option to continue.</p>

        <form method="POST" action="{{ route('register.experience.store') }}" class="mt-8 space-y-6" data-choice-form>
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $label)
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="has_tournament_experience"
                            value="{{ $value }}"
                            class="sr-only"
                            @required($loop->first)
                            {{ old('has_tournament_experience', $wizard['has_tournament_experience'] ?? '') === $value ? 'checked' : '' }}
                        >
                        <span class="choice-card-inner">
                            <span class="font-display text-4xl uppercase italic">{{ $label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('register.welcome') }}" class="btn-ghost">Back</a>
                <button type="submit" class="btn-primary">Continue</button>
            </div>
        </form>
    </section>
@endsection

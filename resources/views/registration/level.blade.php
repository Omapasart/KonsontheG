@extends('layouts.registration')

@section('title', 'Entry Level')

@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Select Your Entry Level</h1>
        <p class="mt-3 text-center text-white/60">Choose the division that best matches your play.</p>

        <form method="POST" action="{{ route('register.level.store') }}" class="mt-8 space-y-6" data-choice-form>
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach (['beginner' => 'Beginner', 'novice' => 'Novice', 'intermediate' => 'Intermediate'] as $value => $label)
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="entry_level"
                            value="{{ $value }}"
                            class="sr-only"
                            @required($loop->first)
                            {{ old('entry_level', $wizard['entry_level'] ?? '') === $value ? 'checked' : '' }}
                        >
                        <span class="choice-card-inner min-h-32">
                            <span class="font-display text-2xl uppercase italic sm:text-3xl">{{ $label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('register.experience') }}" class="btn-ghost">Back</a>
                <button type="submit" class="btn-primary">Continue</button>
            </div>
        </form>
    </section>
@endsection

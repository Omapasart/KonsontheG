@extends('layouts.registration')

@section('title', 'Entry Level')

@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Select Your Entry Level</h1>
        <p class="mt-3 text-center text-white/60">Choose the division that best matches your play.</p>

        @if ($registrationClosed)
            <div class="mt-8 rounded-3xl border border-red-400/40 bg-red-500/10 px-6 py-8 text-center shadow-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-red-200">Registration Closed</p>
                <p class="mt-4 text-base leading-relaxed text-white/85 sm:text-lg">{{ $closedMessage }}</p>
            </div>
            <div class="mt-8 flex items-center justify-between gap-3">
                <a href="{{ route('register.experience') }}" class="btn-ghost">Back</a>
                <button type="button" class="btn-primary cursor-not-allowed opacity-40" disabled>Continue</button>
            </div>
        @else
            <form method="POST" action="{{ route('register.level.store') }}" class="mt-8 space-y-6" data-choice-form data-level-form>
                @csrf
                @error('entry_level')
                    <p class="rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">{{ $message }}</p>
                @enderror
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @php
                        $selected = old('entry_level', $wizard['entry_level'] ?? '');
                        $requiredAssigned = false;
                    @endphp
                    @foreach (['beginner' => 'Beginner', 'novice' => 'Novice', 'intermediate' => 'Intermediate'] as $value => $label)
                        @php $category = $categories[$value]; @endphp
                        <label @class(['choice-card', 'choice-card-disabled' => $category['is_full']])>
                            <input
                                type="radio"
                                name="entry_level"
                                value="{{ $value }}"
                                class="sr-only"
                                data-capacity="{{ $category['availability'] }}"
                                @if ($category['is_full'])
                                    disabled
                                @else
                                    @required(! $requiredAssigned)
                                    {{ $selected === $value ? 'checked' : '' }}
                                @endif
                            >
                            @php if (! $category['is_full'] && ! $requiredAssigned) { $requiredAssigned = true; } @endphp
                            <span class="choice-card-inner min-h-40 flex-col gap-3 px-4 @container">
                                <span @class([
                                    'font-display uppercase italic',
                                    'whitespace-nowrap text-2xl sm:text-[clamp(1rem,14cqi,1.625rem)]' => $value === 'intermediate',
                                    'text-2xl sm:text-2xl' => $value !== 'intermediate',
                                ])>{{ $label }}</span>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-white/70">
                                    {{ $category['regular_remaining'] }} regular slots remaining
                                </span>
                                <span class="text-[11px] uppercase tracking-widest text-white/55">
                                    {{ $category['waiting_remaining'] }} waiting-list slots remaining
                                </span>
                                @if ($category['is_full'])
                                    <span class="text-xs font-extrabold uppercase tracking-widest text-red-300">FULL — REGISTRATION CLOSED</span>
                                @elseif ($category['is_waiting'])
                                    <span class="text-xs font-extrabold uppercase tracking-widest text-amber-300">WAITING LIST ONLY</span>
                                @else
                                    <span class="text-xs font-extrabold uppercase tracking-widest text-ktg-lime">OPEN</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                <div id="capacity-notice" class="hidden rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-4 text-sm leading-relaxed text-amber-100">
                    <p class="font-extrabold uppercase tracking-widest">Waiting List</p>
                    <p class="mt-2" data-capacity-waiting>Regular slots for this category are currently full. You can still continue. After admin verification you will be placed on the waiting list if a position remains.</p>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('register.experience') }}" class="btn-ghost">Back</a>
                    <button type="submit" class="btn-primary">Continue</button>
                </div>
            </form>
        @endif
    </section>
@endsection

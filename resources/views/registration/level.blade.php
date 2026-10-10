@extends('layouts.registration')

@section('title', 'Entry Level')

@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Select Your Entry Level</h1>
        <p class="mt-3 text-center text-white/60">Choose the division that best matches your play.</p>

        <form method="POST" action="{{ route('register.level.store') }}" class="mt-8 space-y-6" data-choice-form data-level-form>
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @php $requiredAssigned = false; @endphp
                @foreach (['beginner' => 'Beginner', 'novice' => 'Novice', 'intermediate' => 'Intermediate'] as $value => $label)
                    @php $category = $categories[$value]; @endphp
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="entry_level"
                            value="{{ $value }}"
                            class="sr-only"
                            data-capacity="{{ $category['is_full'] ? 'full' : ($category['is_waiting'] ? 'waiting' : 'open') }}"
                            @required(! $requiredAssigned)
                            {{ old('entry_level', $wizard['entry_level'] ?? '') === $value ? 'checked' : '' }}
                        >
                        @php if (! $requiredAssigned) { $requiredAssigned = true; } @endphp
                        <span class="choice-card-inner min-h-40 flex-col gap-3 px-4 @container">
                            <span @class([
                                'font-display uppercase italic',
                                'whitespace-nowrap text-2xl sm:text-[clamp(1rem,14cqi,1.625rem)]' => $value === 'intermediate',
                                'text-2xl sm:text-2xl' => $value !== 'intermediate',
                            ])>{{ $label }}</span>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-white/70">
                                {{ $category['confirmed'] }} / {{ $category['capacity'] }} tournament slots
                            </span>
                            @if ($category['is_full'])
                                <span class="text-xs font-extrabold uppercase tracking-widest text-red-300">Full</span>
                                <span class="text-[11px] uppercase tracking-widest text-white/50">Waiting {{ $category['waiting'] }} / {{ $category['waiting_capacity'] }}</span>
                            @elseif ($category['is_waiting'])
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-300">Regular slots full</span>
                                <span class="text-[11px] uppercase tracking-widest text-amber-200">Waiting list {{ $category['waiting'] }} / {{ $category['waiting_capacity'] }}</span>
                            @else
                                <span class="text-[11px] uppercase tracking-widest text-white/55">{{ $category['regular_remaining'] }} regular slots remaining</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

            <div id="capacity-notice" class="hidden rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-4 text-sm leading-relaxed text-amber-100">
                <p class="font-extrabold uppercase tracking-widest">Pending Verification</p>
                <p class="mt-2" data-capacity-waiting>Regular slots for this category are currently full. After admin verification you may be placed on the waiting list if a position is available.</p>
                <p class="mt-2 hidden" data-capacity-full>This category currently has no remaining regular or waiting-list slots. You may still apply. Your application will stay pending until an administrator verifies it, and a slot will be assigned only if one becomes available.</p>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('register.experience') }}" class="btn-ghost">Back</a>
                <button type="submit" class="btn-primary">Continue</button>
            </div>
        </form>
    </section>
@endsection

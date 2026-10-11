@extends('layouts.admin')

@section('title', 'Category Registration')
@section('heading', 'Category Registration')

@section('content')
    <p class="mb-6 max-w-3xl text-sm text-white/70">Open / Close Categories. Existing applicants, slots, and payment records stay as they are.</p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($categories as $category)
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-white/45">{{ $category['level']->label() }}</p>
                <p @class([
                    'mt-3 font-display text-2xl uppercase italic',
                    'text-red-300' => $category['closed'] || $category['is_full'],
                    'text-amber-300' => $category['is_waiting'],
                    'text-ktg-lime' => $category['is_open'],
                ])>{{ $category['closed'] ? 'CLOSED' : $category['availability_label'] }}</p>
                <div class="mt-3 space-y-1 text-xs uppercase tracking-widest text-white/60">
                    <p>{{ $category['confirmed'] }} / {{ $category['capacity'] }} confirmed</p>
                    <p class="text-amber-200">{{ $category['waiting'] }} / {{ $category['waiting_capacity'] }} waiting</p>
                    <p>{{ $category['regular_remaining'] }} regular remaining</p>
                    <p>{{ $category['waiting_remaining'] }} waiting remaining</p>
                </div>
                <form method="POST" action="{{ route('admin.categories.limits', $category['level']->value) }}" class="mt-5 space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="field-label" for="confirmed_capacity_{{ $category['level']->value }}">Confirmed max</label>
                        <input id="confirmed_capacity_{{ $category['level']->value }}" type="number" min="0" max="500" name="confirmed_capacity" value="{{ old('confirmed_capacity', $category['capacity']) }}" class="field-input" required>
                    </div>
                    <div>
                        <label class="field-label" for="waiting_capacity_{{ $category['level']->value }}">Waiting list max</label>
                        <input id="waiting_capacity_{{ $category['level']->value }}" type="number" min="0" max="500" name="waiting_capacity" value="{{ old('waiting_capacity', $category['waiting_capacity']) }}" class="field-input" required>
                    </div>
                    <button type="submit" class="btn-ghost w-full">Save {{ $category['level']->label() }} Limits</button>
                </form>
                <form method="POST" action="{{ route('admin.categories.update', $category['level']->value) }}" class="mt-3">
                    @csrf
                    @method('PUT')
                    @if ($category['closed'])
                        <input type="hidden" name="closed" value="0">
                        <button type="submit" class="btn-primary w-full">Open {{ $category['level']->label() }}</button>
                    @else
                        <input type="hidden" name="closed" value="1">
                        <button type="submit" class="w-full rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest" onclick="return confirm('Close {{ $category['level']->label() }} to new applicants? Existing applicants will not be changed.');">Close {{ $category['level']->label() }}</button>
                    @endif
                </form>
            </section>
        @endforeach
    </div>
@endsection

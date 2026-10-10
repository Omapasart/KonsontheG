@extends('layouts.registration')

@section('title', 'Category Transfer Request')

@section('content')
    <section class="mx-auto max-w-2xl">
        <div class="rounded-3xl border border-ktg-lime/30 bg-white/5 px-6 py-10 shadow-2xl sm:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-ktg-lime">Category Transfer Request</p>
            <h1 class="mt-4 font-display text-3xl uppercase italic tracking-tight sm:text-4xl">Review Proposed Category</h1>

            <dl class="mt-8 space-y-4 text-sm">
                <div>
                    <dt class="text-white/45">Current Category</dt>
                    <dd class="mt-1 font-semibold uppercase">{{ $transfer->current_category->label() }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">Proposed Category</dt>
                    <dd class="mt-1 font-semibold uppercase text-ktg-lime">{{ $transfer->requested_category->label() }}</dd>
                </div>
            </dl>

            @if ($transfer->status->value === 'pending')
                <p class="mt-8 text-base text-white/75">Do you agree to transfer to {{ strtoupper($transfer->requested_category->label()) }}?</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <form method="POST" action="{{ route('transfers.accept', $transfer) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $transfer->token }}">
                        <button type="submit" class="btn-primary w-full">Agree to Transfer</button>
                    </form>
                    <form method="POST" action="{{ route('transfers.decline', $transfer) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $transfer->token }}">
                        <button type="submit" class="btn-ghost w-full">Decline</button>
                    </form>
                </div>
                <p class="mt-4 text-xs text-white/45">Your category will remain {{ $transfer->current_category->label() }} unless you agree. If you decline, your registration will be rejected automatically.</p>
            @else
                <p class="mt-8 text-sm text-white/70">This request has already been {{ $transfer->status->label() }}.</p>
            @endif
        </div>
    </section>
@endsection

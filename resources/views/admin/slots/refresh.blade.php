@extends('layouts.admin')

@section('title', 'Refresh Confirmed Slots')
@section('heading', 'Refresh Confirmed Slots')

@section('content')
    @php
        $toDelete = max(0, $totalApplicants - $waitingApplicants);
    @endphp
    <section class="mx-auto max-w-3xl rounded-3xl border border-red-400/40 bg-white/5 p-5 sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-red-200">Confirm Refresh</p>
        <h2 class="mt-3 font-display text-2xl uppercase italic text-ktg-lime">Clear the current roster and move the waiting list to pending</h2>
        <p class="mt-4 text-sm leading-relaxed text-red-100">
            This permanently deletes {{ $toDelete }} applicant record{{ $toDelete === 1 ? '' : 's' }} that are not on the waiting list (confirmed, pending, rejected, and withdrawn), including their photos and payment proofs. Confirmed applicants are not emailed.
        </p>
        <p class="mt-3 text-sm leading-relaxed text-white/70">
            All {{ $waitingApplicants }} waiting-list applicant{{ $waitingApplicants === 1 ? '' : 's' }} will be kept and marked as <strong>pending slot verification</strong> and <strong>pending payment verification</strong>. They will not automatically receive a confirmed slot. Each of them will be emailed that they have been transferred to a regular slot. An administrator must still verify payment and the application later.
        </p>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ($categories as $category)
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-white/50">{{ $category['level']->label() }}</p>
                    <p class="mt-2 text-sm text-ktg-lime">{{ $category['confirmed'] }} / {{ $category['capacity'] }} confirmed</p>
                    <p class="text-sm text-amber-200">{{ $category['waiting'] }} / {{ $category['waiting_capacity'] }} waiting</p>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.confirmed-slots.refresh.store') }}" class="mt-8 space-y-5">
            @csrf
            <label class="flex items-start gap-3 text-sm text-white/80">
                <input type="checkbox" name="confirm" value="1" class="mt-1 h-4 w-4 accent-ktg-lime" required>
                <span>I understand non-waiting applicants will be deleted without email, and every waiting-list applicant will become pending for both slot and payment verification, without a secured slot. Only the waiting list will be emailed about the transfer to a regular slot.</span>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Confirm Refresh</button>
                <a href="{{ route('admin.dashboard') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
@endsection

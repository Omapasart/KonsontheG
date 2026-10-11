@extends('layouts.admin')

@section('title', 'Confirmation Emails')
@section('heading', 'Confirmation Emails')

@section('content')
    <p class="mb-6 max-w-3xl text-sm text-white/70">Manually send application, payment, and slot confirmation emails. Records and slots are not changed. Only eligible applicants are emailed.</p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-white/45">Application confirmation</p>
            <p class="mt-3 font-display text-3xl uppercase italic text-ktg-lime">{{ $applicationCount }}</p>
            <p class="mt-2 text-sm text-white/60">All registered applicants</p>
            <form method="POST" action="{{ route('admin.emails.store') }}" class="mt-5" onsubmit="return confirm('Send application confirmation to {{ $applicationCount }} applicant{{ $applicationCount === 1 ? '' : 's' }}?');">
                @csrf
                <input type="hidden" name="type" value="application">
                <button type="submit" class="btn-primary w-full" @disabled($applicationCount === 0)>Send to all</button>
            </form>
        </section>

        <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-white/45">Payment confirmation</p>
            <p class="mt-3 font-display text-3xl uppercase italic text-ktg-lime">{{ $paymentCount }}</p>
            <p class="mt-2 text-sm text-white/60">Applicants with verified payment</p>
            <form method="POST" action="{{ route('admin.emails.store') }}" class="mt-5" onsubmit="return confirm('Send payment confirmation to {{ $paymentCount }} verified applicant{{ $paymentCount === 1 ? '' : 's' }}?');">
                @csrf
                <input type="hidden" name="type" value="payment">
                <button type="submit" class="btn-primary w-full" @disabled($paymentCount === 0)>Send to all</button>
            </form>
        </section>

        <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-white/45">Slot confirmation</p>
            <p class="mt-3 font-display text-3xl uppercase italic text-ktg-lime">{{ $slotCount }}</p>
            <p class="mt-2 text-sm text-white/60">Confirmed slots and waiting list</p>
            <form method="POST" action="{{ route('admin.emails.store') }}" class="mt-5" onsubmit="return confirm('Send slot confirmation to {{ $slotCount }} applicant{{ $slotCount === 1 ? '' : 's' }}?');">
                @csrf
                <input type="hidden" name="type" value="slot">
                <button type="submit" class="btn-primary w-full" @disabled($slotCount === 0)>Send to all</button>
            </form>
        </section>
    </div>
@endsection

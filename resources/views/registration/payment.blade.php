@extends('layouts.registration')

@section('title', 'Payment')

@section('content')
    @php
        $experience = $wizard['has_tournament_experience'] ?? '';
        $level = $wizard['entry_level'] ?? '';
    @endphp
    <section class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Payment</h1>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 sm:p-8">
            <h2 class="font-display text-xl uppercase italic text-ktg-lime">GCash Payment Details</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-white/45">Payment Method</dt>
                    <dd class="font-semibold">{{ config('tournament.payment_method') }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">GCash Name</dt>
                    <dd class="font-semibold">{{ config('tournament.account_name') }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">GCash Number</dt>
                    <dd class="font-semibold tracking-wide">{{ config('tournament.gcash_number') }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">Amount</dt>
                    <dd class="font-semibold">{{ config('tournament.amount_label') }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-sm text-white/60">{{ config('tournament.payment_notes') }}</p>

            <div class="mt-6 flex flex-col items-center">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-ktg-lime">GCash QR Code</p>
                <img
                    src="{{ asset(config('tournament.qr_image')) }}"
                    alt="GCash payment QR code"
                    class="h-56 w-56 rounded-3xl bg-white p-3 shadow-xl sm:h-64 sm:w-64"
                >
            </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 sm:p-8">
            <h2 class="font-display text-xl uppercase italic text-ktg-lime">Review Your Registration</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Tournament experience</dt>
                    <dd class="font-semibold uppercase">{{ $experience }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Entry level</dt>
                    <dd class="font-semibold uppercase">{{ $level }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Name</dt>
                    <dd class="text-right font-semibold">
                        {{ $wizard['first_name'] ?? '' }}
                        {{ $wizard['middle_initial'] ?? '' }}
                        {{ $wizard['last_name'] ?? '' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Contact</dt>
                    <dd class="font-semibold">{{ $wizard['contact_number'] ?? '' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Address</dt>
                    <dd class="max-w-[60%] text-right">{{ $wizard['address'] ?? '' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                    <dt class="text-white/50">Email</dt>
                    <dd class="text-right font-semibold">{{ $wizard['email'] ?? '' }}</dd>
                </div>
                @if (! empty($wizard['facebook']))
                    <div class="flex justify-between gap-4 border-b border-white/10 pb-2">
                        <dt class="text-white/50">Facebook</dt>
                        <dd class="max-w-[60%] text-right">{{ $wizard['facebook'] }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-white/50">Photo</dt>
                    <dd>{{ $wizard['photo_name'] ?? 'Uploaded' }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-white/45">Use Back to edit any of the information above. GCash details are shown only on this step.</p>
        </div>

        <form method="POST" action="{{ route('register.submit') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-white/10 bg-white/5 p-5 sm:p-8" data-submit-form>
            @csrf
            <input type="hidden" name="submission_token" value="{{ $wizard['submission_token'] }}">

            <h2 class="font-display text-xl uppercase italic text-ktg-lime">Proof of Payment</h2>
            <p class="text-sm text-white/80"><strong>Please submit/upload your proof of payment.</strong></p>

            <div>
                <label for="payment_proof" class="field-label">Upload Proof of Payment *</label>
                <input
                    id="payment_proof"
                    name="payment_proof"
                    type="file"
                    accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                    class="field-file"
                    data-preview-target="proof-preview"
                    data-filename-target="proof-filename"
                    @if (empty($wizard['payment_proof_path'])) required @endif
                >
                <p class="mt-1 text-xs text-white/45">JPG, JPEG, PNG, or PDF. Maximum {{ number_format(config('tournament.proof_max_kb') / 1024, 1) }} MB.</p>
                <p id="proof-filename" class="mt-2 text-sm text-white/70">
                    @if (! empty($wizard['payment_proof_name']))
                        Selected: {{ $wizard['payment_proof_name'] }}
                    @endif
                </p>
                <img
                    id="proof-preview"
                    src="{{ ! empty($wizard['payment_proof_path']) && ! str_ends_with(strtolower($wizard['payment_proof_name'] ?? ''), '.pdf') ? route('register.preview.proof') : '' }}"
                    alt="Proof of payment preview"
                    class="mt-3 max-h-56 rounded-2xl object-contain ring-2 ring-ktg-lime/40 {{ empty($wizard['payment_proof_path']) || str_ends_with(strtolower($wizard['payment_proof_name'] ?? ''), '.pdf') ? 'hidden' : '' }}"
                >
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <a href="{{ route('register.personal') }}" class="btn-ghost">Back</a>
                <button type="submit" class="btn-primary" data-submit-button>
                    Submit Registration
                </button>
            </div>
        </form>
    </section>
@endsection

@extends('layouts.registration')

@section('title', 'Payment')

@section('content')
    @php
        $experience = $wizard['has_tournament_experience'] ?? '';
        $level = $wizard['entry_level'] ?? '';
    @endphp
    <section class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Payment</h1>

        @if (session('email_available'))
            <p class="rounded-2xl border border-ktg-green/40 bg-ktg-green/10 px-4 py-3 text-center text-sm text-green-200">Email address is available.</p>
        @endif

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
                <!-- <p class="mt-4 max-w-sm text-center text-sm text-white/70">Download the QR code and scan it using your GCash app to complete your payment.</p> -->
                <a href="{{ route('register.payment.qr') }}" class="btn-primary mt-4 gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v8.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0L7.72 11.28a.75.75 0 0 1 1.06-1.06l2.47 2.47V4.5a.75.75 0 0 1 .75-.75Zm-8.25 9.75a.75.75 0 0 1 .75.75v4.5c0 .414.336.75.75.75h12.75a.75.75 0 0 0 .75-.75v-4.5a.75.75 0 0 1 1.5 0v4.5A2.25 2.25 0 0 1 18 21.75H5.25A2.25 2.25 0 0 1 3 19.5v-4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                    </svg>
                    Download QR
                </a>
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
                <div class="flex justify-between gap-4">
                    <dt class="text-white/50">Photo</dt>
                    <dd>{{ $wizard['photo_name'] ?? 'Uploaded' }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-white/45">Use Back to edit any of the information above. GCash details are shown only on this step.</p>
        </div>

        <form method="POST" action="{{ route('register.submit') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-white/10 bg-white/5 p-5 sm:p-8" data-submit-form @if (! empty($wizard['payment_proof_path'])) data-has-proof="1" @endif>
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

            <div class="rounded-2xl border border-ktg-lime/30 bg-black/30 p-4 sm:p-5" data-privacy-consent>
                <h2 class="font-display text-xl uppercase italic text-ktg-lime">Data Privacy and Participant Consent</h2>
                <div class="mt-4">
                    @include('registration.partials.privacy-notice')
                </div>

                <label for="privacy_consent" class="mt-5 flex cursor-pointer items-start gap-3 text-sm font-semibold leading-relaxed text-white">
                    <input
                        id="privacy_consent"
                        name="privacy_consent"
                        type="checkbox"
                        value="1"
                        class="mt-1 h-4 w-4 shrink-0 accent-ktg-lime"
                        data-privacy-checkbox
                        required
                        @checked(old('privacy_consent'))
                    >
                    <span>I have read, understood, and agree to the Data Privacy and Participant Consent terms stated above.</span>
                </label>
                <p id="privacy-consent-error" class="mt-3 hidden text-sm text-red-200" data-privacy-error role="alert">
                    Please read, understand, and agree to the Data Privacy and Participant Consent terms stated above.
                </p>
                @error('privacy_consent')
                    <p class="mt-3 text-sm text-red-200">{{ $message }}</p>
                @enderror
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

@extends('layouts.admin')

@section('title', $registration->registration_number)
@section('heading', 'Applicant Details')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.applicants.index') }}" class="text-sm text-ktg-lime underline">← Back to applicants</a>
    </div>

    <section class="grid grid-cols-1 gap-6 rounded-3xl border border-white/10 bg-white/5 p-5 sm:grid-cols-[180px_1fr] lg:grid-cols-[220px_1fr] lg:p-8">
        <div>
            @if ($registration->photo_path)
                <button type="button" class="js-lightbox" aria-label="Enlarge applicant photo" data-src="{{ route('admin.applicants.photo', $registration) }}" data-download="{{ route('admin.applicants.photo.download', $registration) }}">
                    <img src="{{ route('admin.applicants.photo', $registration) }}" alt="Applicant photo" class="max-h-80 w-full rounded-3xl object-contain ring-2 ring-ktg-lime/40">
                </button>
                <p class="mt-2 text-center text-xs text-white/45">Click photo to enlarge</p>
            @else
                <div class="flex aspect-square items-center justify-center rounded-3xl bg-white/5 text-white/40">No photo</div>
            @endif
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-ktg-lime">{{ $registration->registration_number }}</p>
            <h2 class="mt-2 font-display text-3xl uppercase italic">{{ $registration->fullName() }}</h2>
            <p class="mt-2 uppercase text-white/70">{{ $registration->entry_level->label() }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @include('admin.partials.status-badge', ['status' => $registration->registration_status->value, 'type' => 'registration'])
                @include('admin.partials.status-badge', ['status' => $registration->payment_status->value, 'type' => 'payment'])
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Registration Information</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-white/45">Registration No.</dt>
                <dd class="font-semibold">{{ $registration->registration_number }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Date Registered</dt>
                <dd>{{ $registration->created_at->format('F j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Registration Status</dt>
                <dd>@include('admin.partials.status-badge', ['status' => $registration->registration_status->value, 'type' => 'registration'])</dd>
            </div>
        </dl>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Personal Information</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-white/45">Full Name</dt>
                <dd class="font-semibold">{{ $registration->formalName() }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Last Name</dt>
                <dd>{{ $registration->last_name }}</dd>
            </div>
            <div>
                <dt class="text-white/45">First Name</dt>
                <dd>{{ $registration->first_name }}</dd>
            </div>
            <div>
                <dt class="text-white/45">MI</dt>
                <dd>{{ $registration->middle_initial ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Contact Number</dt>
                <dd>{{ $registration->contact_number }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Email Address</dt>
                <dd><a href="mailto:{{ $registration->email }}" class="text-ktg-lime underline">{{ $registration->email }}</a></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-white/45">Address</dt>
                <dd>{{ $registration->address }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-white/45">Facebook</dt>
                <dd>
                    @if ($registration->facebookHref())
                        <a href="{{ $registration->facebookHref() }}" target="_blank" rel="noopener noreferrer" class="text-ktg-lime underline">{{ $registration->facebook }}</a>
                    @else
                        {{ $registration->facebook ?: '—' }}
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Tournament Information</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-white/45">Participated in Tournament Before</dt>
                <dd class="font-semibold uppercase">{{ $registration->has_tournament_experience->label() }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Entry Level</dt>
                <dd class="font-semibold uppercase">{{ $registration->entry_level->label() }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Payment</h3>
        <p class="mt-3 text-sm">Payment Status:
            @include('admin.partials.status-badge', ['status' => $registration->payment_status->value, 'type' => 'payment'])
        </p>
        @if ($registration->payment_rejection_reason)
            <p class="mt-2 text-sm text-red-300">Payment rejection reason: {{ $registration->payment_rejection_reason }}</p>
        @endif

        <div class="mt-4">
            @if ($registration->paymentProofIsPdf())
                <a href="{{ route('admin.applicants.proof', $registration) }}" target="_blank" class="btn-ghost">Open PDF Proof</a>
            @else
                <button type="button" class="js-lightbox" aria-label="Enlarge proof of payment" data-src="{{ route('admin.applicants.proof', $registration) }}" data-download="{{ route('admin.applicants.proof.download', $registration) }}">
                    <img src="{{ route('admin.applicants.proof', $registration) }}" alt="Proof of payment" class="max-h-96 rounded-2xl object-contain ring-2 ring-white/15">
                </button>
                <p class="mt-2 text-xs text-white/45">Click proof of payment to enlarge</p>
            @endif
            <div class="mt-3">
                <a href="{{ route('admin.applicants.proof.download', $registration) }}" class="text-sm text-ktg-lime underline">Download proof of payment</a>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.applicants.verify-payment', $registration) }}" onsubmit="return confirm('Verify this GCash payment proof?');">
                @csrf
                <button type="submit" class="btn-primary">Verify Payment</button>
            </form>
            <form method="POST" action="{{ route('admin.applicants.reject-payment', $registration) }}" class="flex flex-col gap-2 sm:flex-row">
                @csrf
                <input type="text" name="reason" class="field-input min-w-56" placeholder="Payment rejection reason (optional)">
                <button type="submit" class="rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest" onclick="return confirm('Reject this payment proof?');">Reject Payment</button>
            </form>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Registration Actions</h3>
        @if ($registration->rejection_reason)
            <p class="mt-3 text-sm text-red-300">Rejection reason: {{ $registration->rejection_reason }}</p>
        @endif
        <div class="mt-6 flex flex-col gap-4 lg:flex-row">
            <form method="POST" action="{{ route('admin.applicants.approve', $registration) }}" onsubmit="return confirm('Are you sure you want to approve this registration?');">
                @csrf
                <button type="submit" class="btn-primary">Approve Registration</button>
            </form>
            <form method="POST" action="{{ route('admin.applicants.reject', $registration) }}" class="flex-1 space-y-2">
                @csrf
                <label class="field-label">Reason for rejection</label>
                <textarea name="reason" rows="3" required minlength="5" class="field-input" placeholder="Required for rejection">{{ old('reason') }}</textarea>
                <button type="submit" class="rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest" onclick="return confirm('Are you sure you want to reject this registration?');">Reject Registration</button>
            </form>
        </div>
    </section>

    @if ($registration->activityLogs->isNotEmpty())
        <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
            <h3 class="font-display text-xl uppercase italic text-ktg-lime">Activity Log</h3>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                @foreach ($registration->activityLogs as $log)
                    <li>
                        {{ $log->created_at->format('M j, Y g:i A') }} —
                        Admin {{ $log->admin?->name ?? 'Unknown' }}
                        {{ str_replace('_', ' ', $log->action) }}
                        @if ($log->remarks)
                            ({{ $log->remarks }})
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

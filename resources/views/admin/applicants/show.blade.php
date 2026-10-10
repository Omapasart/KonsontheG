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
                @include('admin.partials.status-badge', ['status' => $registration->slot_status->value, 'type' => 'slot', 'label' => $registration->slotLabel()])
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
            <div>
                <dt class="text-white/45">Slot Status</dt>
                <dd>@include('admin.partials.status-badge', ['status' => $registration->slot_status->value, 'type' => 'slot', 'label' => $registration->slotLabel()])</dd>
            </div>
            @if ($registration->confirmed_at)
                <div>
                    <dt class="text-white/45">Confirmed At</dt>
                    <dd>{{ $registration->confirmed_at->format('F j, Y — g:i A') }}</dd>
                </div>
            @endif
            @if ($registration->withdrawn_at)
                <div>
                    <dt class="text-white/45">Withdrawn At</dt>
                    <dd>{{ $registration->withdrawn_at->format('F j, Y — g:i A') }}</dd>
                </div>
            @endif
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
        </dl>
    </section>

    @php
        $pendingTransfer = $registration->pendingTransferRequest;
        $latestTransfer = $registration->latestTransferRequest;
        $higherLevels = $registration->entry_level->higherLevels();
        $canTransfer = $pendingTransfer === null
            && $registration->slot_status->value !== 'withdrawn'
            && $registration->registration_status->value !== 'rejected'
            && $higherLevels !== [];
    @endphp
    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Tournament Information</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-white/45">Participated in Tournament Before</dt>
                <dd class="font-semibold uppercase">{{ $registration->has_tournament_experience->label() }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Current Category</dt>
                <dd class="font-semibold uppercase">{{ $registration->entry_level->label() }}</dd>
            </div>
            @if ($latestTransfer)
                <div>
                    <dt class="text-white/45">Transfer Status</dt>
                    <dd>@include('admin.partials.status-badge', ['status' => $latestTransfer->status->value, 'type' => 'transfer', 'label' => $latestTransfer->status->label()])</dd>
                </div>
                <div>
                    <dt class="text-white/45">{{ $latestTransfer->status->value === 'accepted' ? 'Previous Category' : 'Proposed Category' }}</dt>
                    <dd class="uppercase">{{ $latestTransfer->status->value === 'accepted' ? $latestTransfer->current_category->label() : $latestTransfer->requested_category->label() }}</dd>
                </div>
                @if ($latestTransfer->status->value === 'accepted')
                    <div>
                        <dt class="text-white/45">New Category</dt>
                        <dd class="uppercase">{{ $latestTransfer->requested_category->label() }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-white/45">Requested By</dt>
                    <dd>{{ $latestTransfer->requestedBy?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">Requested At</dt>
                    <dd>{{ $latestTransfer->requested_at?->format('F j, Y — g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-white/45">Applicant Response</dt>
                    <dd>{{ $latestTransfer->applicant_response?->value ? strtoupper($latestTransfer->applicant_response->value) : 'Pending' }}</dd>
                </div>
                @if ($latestTransfer->responded_at)
                    <div>
                        <dt class="text-white/45">Responded</dt>
                        <dd>{{ $latestTransfer->responded_at->format('F j, Y — g:i A') }}</dd>
                    </div>
                @endif
            @endif
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

        @if ($registration->payment_status->value === 'verified')
            <div class="mt-6 rounded-2xl border border-ktg-lime/30 bg-ktg-lime/10 px-4 py-4">
                <p class="text-sm font-extrabold uppercase tracking-widest text-ktg-lime">Payment Verified</p>
                @if ($registration->paymentReviewer || $registration->payment_reviewed_at)
                    <p class="mt-2 text-sm text-white/70">
                        @if ($registration->paymentReviewer)
                            Verified by {{ $registration->paymentReviewer->name }}
                        @endif
                        @if ($registration->payment_reviewed_at)
                            {{ $registration->paymentReviewer ? ' on ' : '' }}{{ $registration->payment_reviewed_at->format('F j, Y — g:i A') }}
                        @endif
                    </p>
                @endif
            </div>
        @else
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
        @endif
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Slot Management</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-white/45">Registration Status</dt>
                <dd>@include('admin.partials.status-badge', ['status' => $registration->registration_status->value, 'type' => 'registration'])</dd>
            </div>
            <div>
                <dt class="text-white/45">Slot Status</dt>
                <dd>@include('admin.partials.status-badge', ['status' => $registration->slot_status->value, 'type' => 'slot', 'label' => $registration->slotLabel()])</dd>
            </div>
            @if ($registration->confirmed_at)
                <div>
                    <dt class="text-white/45">Confirmed At</dt>
                    <dd>{{ $registration->confirmed_at->format('F j, Y — g:i A') }}</dd>
                </div>
            @endif
            @if ($registration->slot_status->value === 'withdrawn')
                <div>
                    <dt class="text-white/45">Withdrawn</dt>
                    <dd>{{ $registration->withdrawn_at?->format('F j, Y') ?? '—' }}</dd>
                </div>
            @endif
        </dl>
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($registration->slot_status->value === 'confirmed')
                <button type="button" id="withdraw-open" class="rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest">Withdraw Applicant</button>
                <dialog id="withdraw-dialog" class="w-[min(32rem,calc(100%-2rem))] rounded-3xl border border-white/15 bg-[#161616] p-6 text-white shadow-2xl backdrop:bg-black/70">
                    <form method="POST" action="{{ route('admin.applicants.withdraw', $registration) }}">
                        @csrf
                        <p class="font-display text-xl uppercase italic text-ktg-lime">Withdraw Applicant?</p>
                        <p class="mt-4 text-sm leading-relaxed text-white/75">Are you sure you want to mark this applicant as withdrawn?</p>
                        <p class="mt-2 text-sm leading-relaxed text-white/75">Their confirmed tournament slot will become available to the next applicant on the waiting list.</p>
                        <div class="mt-6 flex flex-wrap justify-end gap-3">
                            <button type="button" id="withdraw-cancel" class="btn-ghost">Cancel</button>
                            <button type="submit" class="rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest">Confirm Withdrawal</button>
                        </div>
                    </form>
                </dialog>
            @endif
            @if ($registration->slot_status->value === 'waiting')
                <form method="POST" action="{{ route('admin.applicants.promote', $registration) }}" onsubmit="return confirm('Promote this applicant to a confirmed slot?');">
                    @csrf
                    <button type="submit" class="btn-primary">Promote to Confirmed</button>
                </form>
            @endif
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-ktg-lime">Registration Actions</h3>
        @if ($registration->rejection_reason)
            <p class="mt-3 text-sm text-red-300">Rejection reason: {{ $registration->rejection_reason }}</p>
        @endif
        @if ($pendingTransfer)
            <p class="mt-3 rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">A category transfer request is currently awaiting the applicant's response. The application remains pending until the applicant agrees or declines.</p>
        @endif
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($registration->registration_status->value === 'pending' && $registration->slot_status->value === 'pending_verification' && $pendingTransfer === null)
                <form method="POST" action="{{ route('admin.applicants.approve', $registration) }}" onsubmit="return confirm('Verify this applicant and assign a slot based on current category availability?');">
                    @csrf
                    <button type="submit" class="btn-primary">Approve Registration</button>
                </form>
            @endif
            @if ($canTransfer)
                <button type="button" id="transfer-open" class="btn-ghost">Transfer Category</button>
                <dialog id="transfer-dialog" class="w-[min(36rem,calc(100%-2rem))] rounded-3xl border border-white/15 bg-[#161616] p-6 text-white shadow-2xl backdrop:bg-black/70">
                    <form method="POST" action="{{ route('admin.applicants.category-transfer', $registration) }}">
                        @csrf
                        <p class="font-display text-xl uppercase italic text-ktg-lime">Transfer Category</p>
                        <p class="mt-4 text-sm text-white/70">Applicant: <span class="text-white">{{ $registration->fullName() }}</span></p>
                        <p class="mt-2 text-sm text-white/70">Current Category: <span class="uppercase text-white">{{ $registration->entry_level->label() }}</span></p>
                        <label class="field-label mt-5 block">Transfer applicant to</label>
                        <select name="requested_category" required class="field-input">
                            <option value="">Select a higher category</option>
                            @foreach ($higherLevels as $level)
                                <option value="{{ $level->value }}" @selected(old('requested_category') === $level->value)>{{ $level->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-4 text-xs text-white/50">If the applicant declines, their registration will be rejected automatically.</p>
                        <div class="mt-6 flex flex-wrap justify-end gap-3">
                            <button type="button" id="transfer-cancel" class="btn-ghost">Cancel</button>
                            <button type="submit" class="btn-primary">Send Transfer Request</button>
                        </div>
                    </form>
                </dialog>
            @endif
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
                        @if ($log->old_status || $log->new_status)
                            ({{ strtoupper((string) $log->old_status) }} → {{ strtoupper((string) $log->new_status) }})
                        @endif
                        @if ($log->remarks)
                            — {{ $log->remarks }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

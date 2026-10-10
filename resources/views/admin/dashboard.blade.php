@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    @include('admin.partials.roster-actions')
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
        <a href="{{ route('admin.applicants.index') }}" class="stat-card">
            <p class="stat-label">Total Applicants</p>
            <p class="stat-value">{{ $total }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['slot_status' => 'pending_verification', 'registration_status' => 'pending']) }}" class="stat-card">
            <p class="stat-label">Pending Verification</p>
            <p class="stat-value text-amber-300">{{ $pendingVerification }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['slot_status' => 'confirmed']) }}" class="stat-card">
            <p class="stat-label">Confirmed</p>
            <p class="stat-value text-ktg-lime">{{ $confirmedSlots }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['slot_status' => 'waiting']) }}" class="stat-card">
            <p class="stat-label">Waiting List</p>
            <p class="stat-value text-amber-200">{{ $waitingSlots }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'rejected']) }}" class="stat-card">
            <p class="stat-label">Rejected</p>
            <p class="stat-value text-red-300">{{ $rejected }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['slot_status' => 'withdrawn']) }}" class="stat-card">
            <p class="stat-label">Withdrawn</p>
            <p class="stat-value text-white/70">{{ $withdrawn }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'pending']) }}" class="stat-card">
            <p class="stat-label">Pending Review</p>
            <p class="stat-value text-amber-300">{{ $pendingReview }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'approved']) }}" class="stat-card">
            <p class="stat-label">Approved</p>
            <p class="stat-value text-ktg-lime">{{ $approved }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['payment_status' => 'pending']) }}" class="stat-card">
            <p class="stat-label">Payment Pending</p>
            <p class="stat-value text-amber-300">{{ $paymentPending }}</p>
        </a>
    </div>

    <div class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
        @foreach ($levelStats as $levelValue => $stats)
            <a href="{{ route('admin.applicants.index', ['entry_level' => $levelValue]) }}" class="stat-card">
                <p class="stat-label">{{ $stats['level']->label() }}</p>
                <p class="stat-value">{{ $stats['total'] }}</p>
                <p class="mt-1.5 flex flex-wrap gap-x-2.5 gap-y-0.5 text-[10px] leading-tight text-white/55">
                    <span>{{ $stats['approved'] }} Approved </span>
                    <span>{{ $stats['pending'] }} Pending </span>
                    <span>{{ $stats['rejected'] }} Rejected </span>
                </p>
                @if (isset($categoryCapacity[$levelValue]))
                    @php $capacity = $categoryCapacity[$levelValue]; @endphp
                    <div class="mt-1.5 space-y-0.5 text-[10px] uppercase leading-tight tracking-widest text-white/70">
                        <p>{{ $capacity['confirmed'] }} / {{ $capacity['capacity'] }} confirmed</p>
                        <p class="text-amber-200">{{ $capacity['waiting'] }} / {{ $capacity['waiting_capacity'] }} waiting</p>
                        <p class="text-ktg-lime">{{ $capacity['regular_remaining'] }} regular remaining</p>
                        <p class="text-amber-200">{{ $capacity['waiting_remaining'] }} waiting remaining</p>
                        <p @class([
                            'font-extrabold tracking-[0.2em]',
                            'text-red-300' => $capacity['is_full'],
                            'text-amber-300' => $capacity['is_waiting'],
                            'text-ktg-lime' => $capacity['is_open'],
                        ])>{{ $capacity['availability_label'] }}</p>
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    <section class="mt-8 rounded-3xl border border-white/10 bg-white/5 p-5">
        <h2 class="font-display text-lg uppercase italic text-ktg-lime">Recent Applicants</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="admin-table">
            <thead>
                    <tr>
                        <th>Registration No.</th>
                        <th>Name</th>
                        <th>Level</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Slot Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $applicant)
                        <tr>
                            <td class="font-semibold text-ktg-lime">{{ $applicant->registration_number }}</td>
                            <td>{{ $applicant->fullName() }}</td>
                            <td class="uppercase">{{ $applicant->entry_level->label() }}</td>
                            <td>@include('admin.partials.status-badge', ['status' => $applicant->payment_status->value, 'type' => 'payment'])</td>
                            <td>@include('admin.partials.status-badge', ['status' => $applicant->registration_status->value, 'type' => 'registration'])</td>
                            <td>@include('admin.partials.status-badge', ['status' => $applicant->slot_status->value, 'type' => 'slot', 'label' => $applicant->slotLabel()])</td>
                            <td><a href="{{ route('admin.applicants.show', $applicant) }}" class="text-ktg-lime underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-white/50">No applicants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

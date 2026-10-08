@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('admin.applicants.index') }}" class="stat-card">
            <p class="stat-label">Total Applicants</p>
            <p class="stat-value">{{ $total }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'pending']) }}" class="stat-card">
            <p class="stat-label">Pending Review</p>
            <p class="stat-value text-amber-300">{{ $pendingReview }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'approved']) }}" class="stat-card">
            <p class="stat-label">Approved</p>
            <p class="stat-value text-ktg-lime">{{ $approved }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['registration_status' => 'rejected']) }}" class="stat-card">
            <p class="stat-label">Rejected</p>
            <p class="stat-value text-red-300">{{ $rejected }}</p>
        </a>
        <a href="{{ route('admin.applicants.index', ['payment_status' => 'pending']) }}" class="stat-card">
            <p class="stat-label">Payment Pending</p>
            <p class="stat-value text-amber-300">{{ $paymentPending }}</p>
        </a>
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
                            <td><a href="{{ route('admin.applicants.show', $applicant) }}" class="text-ktg-lime underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-white/50">No applicants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

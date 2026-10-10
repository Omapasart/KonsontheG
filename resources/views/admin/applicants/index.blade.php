@extends('layouts.admin')

@section('title', 'Applicants')
@section('heading', 'Applicants')

@section('content')
    <form method="GET" action="{{ route('admin.applicants.index') }}" class="mb-6 grid grid-cols-1 gap-3 rounded-3xl border border-white/10 bg-white/5 p-4 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="field-label">Search</label>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="field-input" placeholder="Reg. no., name, email, contact">
        </div>
        <div>
            <label class="field-label">Entry Level</label>
            <select name="entry_level" class="field-input">
                <option value="">All Levels</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected($filters['entry_level'] === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Experience</label>
            <select name="experience" class="field-input">
                <option value="">All</option>
                @foreach ($experiences as $experience)
                    <option value="{{ $experience->value }}" @selected($filters['experience'] === $experience->value)>{{ $experience->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Payment</label>
            <select name="payment_status" class="field-input">
                <option value="">All</option>
                @foreach ($paymentStatuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['payment_status'] === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Status</label>
            <select name="registration_status" class="field-input">
                <option value="">All</option>
                @foreach ($registrationStatuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['registration_status'] === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Slot Status</label>
            <select name="slot_status" class="field-input">
                <option value="">All Slots</option>
                @foreach ($slotStatuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['slot_status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="field-label">Sort</label>
            <select name="sort" class="field-input">
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest registration</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest registration</option>
                <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                <option value="number" @selected($filters['sort'] === 'number')>Registration number</option>
                <option value="status" @selected($filters['sort'] === 'status')>Status</option>
            </select>
        </div>
        <div class="flex items-end gap-2 lg:col-span-4">
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('admin.applicants.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-3xl border border-white/10 bg-white/5">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Registration No.</th>
                    <th>Photo</th>
                    <th>Full Name</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Entry Level</th>
                    <th>Transfer</th>
                    <th>Slot Status</th>
                    <th>Experience</th>
                    <th>Payment Status</th>
                    <th>Registration Status</th>
                    <th>Date Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applicants as $applicant)
                    <tr @class(['opacity-80' => $applicant->slot_status->value === 'withdrawn'])>
                        <td class="font-semibold text-ktg-lime">{{ $applicant->registration_number }}</td>
                        <td>
                            <a href="{{ route('admin.applicants.show', $applicant) }}" aria-label="View {{ $applicant->fullName() }}">
                                <img src="{{ route('admin.applicants.photo', $applicant) }}" alt="{{ $applicant->fullName() }}" class="h-12 w-12 rounded-full object-cover ring-1 ring-white/20" onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-[10px] text-white/40', textContent:'N/A'}))">
                            </a>
                        </td>
                        <td>{{ $applicant->fullName() }}</td>
                        <td>{{ $applicant->contact_number }}</td>
                        <td>{{ $applicant->email }}</td>
                        <td class="uppercase">{{ $applicant->entry_level->label() }}</td>
                        <td>
                            @if ($applicant->latestTransferRequest)
                                @include('admin.partials.status-badge', [
                                    'status' => $applicant->latestTransferRequest->status->value,
                                    'type' => 'transfer',
                                    'label' => $applicant->latestTransferRequest->transferLabel(),
                                ])
                            @else
                                @include('admin.partials.status-badge', ['status' => 'none', 'type' => 'transfer', 'label' => 'None'])
                            @endif
                        </td>
                        <td>@include('admin.partials.status-badge', ['status' => $applicant->slot_status->value, 'type' => 'slot', 'label' => $applicant->slotLabel()])</td>
                        <td>{{ $applicant->has_tournament_experience->label() }}</td>
                        <td>@include('admin.partials.status-badge', ['status' => $applicant->payment_status->value, 'type' => 'payment'])</td>
                        <td>
                            @include('admin.partials.status-badge', ['status' => $applicant->registration_status->value, 'type' => 'registration'])
                            @if ($applicant->slot_status->value === 'withdrawn')
                                <p class="mt-1 text-[10px] uppercase tracking-widest text-white/45">Slot withdrawn</p>
                            @endif
                        </td>
                        <td>{{ $applicant->created_at->format('M j, Y') }}</td>
                        <td><a href="{{ route('admin.applicants.show', $applicant) }}" class="btn-ghost !px-4 !py-2 text-xs">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="py-10 text-center text-white/50">No applicants match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-white/50">
            @php
                $levelLabel = collect($levels)->first(fn ($level) => $level->value === $filters['entry_level'])?->label();
                $groupLabel = $filters['slot_status'] === 'withdrawn'
                    ? 'withdrawn applicants'
                    : ($levelLabel ? $levelLabel.' applicants' : 'applicants');
            @endphp
            @if ($applicants->total())
                Showing {{ $applicants->firstItem() }}–{{ $applicants->lastItem() }} of {{ $applicants->total() }} {{ $groupLabel }}
            @else
                Showing 0 of 0 {{ $groupLabel }}
            @endif
        </p>
        {{ $applicants->links() }}
    </div>
@endsection

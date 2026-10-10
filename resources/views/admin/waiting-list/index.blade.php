@extends('layouts.admin')

@section('title', 'Waiting List')
@section('heading', 'Waiting List')

@section('content')
    <form method="GET" action="{{ route('admin.waiting-list') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-3xl border border-white/10 bg-white/5 p-4">
        <div>
            <label class="field-label">Category</label>
            <select name="entry_level" class="field-input min-w-48">
                <option value="">All</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected($entryLevel === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        <a href="{{ route('admin.waiting-list') }}" class="btn-ghost">Reset</a>
    </form>

    <div class="overflow-x-auto rounded-3xl border border-white/10 bg-white/5">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Registration No.</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Email</th>
                    <th>Date Registered</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applicants as $applicant)
                    <tr>
                        <td class="font-display text-lg italic text-ktg-lime">#{{ $applicant->waiting_list_position }}</td>
                        <td>{{ $applicant->registration_number }}</td>
                        <td>{{ $applicant->fullName() }}</td>
                        <td class="uppercase">{{ $applicant->entry_level->label() }}</td>
                        <td>{{ $applicant->email }}</td>
                        <td>{{ $applicant->created_at->format('M j, Y') }}</td>
                        <td><a href="{{ route('admin.applicants.show', $applicant) }}" class="btn-ghost !px-4 !py-2 text-xs">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-white/50">No waiting-list applicants.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applicants->links() }}
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit '.$registration->registration_number)
@section('heading', 'Manage Applicant')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.applicants.show', $registration) }}" class="text-sm text-ktg-lime underline">← Back to applicant</a>
        <p class="text-xs uppercase tracking-widest text-white/45">{{ $registration->registration_number }}</p>
    </div>

    <section class="rounded-3xl border border-white/10 bg-white/5 p-5 lg:p-8">
        <h2 class="font-display text-2xl uppercase italic text-ktg-lime">Edit Applicant Details</h2>
        <p class="mt-2 text-sm text-white/60">Correct personal information without changing slot, payment, or registration status.</p>

        <form method="POST" action="{{ route('admin.applicants.update', $registration) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="last_name" class="field-label">Last Name *</label>
                    <input id="last_name" name="last_name" type="text" required maxlength="80" value="{{ old('last_name', $registration->last_name) }}" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="first_name" class="field-label">First Name *</label>
                    <input id="first_name" name="first_name" type="text" required maxlength="80" value="{{ old('first_name', $registration->first_name) }}" class="field-input">
                </div>
                <div class="sm:col-span-1">
                    <label for="middle_initial" class="field-label">MI</label>
                    <input id="middle_initial" name="middle_initial" type="text" maxlength="5" value="{{ old('middle_initial', $registration->middle_initial) }}" class="field-input" placeholder="A">
                </div>
            </div>

            <div>
                <label for="contact_number" class="field-label">Contact Number *</label>
                <input id="contact_number" name="contact_number" type="tel" inputmode="tel" required value="{{ old('contact_number', $registration->contact_number) }}" class="field-input" placeholder="09XXXXXXXXX">
            </div>

            <div>
                <label for="address" class="field-label">Address *</label>
                <textarea id="address" name="address" rows="3" required maxlength="500" class="field-input">{{ old('address', $registration->address) }}</textarea>
            </div>

            <div>
                <label for="email" class="field-label">Email Address *</label>
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $registration->email) }}" class="field-input">
            </div>

            <div>
                <label for="photo" class="field-label">Replace Photo</label>
                @if ($registration->photo_path)
                    <img src="{{ route('admin.applicants.photo', $registration) }}" alt="Current photo" class="mb-3 h-24 w-24 rounded-2xl object-cover ring-1 ring-white/20">
                @endif
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="field-input">
                <p class="mt-1 text-xs text-white/45">Leave empty to keep the current photo. JPG or PNG only.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Save Applicant Details</button>
                <a href="{{ route('admin.applicants.show', $registration) }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-3xl border border-red-400/30 bg-red-500/10 p-5 lg:p-8">
        <h3 class="font-display text-xl uppercase italic text-red-300">Delete Applicant</h3>
        <p class="mt-2 text-sm text-white/70">This permanently removes the applicant record and files. If they occupy a confirmed slot, that slot is released to the waiting list.</p>
        <form method="POST" action="{{ route('admin.applicants.destroy', $registration) }}" class="mt-4" onsubmit="return confirm('Delete {{ $registration->registration_number }} permanently?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-full bg-red-600 px-6 py-3 text-sm font-extrabold uppercase tracking-widest">Delete Applicant</button>
        </form>
    </section>
@endsection

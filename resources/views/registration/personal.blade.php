@extends('layouts.registration')

@section('title', 'Personal Data')

@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="text-center font-display text-3xl uppercase italic tracking-tight sm:text-5xl">Personal Data</h1>
        <p class="mt-3 text-center text-white/60">Tell us who you are. Required fields are marked with *</p>

        @if (session('capacity_notice') === 'waiting')
            <div class="mt-6 rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-4 text-sm leading-relaxed text-amber-100">
                <p class="font-extrabold uppercase tracking-widest">Waiting List</p>
                <p class="mt-2">Regular slots for this category are currently full. After you submit, an administrator must verify your application. You may then be placed on the waiting list if a position is available.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('register.personal.store') }}" enctype="multipart/form-data" class="mt-8 space-y-5 rounded-3xl border border-white/10 bg-white/5 p-5 sm:p-8">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="last_name" class="field-label">Last Name *</label>
                    <input id="last_name" name="last_name" type="text" required maxlength="80" value="{{ old('last_name', $wizard['last_name'] ?? '') }}" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="first_name" class="field-label">First Name *</label>
                    <input id="first_name" name="first_name" type="text" required maxlength="80" value="{{ old('first_name', $wizard['first_name'] ?? '') }}" class="field-input">
                </div>
                <div class="sm:col-span-1">
                    <label for="middle_initial" class="field-label">MI</label>
                    <input id="middle_initial" name="middle_initial" type="text" maxlength="5" value="{{ old('middle_initial', $wizard['middle_initial'] ?? '') }}" class="field-input" placeholder="A">
                </div>
            </div>

            <div>
                <label for="contact_number" class="field-label">Contact Number *</label>
                <input id="contact_number" name="contact_number" type="tel" inputmode="tel" required value="{{ old('contact_number', $wizard['contact_number'] ?? '') }}" class="field-input" placeholder="09XXXXXXXXX">
                <p class="mt-1 text-xs text-white/45">Philippine mobile format: 09XXXXXXXXX or +639XXXXXXXXX</p>
            </div>

            <div>
                <label for="address" class="field-label">Address *</label>
                <textarea id="address" name="address" rows="3" required maxlength="500" class="field-input">{{ old('address', $wizard['address'] ?? '') }}</textarea>
            </div>

            <div>
                <label for="email" class="field-label">Email Address *</label>
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $wizard['email'] ?? '') }}" class="field-input">
                @error('email')
                    <p class="mt-2 text-sm text-red-200">{{ $message }}</p>
                @enderror
            </div>

            <aside class="rounded-2xl border border-ktg-lime/40 bg-ktg-lime/10 px-4 py-4 text-sm leading-relaxed text-ktg-lime">
                <strong>NOTE:</strong> Please provide a correct and active email address. Your tournament registration confirmation and other important tournament information will be sent to this email address.
            </aside>

            <div>
                <label for="photo" class="field-label">Photo *</label>
                <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="field-file" data-preview-target="photo-preview" data-filename-target="photo-filename" @if (empty($wizard['photo_path'])) required @endif>
                <p class="mt-1 text-xs text-white/45">JPG, JPEG, or PNG. Maximum {{ number_format(config('tournament.photo_max_kb') / 1024, 1) }} MB.</p>
                <p id="photo-filename" class="mt-2 text-sm text-white/70">
                    @if (! empty($wizard['photo_name']))
                        Selected: {{ $wizard['photo_name'] }}
                    @endif
                </p>
                <img
                    id="photo-preview"
                    src="{{ ! empty($wizard['photo_path']) ? route('register.preview.photo') : '' }}"
                    alt="Photo preview"
                    class="mt-3 h-32 w-32 rounded-2xl object-cover ring-2 ring-ktg-lime/40 {{ empty($wizard['photo_path']) ? 'hidden' : '' }}"
                >
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <a href="{{ route('register.level') }}" class="btn-ghost">Back</a>
                <button type="submit" class="btn-primary">Continue to Payment</button>
            </div>
        </form>
    </section>
@endsection

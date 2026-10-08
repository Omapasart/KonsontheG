@extends('layouts.admin')

@section('title', 'Admin Profile')
@section('heading', 'Admin Profile')

@section('content')
    <section class="max-w-xl rounded-3xl border border-white/10 bg-white/5 p-6">
        <dl class="space-y-4 text-sm">
            <div>
                <dt class="text-white/45">Name</dt>
                <dd class="text-lg font-semibold">{{ $admin->name }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Email</dt>
                <dd>{{ $admin->email }}</dd>
            </div>
            <div>
                <dt class="text-white/45">Role</dt>
                <dd>Administrator</dd>
            </div>
        </dl>
    </section>
@endsection

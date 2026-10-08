@php
    $label = ucfirst($status);
    $classes = match ($status) {
        'approved', 'verified' => 'badge-success',
        'rejected' => 'badge-danger',
        default => 'badge-warning',
    };
@endphp
<span class="status-badge {{ $classes }}">{{ $type === 'payment' && $status === 'verified' ? 'Payment Verified' : ($type === 'payment' && $status === 'pending' ? 'Payment Pending' : $label) }}</span>

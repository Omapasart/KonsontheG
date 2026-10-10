@php
    $label = $label ?? ucfirst($status);
    $classes = match (true) {
        in_array($status, ['approved', 'verified', 'confirmed', 'accepted'], true) => 'badge-success',
        $status === 'withdrawn' || $status === 'none' => 'badge-withdrawn',
        in_array($status, ['rejected', 'declined'], true) => 'badge-danger',
        default => 'badge-warning',
    };
    $text = $type === 'payment' && $status === 'verified'
        ? 'Payment Verified'
        : ($type === 'payment' && $status === 'pending' ? 'Payment Pending' : $label);
@endphp
<span class="status-badge {{ $classes }}">{{ $text }}</span>

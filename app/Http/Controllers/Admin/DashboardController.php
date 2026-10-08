<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'total' => Registration::query()->count(),
            'pendingReview' => Registration::query()->where('registration_status', RegistrationStatus::Pending)->count(),
            'approved' => Registration::query()->where('registration_status', RegistrationStatus::Approved)->count(),
            'rejected' => Registration::query()->where('registration_status', RegistrationStatus::Rejected)->count(),
            'paymentPending' => Registration::query()->where('payment_status', PaymentStatus::Pending)->count(),
            'recent' => Registration::query()->latest()->limit(8)->get(),
        ]);
    }
}

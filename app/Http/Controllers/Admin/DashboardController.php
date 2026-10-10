<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\CategoryCapacityService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

    public function __invoke(): View
    {
        $levelStats = [];

        foreach (EntryLevel::cases() as $level) {
            $levelStats[$level->value] = [
                'level' => $level,
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ];
        }

        $levelRows = Registration::query()
            ->selectRaw('entry_level, registration_status, COUNT(*) as aggregate')
            ->groupBy('entry_level', 'registration_status')
            ->get();

        foreach ($levelRows as $row) {
            $level = $row->entry_level instanceof EntryLevel
                ? $row->entry_level->value
                : (string) $row->entry_level;

            if (! isset($levelStats[$level])) {
                continue;
            }

            $status = $row->registration_status instanceof RegistrationStatus
                ? $row->registration_status->value
                : (string) $row->registration_status;

            $count = (int) $row->aggregate;
            $levelStats[$level]['total'] += $count;

            if (array_key_exists($status, $levelStats[$level])) {
                $levelStats[$level][$status] = $count;
            }
        }

        return view('admin.dashboard', [
            'total' => Registration::query()->count(),
            'pendingReview' => Registration::query()->where('registration_status', RegistrationStatus::Pending)->count(),
            'approved' => Registration::query()->where('registration_status', RegistrationStatus::Approved)->count(),
            'rejected' => Registration::query()->where('registration_status', RegistrationStatus::Rejected)->count(),
            'paymentPending' => Registration::query()->where('payment_status', PaymentStatus::Pending)->count(),
            'withdrawn' => Registration::query()->where('slot_status', SlotStatus::Withdrawn)->count(),
            'pendingVerification' => Registration::query()
                ->where('slot_status', SlotStatus::PendingVerification)
                ->where('registration_status', RegistrationStatus::Pending)
                ->count(),
            'confirmedSlots' => Registration::query()->where('slot_status', SlotStatus::Confirmed)->count(),
            'waitingSlots' => Registration::query()->where('slot_status', SlotStatus::Waiting)->count(),
            'levelStats' => $levelStats,
            'categoryCapacity' => $this->capacity->snapshot(),
            'recent' => Registration::query()->latest()->limit(8)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryLevel;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\CategoryCapacityService;
use App\Services\RefreshConfirmedSlotsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefreshConfirmedSlotsController extends Controller
{
    public function __construct(
        private readonly CategoryCapacityService $capacity,
        private readonly RefreshConfirmedSlotsService $refresh,
    ) {}

    public function create(EntryLevel $level): View
    {
        $status = $this->capacity->statusFor($level);

        return view('admin.slots.refresh', [
            'level' => $level,
            'category' => $status,
            'totalApplicants' => Registration::query()->where('entry_level', $level)->count(),
            'waitingApplicants' => Registration::query()
                ->where('entry_level', $level)
                ->where('slot_status', 'waiting')
                ->count(),
        ]);
    }

    public function store(Request $request, EntryLevel $level): RedirectResponse
    {
        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Please confirm that you want to delete non-waiting applicants in this category and move its waiting list to pending slot and payment verification.',
        ]);

        $result = $this->refresh->refresh($request->user(), $level);
        $this->refresh->notifyTransferred($result['pending']);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', $this->refresh->summaryMessage($result));
    }
}

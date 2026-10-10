<?php

namespace App\Http\Controllers\Admin;

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

    public function create(): View
    {
        return view('admin.slots.refresh', [
            'categories' => $this->capacity->snapshot(),
            'totalApplicants' => Registration::query()->count(),
            'waitingApplicants' => Registration::query()->where('slot_status', 'waiting')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Please confirm that you want to delete non-waiting applicants and move the waiting list to pending slot and payment verification.',
        ]);

        $result = $this->refresh->refresh($request->user());
        $this->refresh->notifyTransferred($result['pending']);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', $this->refresh->summaryMessage($result));
    }
}

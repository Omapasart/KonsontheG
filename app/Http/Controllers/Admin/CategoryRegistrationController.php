<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryLevel;
use App\Http\Controllers\Controller;
use App\Services\CategoryCapacityService;
use App\Services\CategoryRegistrationControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryRegistrationController extends Controller
{
    public function __construct(
        private readonly CategoryCapacityService $capacity,
        private readonly CategoryRegistrationControlService $control,
    ) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => $this->capacity->snapshot(),
        ]);
    }

    public function update(Request $request, string $level): RedirectResponse
    {
        $validated = $request->validate([
            'closed' => ['required', Rule::in(['0', '1'])],
        ]);

        abort_unless(in_array($level, array_column(EntryLevel::cases(), 'value'), true), 404);

        $entryLevel = EntryLevel::from($level);
        $closed = $validated['closed'] === '1';
        $this->control->setClosed($entryLevel, $closed, $request->user());

        $message = $closed
            ? $entryLevel->label().' is now closed to new applicants. Existing applicants were not changed.'
            : $entryLevel->label().' is now open for new applicants.';

        return back()->with('success', $message);
    }

    public function updateLimits(Request $request, string $level): RedirectResponse
    {
        abort_unless(in_array($level, array_column(EntryLevel::cases(), 'value'), true), 404);

        $validated = $request->validate([
            'confirmed_capacity' => ['required', 'integer', 'min:0', 'max:500'],
            'waiting_capacity' => ['required', 'integer', 'min:0', 'max:500'],
        ], [
            'confirmed_capacity.min' => 'Confirmed slots cannot be negative.',
            'waiting_capacity.min' => 'Waiting-list slots cannot be negative.',
        ]);

        $entryLevel = EntryLevel::from($level);
        $this->control->setLimits(
            $entryLevel,
            (int) $validated['confirmed_capacity'],
            (int) $validated['waiting_capacity'],
            $request->user()
        );

        return back()->with(
            'success',
            $entryLevel->label().' limits are now '.$validated['confirmed_capacity'].' confirmed and '.$validated['waiting_capacity'].' waiting. Existing applicants were not changed.'
        );
    }
}

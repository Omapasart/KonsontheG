<?php

namespace App\Http\Controllers;

use App\Models\CategoryTransferRequest;
use App\Services\CategoryTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryTransferResponseController extends Controller
{
    public function __construct(private readonly CategoryTransferService $transfers) {}

    public function show(Request $request, CategoryTransferRequest $transfer): View
    {
        abort_unless(hash_equals((string) $transfer->token, (string) $request->query('token')), 403);

        $transfer->load('registration');

        return view('transfers.show', [
            'transfer' => $transfer,
            'registration' => $transfer->registration,
        ]);
    }

    public function accept(Request $request, CategoryTransferRequest $transfer): View|RedirectResponse
    {
        $this->assertToken($request, $transfer);

        try {
            $result = $this->transfers->accept($transfer);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'This transfer request cannot be completed.';

            return view('transfers.result', [
                'title' => 'Category Transfer Unavailable',
                'message' => $message,
                'transfer' => $transfer->fresh('registration'),
            ]);
        }

        $waiting = $result['waiting'];
        $level = $result['transfer']->requested_category->label();

        return view('transfers.result', [
            'title' => 'Category Transfer Accepted',
            'message' => $waiting
                ? 'Your transfer to the '.$level.' category has been accepted. The '.$level.' category\'s regular slots are currently full, so you have been placed on the '.$level.' waiting list.'
                : 'Your transfer to the '.$level.' category has been accepted. Your tournament slot is now confirmed.',
            'transfer' => $result['transfer'],
            'registration' => $result['registration'],
        ]);
    }

    public function decline(Request $request, CategoryTransferRequest $transfer): View
    {
        $this->assertToken($request, $transfer);

        try {
            $updated = $this->transfers->decline($transfer);
        } catch (ValidationException $exception) {
            return view('transfers.result', [
                'title' => 'Category Transfer Request',
                'message' => collect($exception->errors())->flatten()->first() ?? 'This transfer request has already been answered.',
                'transfer' => $transfer->fresh('registration'),
            ]);
        }

        return view('transfers.result', [
            'title' => 'Registration Rejected',
            'message' => 'Your decision has been recorded. Since you did not agree to compete in the proposed category, your tournament registration has been rejected. Your original registered category remains '.$updated->current_category->label().'.',
            'transfer' => $updated,
            'registration' => $updated->registration,
        ]);
    }

    private function assertToken(Request $request, CategoryTransferRequest $transfer): void
    {
        abort_unless(hash_equals((string) $transfer->token, (string) $request->input('token')), 403);
    }
}

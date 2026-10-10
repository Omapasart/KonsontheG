<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentExperience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Http\Requests\Admin\RejectRegistrationRequest;
use App\Models\Registration;
use App\Enums\SlotStatus;
use App\Services\AdminRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    public function __construct(private readonly AdminRegistrationService $adminRegistrations) {}

    public function index(Request $request): View
    {
        $query = Registration::query()->with('latestTransferRequest');

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%'.$search.'%';
                $builder->where('registration_number', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('contact_number', 'like', $like);
            });
        }

        if ($request->filled('entry_level')) {
            $query->where('entry_level', $request->string('entry_level'));
        }

        if ($request->filled('experience')) {
            $query->where('has_tournament_experience', $request->string('experience'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }

        if ($request->filled('registration_status')) {
            $query->where('registration_status', $request->string('registration_status'));
        }

        $slotStatus = (string) $request->query('slot_status', $request->query('status', ''));

        if ($slotStatus !== '') {
            $query->where('slot_status', $slotStatus);
        }

        $sort = (string) $request->query('sort', 'newest');

        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('last_name')->orderBy('first_name'),
            'number' => $query->orderBy('registration_number'),
            'status' => $query->orderBy('registration_status')->orderByDesc('created_at'),
            default => $query->latest(),
        };

        $applicants = $query->paginate(20)->withQueryString();

        return view('admin.applicants.index', [
            'applicants' => $applicants,
            'filters' => [
                'q' => $search,
                'entry_level' => $request->query('entry_level', ''),
                'experience' => $request->query('experience', ''),
                'payment_status' => $request->query('payment_status', ''),
                'registration_status' => $request->query('registration_status', ''),
                'slot_status' => $slotStatus,
                'sort' => $sort,
            ],
            'levels' => EntryLevel::cases(),
            'experiences' => TournamentExperience::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'registrationStatuses' => array_values(array_filter(
                RegistrationStatus::cases(),
                fn (RegistrationStatus $status) => $status !== RegistrationStatus::Verified
            )),
            'slotStatuses' => SlotStatus::cases(),
        ]);
    }

    public function show(Registration $registration): View
    {
        $registration->load([
            'reviewer',
            'paymentReviewer',
            'activityLogs.admin',
            'pendingTransferRequest.requestedBy',
            'latestTransferRequest.requestedBy',
        ]);

        return view('admin.applicants.show', compact('registration'));
    }

    public function approve(Request $request, Registration $registration): RedirectResponse
    {
        try {
            $approved = $this->adminRegistrations->approve($registration, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $message = $approved->slot_status === SlotStatus::Waiting
            ? 'Registration '.$approved->registration_number.' has been verified and placed on the waiting list.'
            : 'Registration '.$approved->registration_number.' has been verified and assigned a confirmed slot.';

        return back()->with('success', $message);
    }

    public function reject(RejectRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $this->adminRegistrations->reject($registration, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Registration '.$registration->registration_number.' has been rejected.');
    }

    public function verifyPayment(Request $request, Registration $registration): RedirectResponse
    {
        try {
            $emailed = $this->adminRegistrations->verifyPayment($registration, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $message = 'Payment for '.$registration->registration_number.' has been verified.';

        if (! $emailed) {
            return back()->with('success', $message)->with('warning', 'The applicant email could not be sent. Check storage/logs/laravel.log.');
        }

        return back()->with('success', $message.' A notice was emailed to the applicant.');
    }

    public function rejectPayment(RejectPaymentRequest $request, Registration $registration): RedirectResponse
    {
        try {
            $emailed = $this->adminRegistrations->rejectPayment(
                $registration,
                $request->user(),
                $request->validated('reason')
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $message = 'Payment for '.$registration->registration_number.' has been rejected.';

        if (! $emailed) {
            return back()->with('success', $message)->with('warning', 'The applicant email could not be sent. Check storage/logs/laravel.log.');
        }

        return back()->with('success', $message.' A notice was emailed to the applicant.');
    }

    public function withdraw(Request $request, Registration $registration): RedirectResponse
    {
        try {
            $this->adminRegistrations->withdraw($registration, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', $registration->registration_number.' has been marked as withdrawn.');
    }

    public function promote(Request $request, Registration $registration): RedirectResponse
    {
        try {
            $this->adminRegistrations->promote($registration, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', $registration->registration_number.' has been promoted to a confirmed slot.');
    }
}

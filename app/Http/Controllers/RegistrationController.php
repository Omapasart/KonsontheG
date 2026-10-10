<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryLevelRequest;
use App\Http\Requests\StoreExperienceRequest;
use App\Http\Requests\StorePersonalDataRequest;
use App\Http\Requests\SubmitRegistrationRequest;
use App\Models\Registration;
use App\Services\CategoryCapacityService;
use App\Services\RegistrationService;
use App\Support\RegistrationWizard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly CategoryCapacityService $capacity,
    ) {}

    public function welcome(): View
    {
        RegistrationWizard::markReached(1);

        return view('registration.welcome', $this->viewData(1));
    }

    public function continueWelcome(): RedirectResponse
    {
        RegistrationWizard::markReached(2);

        return redirect()->route('register.experience');
    }

    public function experience(): View|RedirectResponse
    {
        return $this->guard(2) ?? view('registration.experience', $this->viewData(2));
    }

    public function storeExperience(StoreExperienceRequest $request): RedirectResponse
    {
        RegistrationWizard::merge([
            'has_tournament_experience' => $request->validated('has_tournament_experience'),
        ]);
        RegistrationWizard::markReached(3);

        return redirect()->route('register.level');
    }

    public function level(): View|RedirectResponse
    {
        return $this->guard(3) ?? view('registration.level', array_merge($this->viewData(3), [
            'categories' => $this->capacity->snapshot(),
        ]));
    }

    public function storeLevel(StoreEntryLevelRequest $request): RedirectResponse
    {
        $level = $request->validated('entry_level');
        $status = $this->capacity->statusFor($level);

        RegistrationWizard::merge([
            'entry_level' => $level,
        ]);
        RegistrationWizard::markReached(4);

        return redirect()
            ->route('register.personal')
            ->with('capacity_notice', $status['is_full'] ? 'full' : ($status['is_waiting'] ? 'waiting' : null));
    }

    public function personal(): View|RedirectResponse
    {
        return $this->guard(4) ?? view('registration.personal', $this->viewData(4));
    }

    public function storePersonal(StorePersonalDataRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = RegistrationWizard::storeTempFile($request->file('photo'), 'photo');
            $data['photo_name'] = $request->file('photo')->getClientOriginalName();
        }

        RegistrationWizard::merge($data);
        RegistrationWizard::markReached(5);

        return redirect()
            ->route('register.payment')
            ->with('email_available', true);
    }

    public function payment(): View|RedirectResponse
    {
        $blocked = $this->guard(5);

        if ($blocked) {
            return $blocked;
        }

        if (! filled(RegistrationWizard::data()['submission_token'] ?? null)) {
            RegistrationWizard::merge(['submission_token' => Str::random(64)]);
        }

        return view('registration.payment', $this->viewData(5));
    }

    public function downloadPaymentQr(): BinaryFileResponse
    {
        $relative = str_replace('\\', '/', ltrim((string) config('tournament.qr_image'), '/'));

        abort_unless($relative !== '' && ! str_contains($relative, '..'), 404);

        $publicRoot = realpath(public_path());
        $fullPath = realpath(public_path($relative));

        abort_unless(
            $publicRoot && $fullPath && is_file($fullPath) && str_starts_with($fullPath, $publicRoot.DIRECTORY_SEPARATOR),
            404
        );

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');

        return response()->download($fullPath, 'KONSONTHEGO-GCash-Payment-QR.'.$extension);
    }

    public function submit(SubmitRegistrationRequest $request): RedirectResponse
    {
        $blocked = $this->guard(5);

        if ($blocked) {
            return $blocked;
        }

        $wizard = RegistrationWizard::data();
        $token = $request->validated('submission_token');

        $existing = Registration::query()->where('submission_token', $token)->first();

        if ($existing) {
            session()->forget(RegistrationWizard::SESSION_KEY);
            session(['completed_registration' => $this->completedPayload($existing)]);

            return redirect()->route('register.confirmation');
        }

        if (($wizard['submission_token'] ?? null) !== $token) {
            return back()->withErrors([
                'registration' => 'Your session expired. Please review your information and submit again.',
            ]);
        }

        if ($request->hasFile('payment_proof')) {
            RegistrationWizard::merge([
                'payment_proof_path' => RegistrationWizard::storeTempFile($request->file('payment_proof'), 'proof'),
                'payment_proof_name' => $request->file('payment_proof')->getClientOriginalName(),
            ]);
            $wizard = RegistrationWizard::data();
        }

        $this->registrations->assertComplete($wizard, false);

        try {
            $registration = $this->registrations->submit($wizard, $token);
        } catch (ValidationException $exception) {
            if ($exception->errors()['email'] ?? null) {
                return redirect()->route('register.email-taken');
            }

            if ($exception->errors()['entry_level'] ?? null) {
                return redirect()->route('register.category-full', [
                    'level' => $wizard['entry_level'] ?? null,
                ]);
            }

            throw $exception;
        }

        session()->forget(RegistrationWizard::SESSION_KEY);
        session(['completed_registration' => $this->completedPayload($registration)]);
        $request->session()->regenerateToken();

        return redirect()->route('register.confirmation');
    }

    public function startAgain(): RedirectResponse
    {
        RegistrationWizard::forget();
        session()->forget('completed_registration');

        return redirect()->route('register.welcome');
    }

    public function emailTaken(): View|RedirectResponse
    {
        return view('registration.email-taken', [
            'step' => RegistrationWizard::reached(),
            'wizard' => RegistrationWizard::data(),
        ]);
    }

    public function categoryFull(): View
    {
        $level = request()->query('level', RegistrationWizard::data()['entry_level'] ?? 'beginner');

        try {
            $status = $this->capacity->statusFor((string) $level);
        } catch (\Throwable) {
            $status = $this->capacity->statusFor('beginner');
        }

        return view('registration.category-full', [
            'step' => RegistrationWizard::reached(),
            'wizard' => RegistrationWizard::data(),
            'status' => $status,
        ]);
    }

    public function confirmation(): View|RedirectResponse
    {
        $completed = session('completed_registration');

        if (! is_array($completed)) {
            return redirect()->route('register.welcome');
        }

        return view('registration.confirmation', [
            'step' => 6,
            'wizard' => [],
            'completed' => $completed,
            'hours' => (int) config('tournament.confirmation_hours', 28),
        ]);
    }

    public function previewPhoto(): StreamedResponse
    {
        return $this->preview('photo_path');
    }

    public function previewProof(): StreamedResponse
    {
        return $this->preview('payment_proof_path');
    }

    /**
     * @return array<string, mixed>
     */
    private function completedPayload(Registration $registration): array
    {
        return [
            'id' => $registration->id,
            'number' => $registration->registration_number,
            'email' => $registration->email,
            'name' => $registration->fullName(),
            'entry_level' => $registration->entry_level->label(),
            'slot_status' => $registration->slot_status->value,
            'waiting_list_position' => $registration->waiting_list_position,
        ];
    }

    private function preview(string $key): StreamedResponse
    {
        $path = RegistrationWizard::data()[$key] ?? null;

        abort_unless(RegistrationWizard::ownsTempPath($path), 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(int $step): array
    {
        return [
            'step' => $step,
            'wizard' => RegistrationWizard::data(),
        ];
    }

    private function guard(int $step): ?RedirectResponse
    {
        if (RegistrationWizard::canAccess($step)) {
            return null;
        }

        return redirect()
            ->route($this->routeForStep(RegistrationWizard::reached()))
            ->with('warning', 'Please complete the previous steps first.');
    }

    private function routeForStep(int $step): string
    {
        return match (max(1, min(5, $step))) {
            1 => 'register.welcome',
            2 => 'register.experience',
            3 => 'register.level',
            4 => 'register.personal',
            default => 'register.payment',
        };
    }
}

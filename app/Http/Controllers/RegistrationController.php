<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryLevelRequest;
use App\Http\Requests\StoreExperienceRequest;
use App\Http\Requests\StorePersonalDataRequest;
use App\Http\Requests\SubmitRegistrationRequest;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Support\RegistrationWizard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registrations) {}

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
        return $this->guard(3) ?? view('registration.level', $this->viewData(3));
    }

    public function storeLevel(StoreEntryLevelRequest $request): RedirectResponse
    {
        RegistrationWizard::merge([
            'entry_level' => $request->validated('entry_level'),
        ]);
        RegistrationWizard::markReached(4);

        return redirect()->route('register.personal');
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

        return redirect()->route('register.payment');
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
            session(['completed_registration' => [
                'id' => $existing->id,
                'number' => $existing->registration_number,
                'email' => $existing->email,
                'name' => $existing->fullName(),
            ]]);

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

        $registration = $this->registrations->submit($wizard, $token);

        session()->forget(RegistrationWizard::SESSION_KEY);
        session(['completed_registration' => [
            'id' => $registration->id,
            'number' => $registration->registration_number,
            'email' => $registration->email,
            'name' => $registration->fullName(),
        ]]);
        $request->session()->regenerateToken();

        return redirect()->route('register.confirmation');
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

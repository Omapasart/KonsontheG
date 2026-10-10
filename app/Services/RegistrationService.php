<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\RegistrationReceived;
use App\Models\Registration;
use App\Support\RegistrationWizard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

    /**
     * @param  array<string, mixed>  $wizard
     */
    public function assertComplete(array $wizard, bool $hasNewProof): void
    {
        $missing = [];

        if (! in_array($wizard['has_tournament_experience'] ?? null, ['yes', 'no'], true)) {
            $missing[] = 'tournament experience';
        }

        if (! in_array($wizard['entry_level'] ?? null, ['beginner', 'novice', 'intermediate'], true)) {
            $missing[] = 'entry level';
        }

        foreach (['last_name', 'first_name', 'contact_number', 'address', 'email'] as $field) {
            if (! filled($wizard[$field] ?? null)) {
                $missing[] = str_replace('_', ' ', $field);
            }
        }

        if (! RegistrationWizard::ownsTempPath($wizard['photo_path'] ?? null)) {
            $missing[] = 'photo';
        }

        if (! $hasNewProof && ! RegistrationWizard::ownsTempPath($wizard['payment_proof_path'] ?? null)) {
            $missing[] = 'proof of payment';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'registration' => 'Please complete all required information before submitting: '.implode(', ', $missing).'.',
            ]);
        }

        $this->assertEmailAvailable((string) $wizard['email']);
    }

    public function assertEmailAvailable(string $email): void
    {
        if (Registration::emailIsRegistered($email)) {
            throw ValidationException::withMessages([
                'email' => Registration::EMAIL_TAKEN_MESSAGE,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $wizard
     */
    public function submit(array $wizard, string $submissionToken): Registration
    {
        $existing = Registration::query()->where('submission_token', $submissionToken)->first();

        if ($existing) {
            return $existing;
        }

        $this->assertEmailAvailable((string) $wizard['email']);

        try {
            $registration = DB::transaction(function () use ($wizard, $submissionToken) {
                $this->capacity->assertAcceptingApplications((string) $wizard['entry_level']);

                $photoPath = $this->promoteTempFile($wizard['photo_path'], 'photos');
                $proofPath = $this->promoteTempFile($wizard['payment_proof_path'], 'proofs');

                return Registration::create([
                    'has_tournament_experience' => $wizard['has_tournament_experience'],
                    'entry_level' => $wizard['entry_level'],
                    'last_name' => $wizard['last_name'],
                    'first_name' => $wizard['first_name'],
                    'middle_initial' => $wizard['middle_initial'] ?? null,
                    'contact_number' => $wizard['contact_number'],
                    'address' => $wizard['address'],
                    'email' => Registration::normalizeEmail($wizard['email'] ?? null),
                    'photo_path' => $photoPath,
                    'payment_proof_path' => $proofPath,
                    'payment_status' => PaymentStatus::Pending,
                    'registration_status' => RegistrationStatus::Pending,
                    'slot_status' => SlotStatus::PendingVerification,
                    'waiting_list_position' => null,
                    'confirmed_at' => null,
                    'submission_token' => $submissionToken,
                    'privacy_consent' => true,
                    'privacy_consent_at' => now(),
                    'privacy_notice_version' => (string) config('tournament.privacy_notice_version'),
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'email' => Registration::EMAIL_TAKEN_MESSAGE,
            ]);
        }

        $registration = $registration->refresh();
        $this->notifyParticipant($registration);

        return $registration;
    }

    private function notifyParticipant(Registration $registration): void
    {
        try {
            Mail::to($registration->email)->send(new RegistrationReceived($registration));
        } catch (\Throwable $exception) {
            Log::error('Failed to send registration email.', [
                'registration_id' => $registration->id,
                'email' => $registration->email,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function promoteTempFile(string $tempPath, string $folder): string
    {
        $extension = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));
        $destination = 'registrations/'.$folder.'/'.Str::uuid().'.'.$extension;

        Storage::disk('local')->move($tempPath, $destination);

        return $destination;
    }
}

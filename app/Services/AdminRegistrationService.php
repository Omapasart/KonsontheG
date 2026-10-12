<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\CategoryTransferRequested;
use App\Mail\PaymentRejected;
use App\Mail\PaymentVerified;
use App\Mail\RegistrationApproved;
use App\Mail\RegistrationReceived;
use App\Mail\RegistrationRejected;
use App\Mail\RegistrationWithdrawn;
use App\Mail\WaitingListPlaced;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use App\Support\AppMail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminRegistrationService
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDetails(Registration $registration, User $admin, array $attributes, ?UploadedFile $photo = null): Registration
    {
        return DB::transaction(function () use ($registration, $admin, $attributes, $photo) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $slotStatus = $locked->slot_status->value;
            $registrationStatus = $locked->registration_status->value;
            $paymentStatus = $locked->payment_status->value;
            $entryLevel = $locked->entry_level->value;

            $updates = [
                'last_name' => $attributes['last_name'],
                'first_name' => $attributes['first_name'],
                'middle_initial' => filled($attributes['middle_initial'] ?? null) ? $attributes['middle_initial'] : null,
                'contact_number' => $attributes['contact_number'],
                'address' => $attributes['address'],
                'email' => Registration::normalizeEmail($attributes['email'] ?? null),
            ];

            $changes = [];

            foreach ($updates as $field => $value) {
                $current = $locked->{$field};
                $currentValue = $current === null ? '' : (string) $current;
                $nextValue = $value === null ? '' : (string) $value;

                if ($currentValue !== $nextValue) {
                    $changes[] = str_replace('_', ' ', $field).': '.$currentValue.' → '.$nextValue;
                }
            }

            if ($photo instanceof UploadedFile) {
                $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
                $destination = 'registrations/photos/'.Str::uuid().'.'.$extension;
                Storage::disk('local')->putFileAs('registrations/photos', $photo, basename($destination));

                $oldPhoto = $locked->photo_path;
                $updates['photo_path'] = $destination;
                $changes[] = 'photo replaced';

                if (filled($oldPhoto) && $oldPhoto !== $destination && Storage::disk('local')->exists($oldPhoto)) {
                    Storage::disk('local')->delete($oldPhoto);
                }
            }

            if ($changes === []) {
                return $locked;
            }

            try {
                $locked->update($updates);
            } catch (UniqueConstraintViolationException $exception) {
                throw ValidationException::withMessages([
                    'email' => Registration::EMAIL_TAKEN_MESSAGE,
                ]);
            }

            $updated = $locked->fresh();

            if (
                $updated->slot_status->value !== $slotStatus
                || $updated->registration_status->value !== $registrationStatus
                || $updated->payment_status->value !== $paymentStatus
                || $updated->entry_level->value !== $entryLevel
            ) {
                throw ValidationException::withMessages([
                    'registration' => 'Applicant details could not be saved without changing slot or status records.',
                ]);
            }

            $this->log($admin, $updated, 'updated_details', null, null, implode('; ', $changes));

            return $updated;
        });
    }

    public function deleteApplicant(Registration $registration, User $admin): ?Registration
    {
        $promoted = DB::transaction(function () use ($registration, $admin) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $number = $locked->registration_number;
            $name = $locked->fullName();
            $oldSlot = $locked->slot_status->value;
            $photo = $locked->photo_path;
            $proof = $locked->payment_proof_path;

            $promoted = $this->capacity->vacateIfOccupying($locked);
            $locked->refresh();

            $this->log(
                $admin,
                $locked,
                'deleted',
                $oldSlot,
                null,
                sprintf('Deleted %s %s.', $number, $name)
            );

            $locked->delete();

            foreach ([$photo, $proof] as $path) {
                if (filled($path) && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }

            return $promoted;
        });

        if ($promoted) {
            $this->capacity->notifyPromoted($promoted);
        }

        return $promoted;
    }

    public function approve(Registration $registration, User $admin): Registration
    {
        $approved = DB::transaction(function () use ($registration, $admin) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $oldRegistration = $locked->registration_status->value;
            $oldSlot = $locked->slot_status->value;

            if ($locked->registration_status === RegistrationStatus::Approved && $locked->slot_status->occupiesSlot()) {
                throw ValidationException::withMessages([
                    'registration_status' => 'This registration has already been verified.',
                ]);
            }

            $assignment = $this->capacity->assignOnVerification($locked);
            $locked->refresh();

            $locked->update([
                'registration_status' => RegistrationStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            $this->log($admin, $locked->fresh(), 'approved', $oldRegistration, RegistrationStatus::Approved->value);
            $this->log(
                $admin,
                $locked->fresh(),
                'slot_assigned',
                $oldSlot,
                $assignment['slot_status']->value,
                sprintf(
                    'Verified application and assigned slot status %s in %s.',
                    $assignment['slot_status']->label(),
                    $locked->entry_level->label()
                )
            );

            return $locked->fresh();
        });

        if ($approved->slot_status === SlotStatus::Waiting) {
            $this->mail($approved->email, new WaitingListPlaced($approved));
        } else {
            $this->mail($approved->email, new RegistrationApproved($approved));
        }

        return $approved;
    }

    public function reject(Registration $registration, User $admin, string $reason): void
    {
        $promoted = DB::transaction(function () use ($registration, $admin, $reason) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $old = $locked->registration_status->value;
            $promoted = $this->capacity->vacateIfOccupying($locked);

            $locked->refresh()->update([
                'registration_status' => RegistrationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejected_at' => now(),
                'approved_at' => null,
            ]);

            $this->log($admin, $locked->fresh(), 'rejected', $old, RegistrationStatus::Rejected->value, $reason);

            if ($promoted) {
                $this->log(
                    $admin,
                    $promoted,
                    'promoted',
                    SlotStatus::Waiting->value,
                    SlotStatus::Confirmed->value,
                    sprintf('Auto-promoted after rejection of %s %s.', $locked->registration_number, $locked->fullName())
                );
            }

            return $promoted;
        });

        $this->mail($registration->fresh()->email, new RegistrationRejected($registration->fresh()));

        if ($promoted) {
            $this->capacity->notifyPromoted($promoted);
        }
    }

    public function verifyPayment(Registration $registration, User $admin): bool
    {
        $verified = DB::transaction(function () use ($registration, $admin) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->assertPaymentNotVerified($locked);

            $old = $locked->payment_status->value;

            $locked->update([
                'payment_status' => PaymentStatus::Verified,
                'payment_rejection_reason' => null,
                'payment_reviewed_by' => $admin->id,
                'payment_reviewed_at' => now(),
            ]);

            $this->log($admin, $locked->fresh(), 'payment_verified', $old, PaymentStatus::Verified->value);

            return $locked->fresh();
        });

        return $this->mail($verified->email, new PaymentVerified($verified));
    }

    public function rejectPayment(Registration $registration, User $admin, ?string $reason): bool
    {
        $rejected = DB::transaction(function () use ($registration, $admin, $reason) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->assertPaymentNotVerified($locked);

            $old = $locked->payment_status->value;

            $locked->update([
                'payment_status' => PaymentStatus::Rejected,
                'payment_rejection_reason' => $reason,
                'payment_reviewed_by' => $admin->id,
                'payment_reviewed_at' => now(),
            ]);

            $this->log($admin, $locked->fresh(), 'payment_rejected', $old, PaymentStatus::Rejected->value, $reason);

            return $locked->fresh();
        });

        return $this->mail($rejected->email, new PaymentRejected($rejected));
    }

    private function assertPaymentNotVerified(Registration $registration): void
    {
        if ($registration->payment_status === PaymentStatus::Verified) {
            throw ValidationException::withMessages([
                'payment_status' => 'This payment has already been verified and cannot be changed.',
            ]);
        }
    }

    public function withdraw(Registration $registration, User $admin): void
    {
        $promoted = DB::transaction(function () use ($registration, $admin) {
            $oldSlot = $registration->slot_status->value;
            $promoted = $this->capacity->withdraw($registration);
            $withdrawn = $registration->fresh();

            $remarks = sprintf(
                'Previous slot status: %s. New slot status: withdrawn. Registration status unchanged: %s.',
                strtoupper($oldSlot),
                $withdrawn->registration_status->value
            );

            if ($promoted) {
                $remarks .= sprintf(
                    ' Waiting list applicant promoted: %s %s (%s).',
                    $promoted->registration_number,
                    $promoted->fullName(),
                    $promoted->entry_level->label()
                );
            }

            $this->log($admin, $withdrawn, 'withdrawn', $oldSlot, SlotStatus::Withdrawn->value, $remarks);

            if ($promoted) {
                $this->log(
                    $admin,
                    $promoted,
                    'promoted',
                    SlotStatus::Waiting->value,
                    SlotStatus::Confirmed->value,
                    sprintf('Auto-promoted after withdrawal of %s %s.', $withdrawn->registration_number, $withdrawn->fullName())
                );
            }

            return $promoted;
        });

        $this->mail($registration->fresh()->email, new RegistrationWithdrawn($registration->fresh()));

        if ($promoted) {
            $this->capacity->notifyPromoted($promoted);
        }
    }

    public function sendConfirmationEmail(Registration $registration, User $admin, string $type): bool
    {
        $label = $this->emailTypeLabel($type);
        $mailable = $this->confirmationMailable($registration, $type);
        $sent = $this->mail($registration->email, $mailable);

        $this->log(
            $admin,
            $registration,
            $this->emailLogAction($type),
            null,
            $sent ? 'sent' : 'failed',
            'Manual '.$label.' emailed to '.$registration->email.'.'
        );

        if (! $sent) {
            throw ValidationException::withMessages([
                'email' => 'The '.$label.' email for '.$registration->registration_number.' could not be sent.',
            ]);
        }

        return true;
    }

    /**
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function sendConfirmationEmails(User $admin, string $type): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->confirmationRecipients($type)->with('pendingTransferRequest')->cursor() as $registration) {
            try {
                $this->sendConfirmationEmail($registration, $admin, $type);
                $sent++;
            } catch (ValidationException $exception) {
                $failed++;
            }
        }

        return compact('sent', 'failed', 'skipped');
    }

    /**
     * @param  list<int>  $ids
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function sendConfirmationEmailsTo(User $admin, string $type, array $ids): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $registrations = Registration::query()
            ->with('pendingTransferRequest')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        foreach ($registrations as $registration) {
            try {
                $this->confirmationMailable($registration, $type);
            } catch (ValidationException $exception) {
                $skipped++;

                continue;
            }

            try {
                $this->sendConfirmationEmail($registration, $admin, $type);
                $sent++;
            } catch (ValidationException $exception) {
                $failed++;
            }
        }

        return compact('sent', 'failed', 'skipped');
    }

    public function confirmationRecipientCount(string $type): int
    {
        return $this->confirmationRecipients($type)->count();
    }

    public function emailTypeLabel(string $type): string
    {
        return match ($type) {
            'application' => 'application confirmation',
            'payment' => 'payment confirmation',
            'slot' => 'slot confirmation',
            'transfer' => 'category transfer request',
            default => throw ValidationException::withMessages([
                'type' => 'Unknown confirmation email type.',
            ]),
        };
    }

    private function emailLogAction(string $type): string
    {
        return $type === 'transfer' ? 'emailed_transfer_request' : 'emailed_'.$type.'_confirmation';
    }

    private function confirmationRecipients(string $type)
    {
        return match ($type) {
            'application' => Registration::query()->whereNotNull('email'),
            'payment' => Registration::query()
                ->where('payment_status', PaymentStatus::Verified)
                ->whereNotNull('email'),
            'slot' => Registration::query()
                ->whereIn('slot_status', [SlotStatus::Confirmed, SlotStatus::Waiting])
                ->whereNotNull('email'),
            'transfer' => Registration::query()
                ->with('pendingTransferRequest')
                ->whereNotNull('email')
                ->whereHas('pendingTransferRequest'),
            default => throw ValidationException::withMessages([
                'type' => 'Unknown confirmation email type.',
            ]),
        };
    }

    private function confirmationMailable(Registration $registration, string $type): object
    {
        return match ($type) {
            'application' => new RegistrationReceived($registration),
            'payment' => $registration->payment_status === PaymentStatus::Verified
                ? new PaymentVerified($registration)
                : throw ValidationException::withMessages([
                    'payment_status' => 'Payment confirmation can only be sent after the payment is verified.',
                ]),
            'slot' => match ($registration->slot_status) {
                SlotStatus::Confirmed => new RegistrationApproved($registration),
                SlotStatus::Waiting => new WaitingListPlaced($registration),
                default => throw ValidationException::withMessages([
                    'slot_status' => 'Slot confirmation can only be sent to confirmed or waiting-list applicants.',
                ]),
            },
            'transfer' => $this->transferRequestMailable($registration),
            default => throw ValidationException::withMessages([
                'type' => 'Unknown confirmation email type.',
            ]),
        };
    }

    private function transferRequestMailable(Registration $registration): CategoryTransferRequested
    {
        $pending = $registration->pendingTransferRequest;

        if (! $pending) {
            throw ValidationException::withMessages([
                'transfer' => 'A category transfer request email can only be sent while a transfer is awaiting the applicant\'s response.',
            ]);
        }

        return new CategoryTransferRequested($pending->loadMissing('registration'));
    }

    public function promote(Registration $registration, User $admin): void
    {
        $old = $registration->slot_status->value;
        $promoted = $this->capacity->promote($registration);

        $this->log($admin, $promoted, 'promoted', $old, SlotStatus::Confirmed->value);
        $this->capacity->notifyPromoted($promoted);
    }

    private function log(User $admin, Registration $registration, string $action, ?string $old, ?string $new, ?string $remarks = null): void
    {
        AdminActivityLog::create([
            'admin_user_id' => $admin->id,
            'registration_id' => $registration->id,
            'action' => $action,
            'old_status' => $old,
            'new_status' => $new,
            'remarks' => $remarks,
        ]);
    }

    private function mail(string $email, object $mailable): bool
    {
        return AppMail::send($email, $mailable);
    }
}

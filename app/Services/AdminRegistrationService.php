<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\PaymentRejected;
use App\Mail\PaymentVerified;
use App\Mail\RegistrationApproved;
use App\Mail\RegistrationRejected;
use App\Mail\RegistrationWithdrawn;
use App\Mail\WaitingListPlaced;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use App\Support\AppMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminRegistrationService
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

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

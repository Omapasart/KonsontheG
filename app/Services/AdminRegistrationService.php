<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Mail\PaymentRejected;
use App\Mail\PaymentVerified;
use App\Mail\RegistrationApproved;
use App\Mail\RegistrationRejected;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminRegistrationService
{
    public function approve(Registration $registration, User $admin): void
    {
        $old = $registration->registration_status->value;

        DB::transaction(function () use ($registration, $admin, $old) {
            $registration->update([
                'registration_status' => RegistrationStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            $this->log($admin, $registration, 'approved', $old, RegistrationStatus::Approved->value);
        });

        $this->mail($registration->email, new RegistrationApproved($registration->fresh()));
    }

    public function reject(Registration $registration, User $admin, string $reason): void
    {
        $old = $registration->registration_status->value;

        DB::transaction(function () use ($registration, $admin, $old, $reason) {
            $registration->update([
                'registration_status' => RegistrationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejected_at' => now(),
                'approved_at' => null,
            ]);

            $this->log($admin, $registration, 'rejected', $old, RegistrationStatus::Rejected->value, $reason);
        });

        $this->mail($registration->email, new RegistrationRejected($registration->fresh()));
    }

    public function verifyPayment(Registration $registration, User $admin): void
    {
        $old = $registration->payment_status->value;

        DB::transaction(function () use ($registration, $admin, $old) {
            $registration->update([
                'payment_status' => PaymentStatus::Verified,
                'payment_rejection_reason' => null,
                'payment_reviewed_by' => $admin->id,
                'payment_reviewed_at' => now(),
            ]);

            $this->log($admin, $registration, 'payment_verified', $old, PaymentStatus::Verified->value);
        });

        $this->mail($registration->email, new PaymentVerified($registration->fresh()));
    }

    public function rejectPayment(Registration $registration, User $admin, ?string $reason): void
    {
        $old = $registration->payment_status->value;

        DB::transaction(function () use ($registration, $admin, $old, $reason) {
            $registration->update([
                'payment_status' => PaymentStatus::Rejected,
                'payment_rejection_reason' => $reason,
                'payment_reviewed_by' => $admin->id,
                'payment_reviewed_at' => now(),
            ]);

            $this->log($admin, $registration, 'payment_rejected', $old, PaymentStatus::Rejected->value, $reason);
        });

        $this->mail($registration->email, new PaymentRejected($registration->fresh()));
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

    private function mail(string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::error('Failed to send admin status email.', [
                'email' => $email,
                'mailable' => $mailable::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

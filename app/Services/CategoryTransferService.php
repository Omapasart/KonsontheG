<?php

namespace App\Services;

use App\Enums\CategoryTransferResponse;
use App\Enums\CategoryTransferStatus;
use App\Enums\EntryLevel;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\CategoryTransferConfirmed;
use App\Mail\CategoryTransferDeclined;
use App\Mail\CategoryTransferRequested;
use App\Mail\CategoryTransferUnavailable;
use App\Models\AdminActivityLog;
use App\Models\CategoryTransferRequest;
use App\Models\Registration;
use App\Models\User;
use App\Support\AppMail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryTransferService
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

    public function request(Registration $registration, User $admin, EntryLevel|string $target): CategoryTransferRequest
    {
        $request = DB::transaction(function () use ($registration, $admin, $target) {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $targetLevel = $target instanceof EntryLevel ? $target : EntryLevel::from($target);

            $hasPending = CategoryTransferRequest::query()
                ->where('registration_id', $locked->id)
                ->where('status', CategoryTransferStatus::Pending)
                ->lockForUpdate()
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages([
                    'requested_category' => 'A category transfer request is currently awaiting the applicant\'s response.',
                ]);
            }

            if ($locked->slot_status === SlotStatus::Withdrawn) {
                throw ValidationException::withMessages([
                    'requested_category' => 'A withdrawn applicant cannot be transferred to another category.',
                ]);
            }

            if (! in_array($targetLevel, $locked->entry_level->higherLevels(), true)) {
                throw ValidationException::withMessages([
                    'requested_category' => 'Category transfers may only move an applicant to a higher category.',
                ]);
            }

            $transfer = CategoryTransferRequest::create([
                'registration_id' => $locked->id,
                'current_category' => $locked->entry_level,
                'requested_category' => $targetLevel,
                'status' => CategoryTransferStatus::Pending,
                'requested_by' => $admin->id,
                'requested_at' => now(),
                'token' => Str::random(64),
            ]);

            $this->log(
                $admin,
                $locked,
                'category_transfer_requested',
                $locked->entry_level->value,
                $targetLevel->value,
                sprintf(
                    'Admin requested category transfer: %s. %s → %s.',
                    $locked->fullName(),
                    $locked->entry_level->label(),
                    $targetLevel->label()
                )
            );

            return $transfer;
        });

        $this->mail($registration->email, new CategoryTransferRequested($request->fresh(['registration'])));

        return $request->fresh(['registration', 'requestedBy']);
    }

    public function accept(CategoryTransferRequest $transfer): array
    {
        try {
            $result = DB::transaction(function () use ($transfer) {
                $transfer = CategoryTransferRequest::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
                $this->assertPending($transfer);

                $registration = Registration::query()->whereKey($transfer->registration_id)->lockForUpdate()->firstOrFail();

                if ($registration->entry_level !== $transfer->current_category) {
                    throw ValidationException::withMessages([
                        'transfer' => 'This transfer request is no longer valid for the applicant\'s current category.',
                    ]);
                }

                $moved = $this->capacity->transferTo($registration, $transfer->requested_category);
                $registration = $registration->fresh();

                $transfer->update([
                    'status' => CategoryTransferStatus::Accepted,
                    'applicant_response' => CategoryTransferResponse::Accepted,
                    'responded_at' => now(),
                ]);

                $admin = $transfer->requestedBy;
                $this->log(
                    $admin,
                    $registration,
                    'category_transfer_accepted',
                    $transfer->current_category->value,
                    $transfer->requested_category->value,
                    sprintf(
                        'Applicant accepted category transfer: %s → %s. Changed By: Applicant. Slot status: %s.',
                        $transfer->current_category->label(),
                        $transfer->requested_category->label(),
                        $registration->slotLabel()
                    )
                );

                if ($moved['promoted']) {
                    $this->log(
                        $admin,
                        $moved['promoted'],
                        'promoted',
                        SlotStatus::Waiting->value,
                        SlotStatus::Confirmed->value,
                        sprintf(
                            'Auto-promoted after category transfer of %s %s.',
                            $registration->registration_number,
                            $registration->fullName()
                        )
                    );
                }

                return [
                    'registration' => $registration,
                    'transfer' => $transfer->fresh(),
                    'promoted' => $moved['promoted'],
                    'waiting' => $registration->slot_status === SlotStatus::Waiting,
                ];
            });
        } catch (ValidationException $exception) {
            if (($exception->errors()['entry_level'][0] ?? null) && str_contains($exception->errors()['entry_level'][0], 'currently full')) {
                $this->notifyAdminUnavailable($transfer->fresh(['registration', 'requestedBy']));
            }

            throw $exception;
        }

        $this->mail($result['registration']->email, new CategoryTransferConfirmed(
            $result['registration'],
            $result['transfer']
        ));

        if ($result['promoted']) {
            $this->capacity->notifyPromoted($result['promoted']);
        }

        return $result;
    }

    public function decline(CategoryTransferRequest $transfer): CategoryTransferRequest
    {
        $result = DB::transaction(function () use ($transfer) {
            $transfer = CategoryTransferRequest::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $this->assertPending($transfer);

            $registration = Registration::query()->whereKey($transfer->registration_id)->lockForUpdate()->firstOrFail();
            $previousStatus = $registration->registration_status->value;
            $reason = sprintf(
                'Applicant declined the proposed category transfer from %s to %s. Registration rejected. Original category remains %s.',
                $transfer->current_category->label(),
                $transfer->requested_category->label(),
                $registration->entry_level->label()
            );

            $transfer->update([
                'status' => CategoryTransferStatus::Declined,
                'applicant_response' => CategoryTransferResponse::Declined,
                'responded_at' => now(),
            ]);

            $registration->update([
                'registration_status' => RegistrationStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'approved_at' => null,
            ]);

            $promoted = $this->capacity->releaseSlot($registration);
            $registration = $registration->fresh();

            $this->log(
                $transfer->requestedBy,
                $registration,
                'category_transfer_declined',
                $transfer->current_category->value,
                $transfer->requested_category->value,
                $reason
            );
            $this->log(
                $transfer->requestedBy,
                $registration,
                'rejected',
                $previousStatus,
                RegistrationStatus::Rejected->value,
                $reason
            );

            if ($promoted) {
                $this->log(
                    $transfer->requestedBy,
                    $promoted,
                    'promoted',
                    SlotStatus::Waiting->value,
                    SlotStatus::Confirmed->value,
                    sprintf(
                        'Auto-promoted after %s declined a category transfer and the registration was rejected.',
                        $registration->registration_number
                    )
                );
            }

            return [
                'transfer' => $transfer->fresh(['registration']),
                'promoted' => $promoted,
            ];
        });

        $this->mail($result['transfer']->registration->email, new CategoryTransferDeclined($result['transfer']));

        if ($result['promoted']) {
            $this->capacity->notifyPromoted($result['promoted']);
        }

        return $result['transfer'];
    }

    private function assertPending(CategoryTransferRequest $transfer): void
    {
        if ($transfer->status !== CategoryTransferStatus::Pending) {
            throw ValidationException::withMessages([
                'transfer' => 'This category transfer request has already been answered.',
            ]);
        }
    }

    private function notifyAdminUnavailable(CategoryTransferRequest $transfer): void
    {
        $admin = $transfer->requestedBy;

        if (! $admin?->email) {
            return;
        }

        $this->mail($admin->email, new CategoryTransferUnavailable($transfer));
    }

    private function log(?User $admin, Registration $registration, string $action, ?string $old, ?string $new, ?string $remarks = null): void
    {
        if (! $admin) {
            return;
        }

        AdminActivityLog::create([
            'admin_user_id' => $admin->id,
            'registration_id' => $registration->id,
            'action' => $action,
            'old_status' => $old,
            'new_status' => $new,
            'remarks' => $remarks,
        ]);
    }

    private function mail(string $email, Mailable $mailable): void
    {
        AppMail::send($email, $mailable);
    }
}

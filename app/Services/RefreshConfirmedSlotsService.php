<?php

namespace App\Services;

use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\WaitingListTransferred;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use App\Support\AppMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RefreshConfirmedSlotsService
{
    public function __construct(private readonly CategoryCapacityService $capacity) {}

    /**
     * @return array{deleted: int, total_pending: int, categories: array<string, array<string, mixed>>, pending: list<Registration>, promoted: list<Registration>}
     */
    public function refresh(User $admin): array
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->refresh($admin));
        }

        foreach (EntryLevel::cases() as $level) {
            $this->capacity->lockCategory($level);
        }

        $deleted = $this->deleteNonWaitingApplicants();
        $pending = [];
        $categories = [];

        foreach (EntryLevel::cases() as $level) {
            $moved = $this->moveWaitingListToPending($level, $admin);
            $after = $this->capacity->statusFor($level);
            $pending = array_merge($pending, $moved);
            $categories[$level->value] = [
                'level' => $level,
                'pending' => count($moved),
                'confirmed' => $after['confirmed'],
                'waiting' => $after['waiting'],
                'regular_remaining' => $after['regular_remaining'],
                'capacity' => $after['capacity'],
            ];
        }

        AdminActivityLog::query()->create([
            'admin_user_id' => $admin->id,
            'registration_id' => null,
            'action' => 'registrations_reset',
            'old_status' => null,
            'new_status' => null,
            'remarks' => sprintf(
                'Deleted %d non-waiting applicant%s and moved %d waiting-list applicant%s to pending slot and payment verification. None were given a confirmed slot. Transfer emails were sent to moved applicants.',
                $deleted,
                $deleted === 1 ? '' : 's',
                count($pending),
                count($pending) === 1 ? '' : 's'
            ),
        ]);

        return [
            'deleted' => $deleted,
            'total_pending' => count($pending),
            'categories' => $categories,
            'pending' => $pending,
            'promoted' => [],
        ];
    }

    public function summaryMessage(array $result): string
    {
        $parts = [];

        foreach ($result['categories'] as $row) {
            $parts[] = sprintf(
                '%s: %d pending, %d/%d confirmed',
                $row['level']->label(),
                $row['pending'],
                $row['confirmed'],
                $row['capacity']
            );
        }

        return sprintf(
            'Deleted %d applicant%s. Moved %d waiting-list applicant%s to pending slot and payment verification. No slots were auto-confirmed. Transfer emails were sent. %s',
            $result['deleted'],
            (int) $result['deleted'] === 1 ? '' : 's',
            $result['total_pending'],
            (int) $result['total_pending'] === 1 ? '' : 's',
            implode(' ', $parts)
        );
    }

    private function deleteNonWaitingApplicants(): int
    {
        $toDelete = Registration::query()
            ->where('slot_status', '!=', SlotStatus::Waiting)
            ->lockForUpdate()
            ->get();

        $disk = Storage::disk('local');

        foreach ($toDelete as $applicant) {
            foreach (['photo_path', 'payment_proof_path'] as $field) {
                $path = $applicant->{$field};

                if (is_string($path) && $path !== '' && $disk->exists($path)) {
                    $disk->delete($path);
                }
            }

            $applicant->delete();
        }

        return $toDelete->count();
    }

    /**
     * @return list<Registration>
     */
    private function moveWaitingListToPending(EntryLevel $level, User $admin): array
    {
        $waiters = Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Waiting)
            ->where('registration_status', '!=', RegistrationStatus::Rejected)
            ->orderBy('waiting_list_position')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $moved = [];

        foreach ($waiters as $waiter) {
            $old = $waiter->slot_status->value;

            $waiter->update([
                'slot_status' => SlotStatus::PendingVerification,
                'registration_status' => RegistrationStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'payment_reviewed_by' => null,
                'payment_reviewed_at' => null,
                'payment_rejection_reason' => null,
                'waiting_list_position' => null,
                'confirmed_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $fresh = $waiter->fresh();

            AdminActivityLog::create([
                'admin_user_id' => $admin->id,
                'registration_id' => $fresh->id,
                'action' => 'moved_to_pending',
                'old_status' => $old,
                'new_status' => SlotStatus::PendingVerification->value,
                'remarks' => sprintf(
                    'Waiting-list applicant moved to pending slot and payment verification for %s. Slot was not auto-confirmed. Admin must verify payment again. Transfer email sent after refresh.',
                    $level->label()
                ),
            ]);

            $moved[] = $fresh;
        }

        return $moved;
    }

    /**
     * @param  list<Registration>  $applicants
     */
    public function notifyTransferred(array $applicants): void
    {
        foreach ($applicants as $applicant) {
            if ($applicant->slot_status === SlotStatus::Confirmed) {
                continue;
            }

            AppMail::send($applicant->email, new WaitingListTransferred($applicant));
        }
    }
}

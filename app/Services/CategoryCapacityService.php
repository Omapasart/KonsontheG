<?php

namespace App\Services;

use App\Enums\EntryLevel;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\SlotConfirmed;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryCapacityService
{
    /**
     * @return array{capacity: int, waiting_capacity: int}
     */
    public function limits(EntryLevel|string $level): array
    {
        $key = $level instanceof EntryLevel ? $level->value : $level;
        $config = config('tournament.categories.'.$key);

        if (! is_array($config)) {
            throw new \InvalidArgumentException('Unknown tournament category: '.$key);
        }

        return [
            'capacity' => (int) $config['capacity'],
            'waiting_capacity' => (int) $config['waiting_capacity'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function snapshot(): array
    {
        $snapshot = [];

        foreach (EntryLevel::cases() as $level) {
            $snapshot[$level->value] = $this->statusFor($level);
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function statusFor(EntryLevel|string $level): array
    {
        $enum = $level instanceof EntryLevel ? $level : EntryLevel::from($level);
        $limits = $this->limits($enum);
        $confirmed = $this->countConfirmed($enum);
        $waiting = $this->countWaiting($enum);
        $regularRemaining = max(0, $limits['capacity'] - $confirmed);
        $waitingRemaining = max(0, $limits['waiting_capacity'] - $waiting);
        $isFull = $regularRemaining === 0 && $waitingRemaining === 0;
        $isWaiting = $regularRemaining === 0 && $waitingRemaining > 0;
        $availability = $isFull ? 'full' : ($isWaiting ? 'waiting' : 'open');

        return [
            'level' => $enum,
            'capacity' => $limits['capacity'],
            'waiting_capacity' => $limits['waiting_capacity'],
            'confirmed' => $confirmed,
            'waiting' => $waiting,
            'regular_remaining' => $regularRemaining,
            'waiting_remaining' => $waitingRemaining,
            'is_full' => $isFull,
            'is_waiting' => $isWaiting,
            'is_open' => $regularRemaining > 0,
            'availability' => $availability,
            'availability_label' => match ($availability) {
                'full' => 'FULL',
                'waiting' => 'WAITING LIST ONLY',
                default => 'OPEN',
            },
        ];
    }

    public function registrationIsClosed(): bool
    {
        foreach (EntryLevel::cases() as $level) {
            if (! $this->statusFor($level)['is_full']) {
                return false;
            }
        }

        return true;
    }

    public function closedMessage(): string
    {
        return 'All tournament categories are fully booked, including their waiting lists. Registration is now closed. Thank you for your interest in KONSONTHEGO Tournament.';
    }

    public function fullMessage(EntryLevel|string $level): string
    {
        $status = $this->statusFor($level);
        $label = strtoupper($status['level']->label());

        return $label.' CATEGORY IS FULL. The '.$status['capacity'].' tournament slots and '.$status['waiting_capacity'].' waiting-list slots for '.$status['level']->label().' have already been filled.';
    }

    public function assertAcceptingApplications(EntryLevel|string $level): void
    {
        if (DB::transactionLevel() === 0) {
            DB::transaction(fn () => $this->assertAcceptingApplications($level));

            return;
        }

        $enum = $level instanceof EntryLevel ? $level : EntryLevel::from($level);
        $this->lockCategory($enum);

        if ($this->statusFor($enum)['is_full']) {
            throw ValidationException::withMessages([
                'entry_level' => $this->fullMessage($enum),
            ]);
        }
    }

    /**
     * @return array{slot_status: SlotStatus, waiting_list_position: int|null, confirmed_at: \Illuminate\Support\Carbon|null}
     */
    public function claim(EntryLevel|string $level): array
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->claim($level));
        }

        $enum = $level instanceof EntryLevel ? $level : EntryLevel::from($level);
        $this->lockCategory($enum);
        $status = $this->statusFor($enum);

        if ($status['is_open']) {
            return [
                'slot_status' => SlotStatus::Confirmed,
                'waiting_list_position' => null,
                'confirmed_at' => now(),
            ];
        }

        if ($status['is_waiting']) {
            return [
                'slot_status' => SlotStatus::Waiting,
                'waiting_list_position' => $status['waiting'] + 1,
                'confirmed_at' => null,
            ];
        }

        throw ValidationException::withMessages([
            'entry_level' => $this->fullMessage($enum),
        ]);
    }

    /**
     * @return array{slot_status: SlotStatus, waiting_list_position: int|null, confirmed_at: \Illuminate\Support\Carbon|null}
     */
    public function assignOnVerification(Registration $registration): array
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->assignOnVerification($registration));
        }

        $this->lockCategory($registration->entry_level);
        $registration = Registration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();

        if ($registration->slot_status === SlotStatus::Withdrawn) {
            throw ValidationException::withMessages([
                'slot_status' => 'A withdrawn applicant cannot be assigned a tournament slot.',
            ]);
        }

        if ($registration->registration_status === RegistrationStatus::Rejected) {
            throw ValidationException::withMessages([
                'registration_status' => 'A rejected application cannot be assigned a tournament slot.',
            ]);
        }

        if ($registration->slot_status->occupiesSlot()) {
            throw ValidationException::withMessages([
                'slot_status' => 'This applicant already has an assigned tournament slot.',
            ]);
        }

        $assignment = $this->claim($registration->entry_level);

        $registration->update([
            'slot_status' => $assignment['slot_status'],
            'waiting_list_position' => $assignment['waiting_list_position'],
            'confirmed_at' => $assignment['confirmed_at'],
        ]);

        return $assignment;
    }

    public function vacateIfOccupying(Registration $registration): ?Registration
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->vacateIfOccupying($registration));
        }

        $this->lockCategory($registration->entry_level);
        $registration->refresh();

        if (! $registration->slot_status->occupiesSlot()) {
            return null;
        }

        $wasConfirmed = $registration->slot_status === SlotStatus::Confirmed;
        $level = $registration->entry_level;

        $registration->update([
            'slot_status' => SlotStatus::PendingVerification,
            'waiting_list_position' => null,
            'confirmed_at' => null,
        ]);

        $promoted = $wasConfirmed ? $this->promoteFirstWaiting($level) : null;
        $this->recalculateWaitingPositions($level);

        return $promoted;
    }

    public function withdraw(Registration $registration): ?Registration
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->withdraw($registration));
        }

        $this->lockCategory($registration->entry_level);
        $registration->refresh();

        if ($registration->slot_status === SlotStatus::Withdrawn) {
            throw ValidationException::withMessages([
                'slot_status' => 'This applicant has already been withdrawn.',
            ]);
        }

        if ($registration->slot_status !== SlotStatus::Confirmed) {
            throw ValidationException::withMessages([
                'slot_status' => 'Only applicants with a confirmed tournament slot can be withdrawn.',
            ]);
        }

        return $this->releaseSlot($registration);
    }

    public function releaseSlot(Registration $registration): ?Registration
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->releaseSlot($registration));
        }

        $this->lockCategory($registration->entry_level);
        $registration->refresh();

        if ($registration->slot_status === SlotStatus::Withdrawn || ! $registration->slot_status->occupiesSlot()) {
            return null;
        }

        $wasConfirmed = $registration->slot_status === SlotStatus::Confirmed;
        $level = $registration->entry_level;

        $registration->update([
            'slot_status' => SlotStatus::Withdrawn,
            'waiting_list_position' => null,
            'withdrawn_at' => now(),
        ]);

        $promoted = $wasConfirmed ? $this->promoteFirstWaiting($level) : null;
        $this->recalculateWaitingPositions($level);

        return $promoted;
    }

    /**
     * @return array{slot_status: SlotStatus, waiting_list_position: int|null, confirmed_at: \Illuminate\Support\Carbon|null, promoted: ?Registration}
     */
    public function transferTo(Registration $registration, EntryLevel|string $target): array
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->transferTo($registration, $target));
        }

        $targetLevel = $target instanceof EntryLevel ? $target : EntryLevel::from($target);
        $source = $registration->entry_level;

        foreach (collect([$source->value, $targetLevel->value])->unique()->sort()->values() as $key) {
            $this->lockCategory($key);
        }

        $registration->refresh();
        $source = $registration->entry_level;

        if ($registration->slot_status === SlotStatus::Withdrawn) {
            throw ValidationException::withMessages([
                'entry_level' => 'A withdrawn applicant cannot be transferred to another category.',
            ]);
        }

        if (! $registration->slot_status->occupiesSlot()) {
            $registration->update([
                'entry_level' => $targetLevel,
            ]);

            return [
                'slot_status' => $registration->slot_status,
                'waiting_list_position' => $registration->waiting_list_position,
                'confirmed_at' => $registration->confirmed_at,
                'promoted' => null,
            ];
        }

        $status = $this->statusFor($targetLevel);

        if ($status['is_full']) {
            throw ValidationException::withMessages([
                'entry_level' => 'The requested category is currently full, including its waiting list. Your category cannot be transferred at this time.',
            ]);
        }

        $wasConfirmed = $registration->slot_status === SlotStatus::Confirmed;
        $assignment = $this->claim($targetLevel);

        $registration->update([
            'entry_level' => $targetLevel,
            'slot_status' => $assignment['slot_status'],
            'waiting_list_position' => $assignment['waiting_list_position'],
            'confirmed_at' => $assignment['confirmed_at'],
        ]);

        $promoted = $wasConfirmed ? $this->promoteFirstWaiting($source) : null;
        $this->recalculateWaitingPositions($source);
        $this->recalculateWaitingPositions($targetLevel);

        return [
            'slot_status' => $assignment['slot_status'],
            'waiting_list_position' => $assignment['waiting_list_position'],
            'confirmed_at' => $assignment['confirmed_at'],
            'promoted' => $promoted,
        ];
    }

    public function promote(Registration $registration): Registration
    {
        return DB::transaction(function () use ($registration) {
            $this->lockCategory($registration->entry_level);
            $registration->refresh();

            if ($registration->slot_status !== SlotStatus::Waiting) {
                throw ValidationException::withMessages([
                    'slot_status' => 'Only waiting-list applicants can be promoted.',
                ]);
            }

            $status = $this->statusFor($registration->entry_level);

            if ($status['regular_remaining'] < 1) {
                throw ValidationException::withMessages([
                    'slot_status' => 'No confirmed slot is currently available for this category.',
                ]);
            }

            $this->confirmFromWaiting($registration);
            $this->recalculateWaitingPositions($registration->entry_level);

            return $registration->fresh();
        });
    }

    private function promoteFirstWaiting(EntryLevel $level): ?Registration
    {
        $next = Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Waiting)
            ->orderBy('waiting_list_position')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (! $next) {
            return null;
        }

        $this->confirmFromWaiting($next);

        return $next->fresh();
    }

    public function confirmFromWaiting(Registration $registration): void
    {
        $registration->update([
            'slot_status' => SlotStatus::Confirmed,
            'waiting_list_position' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function recalculateWaitingPositions(EntryLevel|string $level): void
    {
        $waiters = Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Waiting)
            ->orderBy('waiting_list_position')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $position = 1;

        foreach ($waiters as $waiter) {
            if ($waiter->waiting_list_position !== $position) {
                $waiter->update(['waiting_list_position' => $position]);
            }
            $position++;
        }
    }

    public function notifyPromoted(Registration $registration): void
    {
        try {
            \Illuminate\Support\Facades\Mail::to($registration->email)->send(new SlotConfirmed($registration));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Failed to send slot confirmation email.', [
                'registration_id' => $registration->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function lockCategory(EntryLevel|string $level): void
    {
        $key = $level instanceof EntryLevel ? $level->value : $level;

        $lock = DB::table('category_locks')->where('entry_level', $key)->lockForUpdate()->first();

        if ($lock) {
            return;
        }

        DB::table('category_locks')->insertOrIgnore(['entry_level' => $key]);
        DB::table('category_locks')->where('entry_level', $key)->lockForUpdate()->first();
    }

    private function countConfirmed(EntryLevel $level): int
    {
        return Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Confirmed)
            ->where('registration_status', '!=', RegistrationStatus::Rejected)
            ->count();
    }

    private function countWaiting(EntryLevel $level): int
    {
        return Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Waiting)
            ->where('registration_status', '!=', RegistrationStatus::Rejected)
            ->count();
    }
}

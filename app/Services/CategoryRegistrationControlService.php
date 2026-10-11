<?php

namespace App\Services;

use App\Enums\EntryLevel;
use App\Models\AdminActivityLog;
use App\Models\CategoryLock;
use App\Models\User;

class CategoryRegistrationControlService
{
    public function setClosed(EntryLevel $level, bool $closed, User $admin): CategoryLock
    {
        $lock = CategoryLock::query()->firstOrCreate(
            ['entry_level' => $level->value],
            ['registration_closed' => false],
        );

        $wasClosed = (bool) $lock->registration_closed;

        if ($wasClosed === $closed) {
            return $lock;
        }

        $lock->update(['registration_closed' => $closed]);

        AdminActivityLog::create([
            'admin_user_id' => $admin->id,
            'registration_id' => null,
            'action' => $closed ? 'category_closed' : 'category_opened',
            'old_status' => $wasClosed ? 'closed' : 'open',
            'new_status' => $closed ? 'closed' : 'open',
            'remarks' => $level->label().' registration was '.($closed ? 'closed' : 'opened').'. Existing applicants were not changed.',
        ]);

        return $lock->fresh();
    }

    public function setLimits(EntryLevel $level, int $confirmedCapacity, int $waitingCapacity, User $admin): CategoryLock
    {
        $lock = CategoryLock::query()->firstOrCreate(
            ['entry_level' => $level->value],
            ['registration_closed' => false],
        );

        $oldConfirmed = $lock->confirmed_capacity;
        $oldWaiting = $lock->waiting_capacity;

        if ((int) $oldConfirmed === $confirmedCapacity && (int) $oldWaiting === $waitingCapacity) {
            return $lock;
        }

        $lock->update([
            'confirmed_capacity' => $confirmedCapacity,
            'waiting_capacity' => $waitingCapacity,
        ]);

        AdminActivityLog::create([
            'admin_user_id' => $admin->id,
            'registration_id' => null,
            'action' => 'category_limits_updated',
            'old_status' => $oldConfirmed.'/'.$oldWaiting,
            'new_status' => $confirmedCapacity.'/'.$waitingCapacity,
            'remarks' => $level->label().' limits set to '.$confirmedCapacity.' confirmed and '.$waitingCapacity.' waiting. Existing applicants were not changed.',
        ]);

        return $lock->fresh();
    }
}

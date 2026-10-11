<?php

namespace Tests;

use App\Enums\EntryLevel;
use App\Models\CategoryLock;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function writeCategoryLimitsFromConfig(): void
    {
        foreach (EntryLevel::cases() as $level) {
            CategoryLock::query()->updateOrCreate(
                ['entry_level' => $level->value],
                [
                    'confirmed_capacity' => (int) config('tournament.categories.'.$level->value.'.capacity'),
                    'waiting_capacity' => (int) config('tournament.categories.'.$level->value.'.waiting_capacity'),
                ],
            );
        }
    }
}

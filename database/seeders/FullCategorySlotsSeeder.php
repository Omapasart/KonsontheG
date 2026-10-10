<?php

namespace Database\Seeders;

use App\Enums\EntryLevel;
use App\Models\Registration;
use App\Services\CategoryCapacityService;
use Illuminate\Database\Seeder;

class FullCategorySlotsSeeder extends Seeder
{

    /**
     * Fill remaining confirmed and waiting-list slots in every category.
     *
     * Run only for capacity testing. Do not include this in DatabaseSeeder.
     *
     * php artisan db:seed --class=FullCategorySlotsSeeder
     */
    public function run(): void
    {
        $capacity = app(CategoryCapacityService::class);

        foreach (EntryLevel::cases() as $level) {
            $this->fill($level, $capacity);
        }
    }

    private function fill(EntryLevel $level, CategoryCapacityService $capacity): void
    {
        $status = $capacity->statusFor($level);
        $experience = $level === EntryLevel::Beginner ? 'no' : 'yes';

        if ($status['regular_remaining'] > 0) {
            Registration::factory()
                ->count($status['regular_remaining'])
                ->confirmed()
                ->create([
                    'entry_level' => $level,
                    'has_tournament_experience' => $experience,
                ]);
        }

        $status = $capacity->statusFor($level);

        if ($status['waiting_remaining'] > 0) {
            $start = $status['waiting'] + 1;

            for ($position = $start; $position <= $status['waiting_capacity']; $position++) {
                Registration::factory()
                    ->waiting($position)
                    ->create([
                        'entry_level' => $level,
                        'has_tournament_experience' => $experience,
                    ]);
            }
        }

        $filled = $capacity->statusFor($level);

        $this->command?->info(sprintf(
            '%s: %d/%d confirmed, %d/%d waiting%s',
            $level->label(),
            $filled['confirmed'],
            $filled['capacity'],
            $filled['waiting'],
            $filled['waiting_capacity'],
            $filled['is_full'] ? ' — FULL' : ''
        ));
    }
}

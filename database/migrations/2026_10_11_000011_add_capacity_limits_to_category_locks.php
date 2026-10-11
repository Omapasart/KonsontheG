<?php

use App\Enums\EntryLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_locks', function (Blueprint $table) {
            $table->unsignedInteger('confirmed_capacity')->nullable();
            $table->unsignedInteger('waiting_capacity')->nullable();
        });

        foreach (EntryLevel::cases() as $level) {
            $capacityKey = 'TOURNAMENT_'.strtoupper($level->value).'_CAPACITY';
            $waitingKey = 'TOURNAMENT_'.strtoupper($level->value).'_WAITING';
            $defaults = [
                'beginner' => [36, 5],
                'novice' => [48, 5],
                'intermediate' => [36, 5],
            ][$level->value];

            DB::table('category_locks')->updateOrInsert(
                ['entry_level' => $level->value],
                [
                    'confirmed_capacity' => (int) env($capacityKey, $defaults[0]),
                    'waiting_capacity' => (int) env($waitingKey, $defaults[1]),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::table('category_locks', function (Blueprint $table) {
            $table->dropColumn(['confirmed_capacity', 'waiting_capacity']);
        });
    }
};

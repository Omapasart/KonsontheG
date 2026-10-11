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
            $table->boolean('registration_closed')->default(false);
        });

        foreach (EntryLevel::cases() as $level) {
            $envKey = 'TOURNAMENT_'.strtoupper($level->value).'_CLOSED';

            DB::table('category_locks')->updateOrInsert(
                ['entry_level' => $level->value],
                ['registration_closed' => filter_var(env($envKey, false), FILTER_VALIDATE_BOOLEAN)],
            );
        }
    }

    public function down(): void
    {
        Schema::table('category_locks', function (Blueprint $table) {
            $table->dropColumn('registration_closed');
        });
    }
};

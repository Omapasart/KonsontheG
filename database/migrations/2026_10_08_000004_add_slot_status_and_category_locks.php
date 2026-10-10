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
        Schema::create('category_locks', function (Blueprint $table) {
            $table->string('entry_level', 32)->primary();
        });

        foreach (EntryLevel::cases() as $level) {
            DB::table('category_locks')->insert(['entry_level' => $level->value]);
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('slot_status', 32)->default('pending_verification')->after('registration_status');
            $table->unsignedTinyInteger('waiting_list_position')->nullable()->after('slot_status');
            $table->timestamp('confirmed_at')->nullable()->after('waiting_list_position');
            $table->timestamp('withdrawn_at')->nullable()->after('confirmed_at');
            $table->index(['entry_level', 'slot_status']);
        });

        DB::table('registrations')->whereNull('confirmed_at')->update([
            'slot_status' => 'confirmed',
            'confirmed_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['entry_level', 'slot_status']);
            $table->dropColumn(['slot_status', 'waiting_list_position', 'confirmed_at', 'withdrawn_at']);
        });

        Schema::dropIfExists('category_locks');
    }
};

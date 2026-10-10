<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registrations') || ! Schema::hasColumn('registrations', 'facebook')) {
            return;
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('facebook');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('registrations') || Schema::hasColumn('registrations', 'facebook')) {
            return;
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('facebook')->nullable()->after('address');
        });
    }
};

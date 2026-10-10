<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('registration_id')->nullable()->change();
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->foreign('registration_id')
                ->references('id')
                ->on('registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('registration_id')->nullable(false)->change();
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->foreign('registration_id')
                ->references('id')
                ->on('registrations')
                ->cascadeOnDelete();
        });
    }
};

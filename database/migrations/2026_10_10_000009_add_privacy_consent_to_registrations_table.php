<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->boolean('privacy_consent')->default(false)->after('submission_token');
            $table->timestamp('privacy_consent_at')->nullable()->after('privacy_consent');
            $table->string('privacy_notice_version', 32)->nullable()->after('privacy_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['privacy_consent', 'privacy_consent_at', 'privacy_notice_version']);
        });
    }
};

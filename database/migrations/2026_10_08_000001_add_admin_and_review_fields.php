<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('registration_status');
            $table->text('payment_rejection_reason')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->after('payment_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('payment_reviewed_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_reviewed_at')->nullable()->after('payment_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('payment_reviewed_by');
            $table->dropColumn([
                'rejection_reason',
                'payment_rejection_reason',
                'reviewed_at',
                'approved_at',
                'rejected_at',
                'payment_reviewed_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->string('current_category', 32);
            $table->string('requested_category', 32);
            $table->string('status', 32)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->string('applicant_response', 32)->nullable();
            $table->string('token', 64)->unique();
            $table->timestamps();

            $table->index(['registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_transfer_requests');
    }
};

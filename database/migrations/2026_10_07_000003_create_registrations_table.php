<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->nullable()->unique();
            $table->string('has_tournament_experience', 8);
            $table->string('entry_level', 32);
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_initial', 10)->nullable();
            $table->string('contact_number', 20);
            $table->text('address');
            $table->string('facebook')->nullable();
            $table->string('email');
            $table->string('photo_path');
            $table->string('payment_proof_path');
            $table->string('payment_status', 32)->default('pending');
            $table->string('registration_status', 32)->default('pending');
            $table->string('submission_token', 64)->unique();
            $table->timestamps();

            $table->index('email');
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};

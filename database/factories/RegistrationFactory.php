<?php

namespace Database\Factories;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'has_tournament_experience' => fake()->randomElement(['yes', 'no']),
            'entry_level' => fake()->randomElement(['beginner', 'novice', 'intermediate']),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
            'contact_number' => '09'.fake()->numerify('#########'),
            'address' => fake()->address(),
            'email' => fake()->unique()->safeEmail(),
            'photo_path' => 'registrations/photos/'.Str::uuid().'.png',
            'payment_proof_path' => 'registrations/proofs/'.Str::uuid().'.png',
            'payment_status' => 'pending',
            'registration_status' => 'pending',
            'slot_status' => 'pending_verification',
            'waiting_list_position' => null,
            'confirmed_at' => null,
            'submission_token' => Str::random(64),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => 'verified',
            'registration_status' => 'approved',
            'slot_status' => 'confirmed',
            'waiting_list_position' => null,
            'confirmed_at' => now(),
            'approved_at' => now(),
            'reviewed_at' => now(),
        ]);
    }

    public function waiting(int $position): static
    {
        return $this->state(fn (): array => [
            'payment_status' => 'verified',
            'registration_status' => 'approved',
            'slot_status' => 'waiting',
            'waiting_list_position' => $position,
            'confirmed_at' => null,
            'approved_at' => now(),
            'reviewed_at' => now(),
        ]);
    }
}

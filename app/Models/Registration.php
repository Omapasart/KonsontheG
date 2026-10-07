<?php

namespace App\Models;

use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentExperience;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'registration_number',
        'has_tournament_experience',
        'entry_level',
        'last_name',
        'first_name',
        'middle_initial',
        'contact_number',
        'address',
        'facebook',
        'email',
        'photo_path',
        'payment_proof_path',
        'payment_status',
        'registration_status',
        'submission_token',
    ];

    protected function casts(): array
    {
        return [
            'has_tournament_experience' => TournamentExperience::class,
            'entry_level' => EntryLevel::class,
            'payment_status' => PaymentStatus::class,
            'registration_status' => RegistrationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Registration $registration): void {
            if (filled($registration->registration_number)) {
                return;
            }

            $registration->forceFill([
                'registration_number' => sprintf(
                    'KTG-%s-%04d',
                    $registration->created_at->format('Y'),
                    $registration->id
                ),
            ])->saveQuietly();
        });
    }

    public function fullName(): string
    {
        $middle = filled($this->middle_initial)
            ? ' '.rtrim($this->middle_initial, '.').'.'
            : '';

        return trim("{$this->first_name}{$middle} {$this->last_name}");
    }
}

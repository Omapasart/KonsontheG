<?php

namespace App\Models;

use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentExperience;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
    use HasFactory;

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
        'rejection_reason',
        'payment_rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'payment_reviewed_by',
        'payment_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_tournament_experience' => TournamentExperience::class,
            'entry_level' => EntryLevel::class,
            'payment_status' => PaymentStatus::class,
            'registration_status' => RegistrationStatus::class,
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'payment_reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function paymentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_reviewed_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class)->latest();
    }

    public function fullName(): string
    {
        $middle = filled($this->middle_initial)
            ? ' '.rtrim($this->middle_initial, '.').'.'
            : '';

        return trim("{$this->first_name}{$middle} {$this->last_name}");
    }

    public function formalName(): string
    {
        $middle = filled($this->middle_initial)
            ? ' '.rtrim($this->middle_initial, '.').'.'
            : '';

        return trim("{$this->last_name}, {$this->first_name}{$middle}");
    }

    public function facebookHref(): ?string
    {
        if (! filled($this->facebook)) {
            return null;
        }

        $value = trim($this->facebook);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (str_contains(strtolower($value), 'facebook.com')) {
            return 'https://'.ltrim(preg_replace('#^https?://#i', '', $value), '/');
        }

        return null;
    }

    public function paymentProofIsPdf(): bool
    {
        return strtolower(pathinfo((string) $this->payment_proof_path, PATHINFO_EXTENSION)) === 'pdf';
    }
}

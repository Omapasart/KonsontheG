<?php

namespace App\Models;

use App\Enums\CategoryTransferStatus;
use App\Enums\EntryLevel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Enums\TournamentExperience;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'email',
        'photo_path',
        'payment_proof_path',
        'payment_status',
        'registration_status',
        'slot_status',
        'waiting_list_position',
        'confirmed_at',
        'withdrawn_at',
        'submission_token',
        'privacy_consent',
        'privacy_consent_at',
        'privacy_notice_version',
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
            'slot_status' => SlotStatus::class,
            'reviewed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'payment_reviewed_at' => 'datetime',
            'privacy_consent' => 'boolean',
            'privacy_consent_at' => 'datetime',
        ];
    }

    public const EMAIL_TAKEN_MESSAGE = 'This email address has already been used for a KONSONTHEGO tournament registration. Only one registration is allowed per email address.';

    protected static function booted(): void
    {
        static::saving(function (Registration $registration): void {
            if (filled($registration->email)) {
                $registration->email = strtolower(trim((string) $registration->email));
            }
        });

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

    public function transferRequests(): HasMany
    {
        return $this->hasMany(CategoryTransferRequest::class)->latest();
    }

    public function latestTransferRequest(): HasOne
    {
        return $this->hasOne(CategoryTransferRequest::class)->latestOfMany();
    }

    public function pendingTransferRequest(): HasOne
    {
        return $this->hasOne(CategoryTransferRequest::class)
            ->where('status', CategoryTransferStatus::Pending)
            ->latestOfMany();
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

    public function paymentProofIsPdf(): bool
    {
        return strtolower(pathinfo((string) $this->payment_proof_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public static function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public function slotLabel(): string
    {
        if ($this->slot_status === SlotStatus::Waiting) {
            return 'Waiting #'.($this->waiting_list_position ?: '?');
        }

        return $this->slot_status?->label() ?? 'Pending Verification';
    }

    public static function emailIsRegistered(?string $email): bool
    {
        $normalized = self::normalizeEmail($email);

        if ($normalized === '') {
            return false;
        }

        return self::query()->where('email', $normalized)->exists();
    }
}

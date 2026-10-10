<?php

namespace App\Models;

use App\Enums\CategoryTransferResponse;
use App\Enums\CategoryTransferStatus;
use App\Enums\EntryLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class CategoryTransferRequest extends Model
{
    protected $fillable = [
        'registration_id',
        'current_category',
        'requested_category',
        'status',
        'requested_by',
        'requested_at',
        'responded_at',
        'applicant_response',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'current_category' => EntryLevel::class,
            'requested_category' => EntryLevel::class,
            'status' => CategoryTransferStatus::class,
            'applicant_response' => CategoryTransferResponse::class,
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function signedShowUrl(): string
    {
        return URL::temporarySignedRoute(
            'transfers.show',
            now()->addDays(14),
            ['transfer' => $this, 'token' => $this->token]
        );
    }

    public function transferLabel(): string
    {
        if ($this->status === CategoryTransferStatus::Accepted) {
            return 'Accepted → '.$this->requested_category->label();
        }

        return $this->status->label();
    }
}

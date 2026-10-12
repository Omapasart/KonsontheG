<?php

namespace App\Enums;

enum SlotStatus: string
{
    case PendingVerification = 'pending_verification';
    case Confirmed = 'confirmed';
    case Waiting = 'waiting';
    case Withdrawn = 'withdrawn';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Pending Verification',
            self::Confirmed => 'Confirmed',
            self::Waiting => 'Waiting List',
            self::Withdrawn => 'Withdrawn',
            self::Rejected => 'Rejected',
        };
    }

    public function occupiesSlot(): bool
    {
        return $this === self::Confirmed || $this === self::Waiting;
    }

    public function isReleased(): bool
    {
        return $this === self::Withdrawn || $this === self::Rejected;
    }
}

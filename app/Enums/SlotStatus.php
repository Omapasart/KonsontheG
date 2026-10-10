<?php

namespace App\Enums;

enum SlotStatus: string
{
    case PendingVerification = 'pending_verification';
    case Confirmed = 'confirmed';
    case Waiting = 'waiting';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Pending Verification',
            self::Confirmed => 'Confirmed',
            self::Waiting => 'Waiting List',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function occupiesSlot(): bool
    {
        return $this === self::Confirmed || $this === self::Waiting;
    }
}

<?php

namespace App\Enums;

enum TournamentExperience: string
{
    case Yes = 'yes';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Yes',
            self::No => 'No',
        };
    }
}

<?php

namespace App\Enums;

enum EntryLevel: string
{
    case Beginner = 'beginner';
    case Novice = 'novice';
    case Intermediate = 'intermediate';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Novice => 'Novice',
            self::Intermediate => 'Intermediate',
        };
    }
}

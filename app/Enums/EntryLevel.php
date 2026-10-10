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

    public function rank(): int
    {
        return match ($this) {
            self::Beginner => 1,
            self::Novice => 2,
            self::Intermediate => 3,
        };
    }

    /**
     * @return list<self>
     */
    public function higherLevels(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $level) => $level->rank() > $this->rank()
        ));
    }
}

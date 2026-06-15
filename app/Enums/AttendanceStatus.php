<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Whether this status counts toward attended classes for rate calculations.
     * Late and excused are treated as attended.
     */
    public function countsAsPresent(): bool
    {
        return $this !== self::ABSENT;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

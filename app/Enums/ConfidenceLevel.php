<?php

namespace App\Enums;

enum ConfidenceLevel: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    public function label(): string
    {
        return match ($this) {
            self::VERY_LOW => 'Very Low',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::VERY_HIGH => 'Very High',
        };
    }

    public function threshold(): float
    {
        return match ($this) {
            self::VERY_LOW => 0.0,
            self::LOW => 0.3,
            self::MEDIUM => 0.5,
            self::HIGH => 0.7,
            self::VERY_HIGH => 0.9,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VERY_LOW => 'red',
            self::LOW => 'orange',
            self::MEDIUM => 'yellow',
            self::HIGH => 'green',
            self::VERY_HIGH => 'emerald',
        };
    }

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 0.9 => self::VERY_HIGH,
            $score >= 0.7 => self::HIGH,
            $score >= 0.5 => self::MEDIUM,
            $score >= 0.3 => self::LOW,
            default => self::VERY_LOW,
        };
    }
}

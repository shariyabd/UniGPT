<?php

namespace App\Enums;

enum ExamType: string
{
    case MIDTERM = 'midterm';
    case FINAL = 'final';
    case QUIZ = 'quiz';
    case PRACTICAL = 'practical';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->getLabel()],
            self::cases(),
        );
    }
}

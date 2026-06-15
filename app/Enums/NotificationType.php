<?php

namespace App\Enums;

enum NotificationType: string
{
    case GRADE = 'grade';
    case MATERIAL = 'material';
    case EXAM = 'exam';
    case ANNOUNCEMENT = 'announcement';
    case SYSTEM = 'system';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Heroicon name rendered alongside the notification in the UI.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::GRADE => 'AcademicCapIcon',
            self::MATERIAL => 'BookOpenIcon',
            self::EXAM => 'CalendarDaysIcon',
            self::ANNOUNCEMENT => 'MegaphoneIcon',
            self::SYSTEM => 'BellIcon',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

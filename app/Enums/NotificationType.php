<?php

namespace App\Enums;

enum NotificationType: string
{
    case GRADE = 'grade';
    case MATERIAL = 'material';
    case ASSIGNMENT = 'assignment';
    case SUBMISSION = 'submission';
    case ENROLLMENT = 'enrollment';
    case EXAM = 'exam';
    case CLASS_TEST = 'class_test';
    case ANNOUNCEMENT = 'announcement';
    case OFFICE_HOURS = 'office_hours';
    case ACHIEVEMENT = 'achievement';
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
            self::ASSIGNMENT => 'DocumentTextIcon',
            self::SUBMISSION => 'InboxArrowDownIcon',
            self::ENROLLMENT => 'UserPlusIcon',
            self::EXAM => 'CalendarDaysIcon',
            self::CLASS_TEST => 'PencilSquareIcon',
            self::ANNOUNCEMENT => 'MegaphoneIcon',
            self::OFFICE_HOURS => 'ClockIcon',
            self::ACHIEVEMENT => 'TrophyIcon',
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

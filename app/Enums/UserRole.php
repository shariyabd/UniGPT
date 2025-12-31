<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case FACULTY = 'faculty';
    case STUDENT = 'student';

    public function getLabel(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::FACULTY => 'Faculty',
            self::STUDENT => 'Student',
        };
    }
    public function getDescription(): string
    {
        return match ($this) {
            self::STUDENT => 'Regular university student with access to courses and materials',
            self::FACULTY => 'Teaching faculty member with course management capabilities',
            self::ADMIN => 'System administrator with full access and management permissions',
        };
    }

    public function getSlug(): string
    {
        return strtoupper($this->value);
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return array_map(fn($role) => $role->getLabel(), self::cases());
    }
    public function permissions(): array
    {
        return match($this) {
            self::ADMIN => [
                'manage.users',
                'manage.documents',
                'manage.settings',
                'view.analytics',
                'manage.prompts',
                'approve.content',
                'manage.roles',
            ],
            self::FACULTY => [
                'upload.documents',
                'view.analytics',
                'use.ai.assist',
                'manage.courses',
                'view.student.progress',
            ],
            self::STUDENT => [
                'use.chat',
                'view.roadmap',
                'save.answers',
                'view.documents',
                'generate.summaries',
            ],
        };
    }
}

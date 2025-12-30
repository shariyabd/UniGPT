<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case FACULTY = 'faculty';
    case STUDENT = 'student';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::FACULTY => 'Faculty',
            self::STUDENT => 'Student',
        };
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

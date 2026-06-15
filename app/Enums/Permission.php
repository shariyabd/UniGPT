<?php

namespace App\Enums;

enum Permission: string
{
    // Document Permissions
    case UPLOAD_DOCUMENT = 'upload_document';
    case DOWNLOAD_DOCUMENT = 'download_document';
    case VIEW_DOCUMENTS = 'view_documents';
    case DELETE_DOCUMENT = 'delete_document';
    case APPROVE_DOCUMENT = 'approve_document';

    // User Management
    case VIEW_USERS = 'view_users';
    case CREATE_USER = 'create_user';
    case UPDATE_USER = 'update_user';
    case DELETE_USER = 'delete_user';
    case MANAGE_USER_ROLES = 'manage_user_roles';

    // Course Management
    case VIEW_COURSES = 'view_courses';
    case CREATE_COURSE = 'create_course';
    case UPDATE_COURSE = 'update_course';
    case DELETE_COURSE = 'delete_course';
    case ENROLL_COURSE = 'enroll_course';
    case MANAGE_MATERIALS = 'manage_materials';

    // Assignment Management
    case VIEW_ASSIGNMENTS = 'view_assignments';
    case CREATE_ASSIGNMENT = 'create_assignment';
    case SUBMIT_ASSIGNMENT = 'submit_assignment';
    case GRADE_ASSIGNMENT = 'grade_assignment';
    case DELETE_ASSIGNMENT = 'delete_assignment';

    // Attendance
    case VIEW_ATTENDANCE = 'view_attendance';
    case MARK_ATTENDANCE = 'mark_attendance';

    // Exams / Timetable
    case VIEW_EXAMS = 'view_exams';
    case MANAGE_EXAMS = 'manage_exams';

    // Chat & AI
    case USE_AI_CHAT = 'use_ai_chat';
    case VIEW_CHAT_HISTORY = 'view_chat_history';
    case DELETE_CHAT = 'delete_chat';
    case CONFIGURE_AI = 'configure_ai';

    // Analytics
    case VIEW_OWN_ANALYTICS = 'view_own_analytics';
    case VIEW_DEPARTMENT_ANALYTICS = 'view_department_analytics';
    case VIEW_ALL_ANALYTICS = 'view_all_analytics';

    // System Administration
    case MANAGE_SYSTEM = 'manage_system';
    case VIEW_SYSTEM_LOGS = 'view_system_logs';
    case CONFIGURE_SETTINGS = 'configure_settings';
    case MANAGE_PERMISSIONS = 'manage_permissions';
    case SEND_NOTIFICATIONS = 'send_notifications';

    public function getLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    public function getSlug(): string
    {
        return $this->value;
    }

    public function getCategory(): string
    {
        return match ($this) {
            self::UPLOAD_DOCUMENT, self::DOWNLOAD_DOCUMENT, self::VIEW_DOCUMENTS,
            self::DELETE_DOCUMENT, self::APPROVE_DOCUMENT => 'Documents',

            self::VIEW_USERS, self::CREATE_USER, self::UPDATE_USER,
            self::DELETE_USER, self::MANAGE_USER_ROLES => 'User Management',

            self::VIEW_COURSES, self::CREATE_COURSE, self::UPDATE_COURSE,
            self::DELETE_COURSE, self::ENROLL_COURSE, self::MANAGE_MATERIALS => 'Courses',

            self::VIEW_ASSIGNMENTS, self::CREATE_ASSIGNMENT, self::SUBMIT_ASSIGNMENT,
            self::GRADE_ASSIGNMENT, self::DELETE_ASSIGNMENT => 'Assignments',

            self::VIEW_ATTENDANCE, self::MARK_ATTENDANCE => 'Attendance',

            self::VIEW_EXAMS, self::MANAGE_EXAMS => 'Exams',

            self::USE_AI_CHAT, self::VIEW_CHAT_HISTORY, self::DELETE_CHAT,
            self::CONFIGURE_AI => 'AI & Chat',

            self::VIEW_OWN_ANALYTICS, self::VIEW_DEPARTMENT_ANALYTICS,
            self::VIEW_ALL_ANALYTICS => 'Analytics',

            self::MANAGE_SYSTEM, self::VIEW_SYSTEM_LOGS, self::CONFIGURE_SETTINGS,
            self::MANAGE_PERMISSIONS, self::SEND_NOTIFICATIONS => 'System',
        };
    }

    public static function getByCategory(): array
    {
        $grouped = [];
        foreach (self::cases() as $permission) {
            $category = $permission->getCategory();
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}

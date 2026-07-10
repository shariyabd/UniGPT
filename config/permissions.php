<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | Role-based permissions for the UniNexus system
    |
    */

    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'permissions' => [
                'manage.users',
                'manage.documents',
                'manage.settings',
                'view.analytics',
                'manage.prompts',
                'approve.content',
                'manage.roles',
            ],
        ],

        'faculty' => [
            'name' => 'Faculty',
            'permissions' => [
                'upload.documents',
                'view.analytics',
                'use.ai.assist',
                'manage.courses',
                'view.student.progress',
            ],
        ],

        'student' => [
            'name' => 'Student',
            'permissions' => [
                'use.chat',
                'view.roadmap',
                'save.answers',
                'view.documents',
                'generate.summaries',
            ],
        ],
    ],

    'default_role' => 'student',

    'super_admin_email' => env('SUPER_ADMIN_EMAIL', 'admin@uninexus.edu'),
];

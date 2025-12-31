<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\Permission as PermissionEnum;
use Illuminate\Support\Facades\Hash;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting RBAC Seeder...');

        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
        $this->createDemoUsers();

        $this->command->info('RBAC Seeder completed successfully!');
    }

    private function createRoles(): void
    {
        $this->command->info('Creating roles...');

        foreach (UserRole::cases() as $roleEnum) {
            Role::firstOrCreate(
                ['slug' => $roleEnum->getSlug()],
                [
                    'name' => $roleEnum->getLabel(),
                    'description' => $roleEnum->getDescription(),
                    'is_active' => true,
                    'level' => fake()->numberBetween(1, 3),
                ]
            );

            $this->command->info("   ✓ {$roleEnum->getLabel()} role created");
        }
    }

    private function createPermissions(): void
    {
        $this->command->info('Creating permissions...');

        foreach (PermissionEnum::cases() as $permissionEnum) {
            Permission::firstOrCreate(
                ['slug' => $permissionEnum->getSlug()],
                [
                    'name' => $permissionEnum->getLabel(),
                    'description' => "Permission to {$permissionEnum->getLabel()}",
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("   ✓ " . count(PermissionEnum::cases()) . " permissions created");
    }

    private function assignPermissionsToRoles(): void
    {
        $this->command->info('Assigning permissions to roles...');

        $studentRole = Role::findByEnum(UserRole::STUDENT);
        $facultyRole = Role::findByEnum(UserRole::FACULTY);
        $adminRole = Role::findByEnum(UserRole::ADMIN);

        $studentPermissions = [
            PermissionEnum::VIEW_DOCUMENTS,
            PermissionEnum::DOWNLOAD_DOCUMENT,
            PermissionEnum::VIEW_COURSES,
            PermissionEnum::ENROLL_COURSE,
            PermissionEnum::VIEW_ASSIGNMENTS,
            PermissionEnum::SUBMIT_ASSIGNMENT,
            PermissionEnum::USE_AI_CHAT,
            PermissionEnum::VIEW_CHAT_HISTORY,
            PermissionEnum::DELETE_CHAT,
            PermissionEnum::VIEW_OWN_ANALYTICS,
        ];

        foreach ($studentPermissions as $permission) {
            $studentRole->givePermission($permission);
        }
        $this->command->info("   ✓ Student permissions assigned (" . count($studentPermissions) . ")");


        $facultyPermissions = array_merge($studentPermissions, [
            PermissionEnum::UPLOAD_DOCUMENT,
            PermissionEnum::CREATE_COURSE,
            PermissionEnum::UPDATE_COURSE,
            PermissionEnum::CREATE_ASSIGNMENT,
            PermissionEnum::GRADE_ASSIGNMENT,
            PermissionEnum::VIEW_DEPARTMENT_ANALYTICS,
        ]);

        foreach ($facultyPermissions as $permission) {
            $facultyRole->givePermission($permission);
        }
        $this->command->info(" Faculty permissions assigned (" . count($facultyPermissions) . ")");


        foreach (PermissionEnum::cases() as $permission) {
            $adminRole->givePermission($permission);
        }
        $this->command->info("   ✓ Admin permissions assigned (ALL - " . count(PermissionEnum::cases()) . ")");
    }

    private function createDemoUsers(): void
    {
        $this->command->info('Creating demo users...');

        $demoUsers = [
            [
                'name' => 'Demo Student',
                'email' => 'student@university.edu',
                'password' => Hash::make('demo123'),
                'department' => 'Computer Science Engineering',
                'semester' => '5th Semester',
                'student_id' => 'CS2024001',
                'role' => UserRole::STUDENT,
            ],
            [
                'name' => 'Prof. John Smith',
                'email' => 'prof.smith@university.edu',
                'password' => Hash::make('demo123'),
                'department' => 'Computer Science Engineering',
                'employee_id' => 'FAC001',
                'bio' => 'Professor of Computer Science with 15+ years of experience in AI and Machine Learning.',
                'role' => UserRole::FACULTY,
            ],
            [
                'name' => 'System Administrator',
                'email' => 'admin@university.edu',
                'password' => Hash::make('demo123'),
                'department' => 'Administration',
                'employee_id' => 'ADM001',
                'bio' => 'System Administrator managing UniGPT platform and all technical operations.',
                'role' => UserRole::ADMIN,
            ],
        ];

        foreach ($demoUsers as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'email_verified_at' => now(),
                    'is_active' => true,
                ])
            );

            $user->assignRole($role);
            $this->command->info("   ✓ {$userData['name']} ({$role->getLabel()}) created");
        }
    }
}

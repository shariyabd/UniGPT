<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting RBAC Seeder...');

        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
        $this->createDepartments();
        $this->createDemoUsers();

        $this->command->info('RBAC Seeder completed successfully!');
    }

    private function createRoles(): void
    {
        $this->command->info('Creating roles...');

        foreach (UserRole::cases() as $roleEnum) {
            $features = [];
            if ($roleEnum == UserRole::ADMIN) {
                $features = ['User Management', 'System Analytics', 'AI Configuration', 'Content Moderation'];
            } elseif ($roleEnum == UserRole::FACULTY) {
                $features = ['AI Teaching Assistant', 'Course Management', 'Student Analytics', 'Grading Tools'];
            } elseif ($roleEnum == UserRole::STUDENT) {
                $features = ['AI Study Assistant', 'Course Materials', 'Learning Roadmap', 'Progress Tracking'];
            }

            Role::firstOrCreate(
                ['slug' => strtolower($roleEnum->getSlug())],
                [
                    'name' => $roleEnum->getLabel(),
                    'description' => $roleEnum->getDescription(),
                    'features' => $features,
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
                    'category' => $permissionEnum->getCategory(),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('   ✓ '.count(PermissionEnum::cases()).' permissions created');
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
            PermissionEnum::VIEW_ATTENDANCE,
        ];

        foreach ($studentPermissions as $permission) {
            $studentRole->givePermission($permission);
        }
        $this->command->info('   ✓ Student permissions assigned ('.count($studentPermissions).')');

        $facultyPermissions = array_merge($studentPermissions, [
            PermissionEnum::UPLOAD_DOCUMENT,
            PermissionEnum::CREATE_COURSE,
            PermissionEnum::UPDATE_COURSE,
            PermissionEnum::DELETE_COURSE,
            PermissionEnum::MANAGE_MATERIALS,
            PermissionEnum::CREATE_ASSIGNMENT,
            PermissionEnum::GRADE_ASSIGNMENT,
            PermissionEnum::MARK_ATTENDANCE,
            PermissionEnum::VIEW_DEPARTMENT_ANALYTICS,
        ]);

        foreach ($facultyPermissions as $permission) {
            $facultyRole->givePermission($permission);
        }
        $this->command->info(' Faculty permissions assigned ('.count($facultyPermissions).')');

        foreach (PermissionEnum::cases() as $permission) {
            $adminRole->givePermission($permission);
        }
        $this->command->info('   ✓ Admin permissions assigned (ALL - '.count(PermissionEnum::cases()).')');
    }

    private function createDepartments(): void
    {
        $this->command->info('Creating departments...');

        $departments = [
            'Computer Science Engineering',
            'Electrical Engineering',
            'Mechanical Engineering',
            'Civil Engineering',
            'Business Administration',
            'Psychology',
            'Biology',
            'Mathematics',
            'Physics',
            'Chemistry',
        ];

        foreach ($departments as $departmentName) {
            $data = [
                'slug' => Str::slug($departmentName),
                'description' => "{$departmentName} Department",
                'code' => 'CSE'.rand(1, 10).chr(rand(65, 90)).chr(rand(65, 90)),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            \App\Models\Department::firstOrCreate(
                ['name' => $departmentName],
                $data
            );

            $this->command->info("   ✓ {$departmentName} department created");
        }
    }

    private function createDemoUsers(): void
    {
        $this->command->info('Creating demo users...');

        $departmentIds = Department::all()->pluck('id')->toArray();
        $demoUsers = [
            [
                'name' => 'Demo Student',
                'email' => 'student@university.edu',
                'password' => Hash::make('demo123'),
                'department_id' => $departmentIds[array_rand($departmentIds)],
                'semester' => '5th Semester',
                'student_id' => 'CS2024001',
                'role' => UserRole::STUDENT,
            ],
            [
                'name' => 'Prof. John Smith',
                'email' => 'prof.smith@university.edu',
                'password' => Hash::make('demo123'),
                'department_id' => $departmentIds[array_rand($departmentIds)],
                'employee_id' => 'FAC001',
                'bio' => 'Professor of Computer Science with 15+ years of experience in AI and Machine Learning.',
                'role' => UserRole::FACULTY,
            ],
            [
                'name' => 'System Administrator',
                'email' => 'admin@university.edu',
                'password' => Hash::make('demo123'),
                'department_id' => $departmentIds[array_rand($departmentIds)],
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

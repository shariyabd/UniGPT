<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserManagementService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user statistics for admin dashboard
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => $this->userRepository->count(),
            'active_users' => $this->userRepository->countActive(),
            'total_students' => $this->userRepository->countByRole(UserRole::STUDENT),
            'total_faculty' => $this->userRepository->countByRole(UserRole::FACULTY),
            'total_admins' => $this->userRepository->countByRole(UserRole::ADMIN),
            'new_registrations_this_week' => $this->userRepository->countNewRegistrations(7),
            'online_users' => $this->userRepository->countOnlineUsers(),
        ];
    }

    /**
     * Get users with pagination
     */
    public function getPaginatedUsers(int $perPage = 20): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedWithRoles($perPage);
    }

    /**
     * Create new user
     */
    public function createUser(array $userData, UserRole $role, ?User $createdBy = null): User
    {
        $user = $this->userRepository->create($userData);
        $user->assignRole($role, $createdBy);

        return $user;
    }

    /**
     * Update user
     */
    public function updateUser(User $user, array $userData): User
    {
        return $this->userRepository->update($user, $userData);
    }

    /**
     * Deactivate user
     */
    public function deactivateUser(User $user, ?User $deactivatedBy = null): void
    {
        $this->userRepository->update($user, [
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $deactivatedBy?->id,
        ]);
    }

    /**
     * Get users by department
     */
    public function getUsersByDepartment(string $department): Collection
    {
        return $this->userRepository->findByDepartment($department);
    }

    /**
     * Search users
     */
    public function searchUsers(string $query): Collection
    {
        return $this->userRepository->search($query);
    }
}

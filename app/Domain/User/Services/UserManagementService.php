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
     * Get user statistics. Pass the active list filters (role, status, search)
     * to scope the counts to the current query; omit them for grand totals
     * (e.g. the admin dashboard).
     *
     * @param  array<string, string|null>  $filters
     * @return array<string, int>
     */
    public function getUserStatistics(array $filters = []): array
    {
        return $this->userRepository->statistics($filters);
    }

    /**
     * Get users with pagination, optionally filtered by role, status and search.
     *
     * @param  array<string, string|null>  $filters
     */
    public function getPaginatedUsers(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedWithRoles($perPage, $filters);
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
     * Permanently delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
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

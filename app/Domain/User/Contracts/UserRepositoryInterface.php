<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;

    public function count(): int;

    public function countActive(): int;

    public function countByRole(UserRole $role): int;

    public function countNewRegistrations(int $days): int;

    public function countOnlineUsers(): int;

    public function getPaginatedWithRoles(int $perPage): LengthAwarePaginator;

    public function findByDepartment(string $department): Collection;

    public function search(string $query): Collection;

    public function findByRole(UserRole $role): Collection;

    public function findActiveStudents(): Collection;
}

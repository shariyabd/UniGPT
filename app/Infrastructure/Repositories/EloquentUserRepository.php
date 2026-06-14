<?php

namespace App\Infrastructure\Repositories;

use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function count(): int
    {
        return User::count();
    }

    public function countActive(): int
    {
        return User::active()->count();
    }

    public function countByRole(UserRole $role): int
    {
        return User::withRole($role)->count();
    }

    public function countNewRegistrations(int $days): int
    {
        return User::where('created_at', '>=', now()->subDays($days))->count();
    }

    public function countOnlineUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subMinutes(15))->count();
    }

    public function getPaginatedWithRoles(int $perPage): LengthAwarePaginator
    {
        return User::with('roles')->latest()->paginate($perPage);
    }

    public function findByDepartment(string $department): Collection
    {
        return User::where('department', $department)->get();
    }

    public function search(string $query): Collection
    {
        return User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('student_id', 'like', "%{$query}%")
                ->orWhere('employee_id', 'like', "%{$query}%");
        })->with('roles')->get();
    }

    public function findByRole(UserRole $role): Collection
    {
        return User::withRole($role)->get();
    }

    public function findActiveStudents(): Collection
    {
        return User::active()->withRole(UserRole::STUDENT)->get();
    }
}

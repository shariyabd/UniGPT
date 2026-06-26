<?php

namespace App\Infrastructure\Repositories;

use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
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

    public function getPaginatedWithRoles(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters(User::with('roles'), $filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Aggregate user counts, optionally constrained to the same filters the
     * list view is using so the dashboard cards track the active query rather
     * than reporting the unfiltered grand total.
     *
     * @param  array<string, string|null>  $filters  role, status, search
     * @return array<string, int>
     */
    public function statistics(array $filters = []): array
    {
        return [
            'total_users' => $this->applyFilters(User::query(), $filters)->count(),
            'active_users' => $this->applyFilters(User::query(), $filters)->where('is_active', true)->count(),
            'total_students' => $this->applyFilters(User::query(), $filters)->withRole(UserRole::STUDENT)->count(),
            'total_faculty' => $this->applyFilters(User::query(), $filters)->withRole(UserRole::FACULTY)->count(),
            'total_admins' => $this->applyFilters(User::query(), $filters)->withRole(UserRole::ADMIN)->count(),
            'new_registrations_this_week' => $this->applyFilters(User::query(), $filters)
                ->where('created_at', '>=', now()->subDays(7))->count(),
            'online_users' => $this->applyFilters(User::query(), $filters)
                ->where('last_login_at', '>=', now()->subMinutes(15))->count(),
        ];
    }

    /**
     * Apply the shared list filters (role, status, search) to a user query.
     *
     * @param  array<string, string|null>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['role'])) {
            $query->withRole($filters['role']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
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

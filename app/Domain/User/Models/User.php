<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'semester',
        'student_id',
        'employee_id',
        'bio',
        'avatar',
        'is_active',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at'])
            ->withTimestamps()
            ->wherePivotNull('expires_at')
            ->orWherePivot('expires_at', '>', now());
    }

    public function allRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at'])
            ->withTimestamps();
    }


    public function hasRole(string|UserRole|array $roles): bool
    {
        if (is_string($roles) || $roles instanceof UserRole) {
            $roleSlug = $roles instanceof UserRole ? $roles->getSlug() : strtoupper($roles);
            return $this->roles->contains('slug', $roleSlug);
        }

        if (is_array($roles)) {
            $roleSlugs = array_map(function ($role) {
                return $role instanceof UserRole ? $role->getSlug() : strtoupper($role);
            }, $roles);

            return $this->roles->whereIn('slug', $roleSlugs)->isNotEmpty();
        }

        return false;
    }

    public function hasPermission(string|\App\Enums\Permission $permission): bool
    {
        $permissionSlug = $permission instanceof \App\Enums\Permission
            ? $permission->getSlug()
            : strtoupper($permission);

        return $this->roles->flatMap->permissions
            ->contains('slug', $permissionSlug);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function assignRole(string|UserRole|Role $role, ?User $assignedBy = null, ?\DateTime $expiresAt = null): void
    {
        if ($role instanceof Role) {
            $roleId = $role->id;
        } elseif ($role instanceof UserRole) {
            $roleModel = Role::where('slug', $role->getSlug())->first();
            $roleId = $roleModel ? $roleModel->id : null;
        } else {
            $roleModel = Role::where('slug', strtoupper($role))->first();
            $roleId = $roleModel ? $roleModel->id : null;
        }

        if (!$roleId) {
            throw new \InvalidArgumentException("Role not found: {$role}");
        }

        $this->roles()->syncWithoutDetaching([
            $roleId => [
                'assigned_at' => now(),
                'assigned_by' => $assignedBy?->id,
                'expires_at' => $expiresAt,
            ]
        ]);
    }

    public function removeRole(string|UserRole|Role $role): void
    {
        if ($role instanceof Role) {
            $roleId = $role->id;
        } elseif ($role instanceof UserRole) {
            $roleModel = Role::where('slug', $role->getSlug())->first();
            $roleId = $roleModel ? $roleModel->id : null;
        } else {
            $roleModel = Role::where('slug', strtoupper($role))->first();
            $roleId = $roleModel ? $roleModel->id : null;
        }

        if ($roleId) {
            $this->roles()->detach($roleId);
        }
    }

    public function syncRoles(array $roles): void
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $roleIds[] = $role->id;
            } elseif ($role instanceof UserRole) {
                $roleModel = Role::where('slug', $role->getSlug())->first();
                if ($roleModel) $roleIds[] = $roleModel->id;
            } else {
                $roleModel = Role::where('slug', strtoupper($role))->first();
                if ($roleModel) $roleIds[] = $roleModel->id;
            }
        }

        $this->roles()->sync($roleIds);
    }


    public function isStudent(): bool
    {
        return $this->hasRole(UserRole::STUDENT);
    }

    public function isFaculty(): bool
    {
        return $this->hasRole(UserRole::FACULTY);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }


    public function getPrimaryRole(): ?Role
    {
        return $this->roles()
            ->orderBy('level', 'desc')
            ->first();
    }


    public function getPrimaryRoleEnum(): ?UserRole
    {
        $primaryRole = $this->getPrimaryRole();

        if (!$primaryRole) return null;

        return match ($primaryRole->slug) {
            'STUDENT' => UserRole::STUDENT,
            'FACULTY' => UserRole::FACULTY,
            'ADMIN' => UserRole::ADMIN,
            default => null
        };
    }


    public function getDashboardRoute(): string
    {
        $primaryRole = $this->getPrimaryRole();

        return match ($primaryRole?->slug) {
            'ADMIN' => 'admin.dashboard',
            'FACULTY' => 'faculty.dashboard',
            'STUDENT' => 'dashboard',
            default => 'login'
        };
    }


    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $primaryRole = $this->getPrimaryRole();

                return match ($primaryRole?->slug) {
                    'FACULTY' => "Prof. {$this->name}",
                    'ADMIN' => "Admin {$this->name}",
                    default => $this->name
                };
            }
        );
    }

    protected function identifier(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->student_id ?? $this->employee_id
        );
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string|UserRole $role)
    {
        $roleSlug = $role instanceof UserRole ? $role->getSlug() : strtoupper($role);

        return $query->whereHas('roles', function ($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        });
    }


    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\UserRole;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'level'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at'])
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    public function givePermission(string|\App\Enums\Permission|Permission $permission): void
    {
        if ($permission instanceof Permission) {
            $permissionId = $permission->id;
        } elseif ($permission instanceof \App\Enums\Permission) {
            $permissionModel = Permission::where('slug', $permission->getSlug())->first();
            $permissionId = $permissionModel ? $permissionModel->id : null;
        } else {
            $permissionModel = Permission::where('slug', strtoupper($permission))->first();
            $permissionId = $permissionModel ? $permissionModel->id : null;
        }

        if (!$permissionId) {
            throw new \InvalidArgumentException("Permission not found: {$permission}");
        }

        $this->permissions()->syncWithoutDetaching($permissionId);
    }

    public function revokePermission(string|\App\Enums\Permission|Permission $permission): void
    {
        if ($permission instanceof Permission) {
            $permissionId = $permission->id;
        } elseif ($permission instanceof \App\Enums\Permission) {
            $permissionModel = Permission::where('slug', $permission->getSlug())->first();
            $permissionId = $permissionModel ? $permissionModel->id : null;
        } else {
            $permissionModel = Permission::where('slug', strtoupper($permission))->first();
            $permissionId = $permissionModel ? $permissionModel->id : null;
        }

        if ($permissionId) {
            $this->permissions()->detach($permissionId);
        }
    }

    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            } elseif ($permission instanceof \App\Enums\Permission) {
                $permissionModel = Permission::where('slug', $permission->getSlug())->first();
                if ($permissionModel) $permissionIds[] = $permissionModel->id;
            } else {
                $permissionModel = Permission::where('slug', strtoupper($permission))->first();
                if ($permissionModel) $permissionIds[] = $permissionModel->id;
            }
        }

        $this->permissions()->sync($permissionIds);
    }

    public function hasPermission(string|\App\Enums\Permission $permission): bool
    {
        $permissionSlug = $permission instanceof \App\Enums\Permission
            ? $permission->getSlug()
            : strtoupper($permission);

        return $this->permissions->contains('slug', $permissionSlug);
    }


    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', strtoupper($slug))->first();
    }

    public static function findByEnum(UserRole $role): ?self
    {
        return self::where('slug', $role->getSlug())->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function getEnum(): ?UserRole
    {
        return match ($this->slug) {
            'STUDENT' => UserRole::STUDENT,
            'FACULTY' => UserRole::FACULTY,
            'ADMIN' => UserRole::ADMIN,
            default => null
        };
    }
}

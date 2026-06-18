<?php

namespace App\Domain\User\Models;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The canonical System Administrator account. This account is protected
     * from deactivation and role changes so admins can never be locked out.
     */
    public const PROTECTED_ADMIN_EMAIL = 'admin@university.edu';

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
        'preferences',
        'is_active',
        'last_login_at',
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
            'preferences' => 'array',
            'password' => 'hashed',
            // Curriculum level (1–8), aligned with courses.semester.
            'semester' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function chatSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ChatSession::class);
    }

    public function savedAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SavedAnswer::class);
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Course::class, 'course_user')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at', 'term_id', 'section_id'])
            ->withTimestamps()
            ->wherePivot('role', 'student');
    }

    /**
     * Sections (offerings) this user is enrolled in as a student.
     *
     * The section — not the course — is the unit a student actually attends, so
     * every section-scoped read (the section's instructor, its materials,
     * assignments and exams) hangs off this relation. It is the section-level
     * analog of {@see enrolledCourses()}, joined through the same pivot on its
     * `section_id`.
     */
    public function enrolledSections(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Section::class, 'course_user', 'user_id', 'section_id')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at', 'term_id', 'course_id'])
            ->withTimestamps()
            ->wherePivot('role', 'student');
    }

    /**
     * IDs of the sections this student is enrolled in, excluding dropped ones
     * and pending placements the student has not yet confirmed (registered).
     * Completed (past-term) enrolments are retained so the student keeps
     * read access to their finished sections' materials and history.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function enrolledSectionIds(): \Illuminate\Support\Collection
    {
        return $this->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'pending'])
            ->pluck('sections.id')
            ->unique()
            ->values();
    }

    /**
     * IDs of the sections this faculty member teaches.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function teachingSectionIds(): \Illuminate\Support\Collection
    {
        return $this->teachingSections()->pluck('id');
    }

    public function teachingCourses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Course::class, 'faculty_id');
    }

    /**
     * Sections (offerings) this faculty member teaches. One faculty can teach
     * many sections, across courses and terms.
     */
    public function teachingSections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Section::class, 'faculty_id');
    }

    /**
     * Course materials this student has marked complete.
     */
    public function completedMaterials(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\CourseMaterial::class, 'material_completions')
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AssignmentSubmission::class);
    }

    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AttendanceRecord::class, 'user_id');
    }

    /**
     * In-app notifications addressed to this user.
     *
     * Overrides the Notifiable trait's morph relation — this app uses a custom
     * per-recipient notifications table, not Laravel's database channel.
     */
    public function notifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Notification::class)->latest();
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Note::class);
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Task::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at'])
            ->withTimestamps()
            ->where(function ($query) {
                $query->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', now());
            });
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
            $roleSlug = $roles instanceof UserRole ? $roles->getSlug() : $roles;

            return $this->roles->contains('slug', $roleSlug);
        }

        if (is_array($roles)) {
            $roleSlugs = array_map(function ($role) {
                return $role instanceof UserRole ? $role->getSlug() : $role;
            }, $roles);

            return $this->roles->whereIn('slug', $roleSlugs)->isNotEmpty();
        }

        return false;
    }

    public function hasPermission(string|\App\Enums\Permission $permission): bool
    {
        $permissionSlug = $permission instanceof \App\Enums\Permission
            ? $permission->getSlug()
            : strtolower($permission);

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
            if (! $this->hasPermission($permission)) {
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
            $roleModel = Role::where('slug', strtolower($role))->first();
            $roleId = $roleModel ? $roleModel->id : null;
        }

        if (! $roleId) {
            throw new \InvalidArgumentException("Role not found: {$role}");
        }

        $this->roles()->syncWithoutDetaching([
            $roleId => [
                'assigned_at' => now(),
                'assigned_by' => $assignedBy?->id,
                'expires_at' => $expiresAt,
            ],
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
            $roleModel = Role::where('slug', strtolower($role))->first();
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
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } else {
                $roleModel = Role::where('slug', strtolower($role))->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
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

    /**
     * The protected System Administrator account cannot be deactivated or
     * have its role changed, preventing accidental admin lockout.
     */
    public function isProtectedAdmin(): bool
    {
        return $this->email === self::PROTECTED_ADMIN_EMAIL;
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

        if (! $primaryRole) {
            return null;
        }

        return match ($primaryRole->slug) {
            'student' => UserRole::STUDENT,
            'faculty' => UserRole::FACULTY,
            'admin' => UserRole::ADMIN,
            default => null
        };
    }

    public function getDashboardRoute(): string
    {
        $primaryRole = $this->getPrimaryRole();

        return match ($primaryRole?->slug) {
            'admin' => 'admin.dashboard',
            'faculty' => 'faculty.dashboard',
            'student' => 'dashboard',
            default => 'login'
        };
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $primaryRole = $this->getPrimaryRole();

                return match ($primaryRole?->slug) {
                    'faculty' => "Prof. {$this->name}",
                    'admin' => "Admin {$this->name}",
                    default => $this->name
                };
            }
        );
    }

    protected function identifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->student_id ?? $this->employee_id
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string|UserRole $role)
    {
        $roleSlug = $role instanceof UserRole ? $role->getSlug() : strtolower($role);

        return $query->whereHas('roles', function ($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        });
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}

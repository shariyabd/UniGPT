<?php

namespace App\Domain\User\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ClassTestAttempt;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Department;
use App\Models\Document;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Notification;
use App\Models\Role;
use App\Models\SavedAnswer;
use App\Models\Section;
use App\Models\Task;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        'leaderboard_opt_in',
        'leaderboard_alias',
        'is_active',
        'last_login_at',
        'ai_chat_blocked_at',
        'ai_chat_blocked_until',
        'ai_chat_block_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            // Cast the key to int so ownership checks (e.g. policies comparing
            // $model->user_id === $user->id) never fail on a string/int mismatch
            // — PDO can return integer columns as strings under emulated prepares.
            'id' => 'integer',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'ai_chat_blocked_at' => 'datetime',
            'ai_chat_blocked_until' => 'datetime',
            'is_active' => 'boolean',
            'leaderboard_opt_in' => 'boolean',
            'last_seen_at' => 'datetime',
            'preferences' => 'array',
            'password' => 'hashed',
            // Curriculum level (1–8), aligned with courses.semester.
            'semester' => 'integer',
        ];
    }

    /**
     * A user counts as "active" (green dot) if their presence heartbeat landed
     * within this window. The client pings more frequently than the window so a
     * still-open tab never flickers offline.
     */
    public const ACTIVE_WINDOW_SECONDS = 120;

    /**
     * Whether the user is currently active, per the presence heartbeat.
     */
    public function isActive(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds(self::ACTIVE_WINDOW_SECONDS));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * All chat messages this user has generated, across every session — used to
     * aggregate request counts and token usage for the admin AI-usage monitor.
     */
    public function chatMessages(): HasManyThrough
    {
        return $this->hasManyThrough(
            ChatMessage::class,
            ChatSession::class,
            'user_id',          // FK on chat_sessions → users
            'chat_session_id',  // FK on chat_messages → chat_sessions
            'id',
            'id',
        );
    }

    /**
     * Whether the user is currently blocked from the AI chat. A block is active
     * when it has been set and is either permanent (no expiry) or not yet expired.
     */
    public function isAiChatBlocked(): bool
    {
        if ($this->ai_chat_blocked_at === null) {
            return false;
        }

        return $this->ai_chat_blocked_until === null
            || $this->ai_chat_blocked_until->isFuture();
    }

    /**
     * Block the user from the AI chat. A null $until is a permanent block.
     */
    public function blockAiChat(string $reason, ?Carbon $until = null): void
    {
        $this->forceFill([
            'ai_chat_blocked_at' => now(),
            'ai_chat_blocked_until' => $until,
            'ai_chat_block_reason' => $reason,
        ])->save();
    }

    /**
     * Restore the user's AI-chat access.
     */
    public function unblockAiChat(): void
    {
        $this->forceFill([
            'ai_chat_blocked_at' => null,
            'ai_chat_blocked_until' => null,
            'ai_chat_block_reason' => null,
        ])->save();
    }

    public function savedAnswers(): HasMany
    {
        return $this->hasMany(SavedAnswer::class);
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at', 'term_id', 'section_id'])
            ->withTimestamps()
            ->wherePivot('role', 'student');
    }

    /**
     * Documents this user has bookmarked in the knowledge-base library.
     */
    public function bookmarkedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_bookmarks')
            ->withTimestamps();
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
        return $this->belongsToMany(Section::class, 'course_user', 'user_id', 'section_id')
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
     * @return Collection<int, int>
     */
    public function enrolledSectionIds(): Collection
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
     * @return Collection<int, int>
     */
    public function teachingSectionIds(): Collection
    {
        return $this->teachingSections()->pluck('id');
    }

    /**
     * Whether this user is allowed to start/continue a direct conversation with
     * another user. Messaging is restricted to a real teaching relationship: a
     * faculty member and a student who share at least one section. Same-person
     * and student↔student / faculty↔faculty pairs are never allowed.
     */
    public function canMessage(self $other): bool
    {
        if ($this->id === $other->id) {
            return false;
        }

        [$faculty, $student] = match (true) {
            $this->isFaculty() && $other->isStudent() => [$this, $other],
            $this->isStudent() && $other->isFaculty() => [$other, $this],
            default => [null, null],
        };

        if ($faculty === null || $student === null) {
            return false;
        }

        return $faculty->teachingSectionIds()
            ->intersect($student->enrolledSectionIds())
            ->isNotEmpty();
    }

    /**
     * Direct conversations this user participates in. The pivot carries the
     * per-user read cursor (last_read_message_id) that backs unread counts.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_message_id')
            ->withTimestamps();
    }

    public function teachingCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'faculty_id');
    }

    /**
     * Sections (offerings) this faculty member teaches. One faculty can teach
     * many sections, across courses and terms.
     */
    public function teachingSections(): HasMany
    {
        return $this->hasMany(Section::class, 'faculty_id');
    }

    /**
     * Course materials this student has marked complete.
     */
    public function completedMaterials(): BelongsToMany
    {
        return $this->belongsToMany(CourseMaterial::class, 'material_completions')
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function classTestAttempts(): HasMany
    {
        return $this->hasMany(ClassTestAttempt::class);
    }

    public function flashcardDecks(): HasMany
    {
        return $this->hasMany(FlashcardDeck::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'user_id');
    }

    /**
     * In-app notifications addressed to this user.
     *
     * Overrides the Notifiable trait's morph relation — this app uses a custom
     * per-recipient notifications table, not Laravel's database channel.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
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

    public function hasPermission(string|Permission $permission): bool
    {
        $permissionSlug = $permission instanceof Permission
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

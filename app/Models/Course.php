<?php

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'department_id',
        'faculty_id',
        'semester',
        'credits',
        'schedule',
        'max_enrollment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'faculty_id' => 'integer',
            'schedule' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * The course's primary (first) section — the single offering in the current
     * flat model. Used by write paths that must attach a record to a section.
     */
    public function primarySection(): ?Section
    {
        return $this->sections()->orderBy('id')->first();
    }

    /**
     * The section of this course that the given faculty member teaches.
     *
     * Used to scope a faculty member's actions and reads to their own offering:
     * when Prof. B uploads a material to a course they teach Section B of, it
     * must land on Section B — not blindly the course's first section. Falls
     * back to the primary section for legacy/owner-only cases.
     */
    public function sectionFor(User $faculty): ?Section
    {
        return $this->sections()
            ->where('faculty_id', $faculty->id)
            ->orderBy('id')
            ->first()
            ?? $this->primarySection();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at', 'term_id', 'section_id'])
            ->withTimestamps()
            ->wherePivot('role', 'student');
    }

    /**
     * Courses that must be completed before registering for this one.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'course_id', 'prerequisite_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

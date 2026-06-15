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

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at'])
            ->withTimestamps()
            ->wherePivot('role', 'student');
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

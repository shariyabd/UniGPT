<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A section (offering): one instance of a catalog Course, taught in a term by a
 * faculty member. Owns the roster, materials, assignments, attendance and exams.
 *
 * @property int $id
 * @property int $course_id
 * @property int|null $term_id
 * @property int|null $faculty_id
 * @property string $label
 * @property array|null $schedule
 * @property int $max_enrollment
 * @property bool $is_active
 */
class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'term_id',
        'faculty_id',
        'label',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user', 'section_id', 'user_id')
            ->withPivot(['role', 'status', 'grade', 'progress', 'enrolled_at', 'term_id'])
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

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}

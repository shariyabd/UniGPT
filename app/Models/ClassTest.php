<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A faculty-authored, in-browser, auto-graded class test / quiz bound to a single
 * section. Distinct from the schedule-only {@see Exam} model.
 *
 * @property int $id
 * @property int $course_id
 * @property int $section_id
 * @property string $title
 * @property string|null $description
 * @property int $duration_minutes
 * @property int $total_marks
 * @property int|null $pass_marks
 * @property string $status
 * @property Carbon|null $available_from
 * @property Carbon|null $available_until
 * @property bool $shuffle_questions
 * @property int $max_warnings
 * @property array<string, bool>|null $security_config
 * @property int|null $created_by
 */
class ClassTest extends Model
{
    use BelongsToSection;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'duration_minutes',
        'total_marks',
        'pass_marks',
        'status',
        'available_from',
        'available_until',
        'shuffle_questions',
        'max_warnings',
        'security_config',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'total_marks' => 'integer',
            'pass_marks' => 'integer',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'shuffle_questions' => 'boolean',
            'max_warnings' => 'integer',
            'security_config' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ClassTestQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ClassTestAttempt::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Whether the test is published and within its (optional) availability window.
     */
    public function isOpen(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();

        return ($this->available_from === null || $this->available_from->lte($now))
            && ($this->available_until === null || $this->available_until->gte($now));
    }
}

<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Enums\ExamType;
use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exam extends Model
{
    use BelongsToSection;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'type',
        'exam_date',
        'start_time',
        'duration_minutes',
        'location',
        'total_marks',
        'instructions',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamType::class,
            'exam_date' => 'date',
            'duration_minutes' => 'integer',
            'total_marks' => 'integer',
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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('exam_date', '>=', now()->toDateString());
    }
}

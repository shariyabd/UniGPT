<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable question in a course's bank — same shape as ClassTestQuestion
 * (type/options/correct_answer/marks) plus topic + difficulty tags, so items
 * flow between the bank, class tests and student practice quizzes.
 */
class QuestionBankItem extends Model
{
    protected $fillable = [
        'course_id',
        'created_by',
        'type',
        'question_text',
        'options',
        'correct_answer',
        'marks',
        'topic',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'created_by' => 'integer',
            'options' => 'array',
            'marks' => 'integer',
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
}

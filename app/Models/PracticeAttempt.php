<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One graded run through a practice quiz (retakes create new attempts).
 */
class PracticeAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'practice_quiz_id',
        'answers',
        'score',
        'total',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'practice_quiz_id' => 'integer',
            'answers' => 'array',
            'score' => 'integer',
            'total' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(PracticeQuiz::class, 'practice_quiz_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A flagged high-similarity pair between two submissions of the same
 * assignment. Rows are stored in both directions, so a submission's flags are
 * always `where('submission_id', ...)`.
 */
class SubmissionSimilarity extends Model
{
    protected $fillable = [
        'assignment_id',
        'submission_id',
        'matched_submission_id',
        'score',
        'coverage',
        'matched_chunks',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'assignment_id' => 'integer',
            'submission_id' => 'integer',
            'matched_submission_id' => 'integer',
            'score' => 'float',
            'coverage' => 'float',
            'matched_chunks' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function matched(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'matched_submission_id');
    }
}

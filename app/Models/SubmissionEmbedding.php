<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One embedded chunk of an assignment submission's text, retained so later
 * submissions to the same assignment can be similarity-screened against it.
 */
class SubmissionEmbedding extends Model
{
    protected $fillable = [
        'assignment_id',
        'assignment_submission_id',
        'chunk_index',
        'excerpt',
        'vector',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'assignment_id' => 'integer',
            'assignment_submission_id' => 'integer',
            'vector' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }
}

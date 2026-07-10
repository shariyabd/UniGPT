<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One peer-review task: a reviewer assigned to a classmate's submission.
 * ⚠️ reviewer_id must never reach the reviewee or their payloads — reviews
 * are anonymous in both directions (reviewers also don't learn authors).
 */
class PeerReview extends Model
{
    protected $fillable = [
        'assignment_id',
        'submission_id',
        'reviewer_id',
        'rating',
        'comments',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('rating');
    }
}

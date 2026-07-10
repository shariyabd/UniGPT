<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Academic\Services\SubmissionSimilarityService;
use App\Models\AssignmentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Similarity-screen a newly (re)submitted assignment submission off the
 * request cycle: extract → chunk → embed → compare against classmates'
 * stored vectors → persist flagged pairs for the grading screen.
 */
class ScreenSubmissionSimilarityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $submissionId) {}

    public function handle(SubmissionSimilarityService $screening): void
    {
        $submission = AssignmentSubmission::find($this->submissionId);

        if ($submission !== null) {
            $screening->screen($submission);
        }
    }
}

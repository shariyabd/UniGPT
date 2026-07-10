<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Infrastructure\FileStorage\DocumentTextExtractor;
use App\Models\AssignmentSubmission;
use Throwable;

/**
 * A submission's comparable/readable plain text: the written answer plus any
 * extractable text from the uploaded file (PDF/DOCX/plain). Shared by
 * similarity screening and AI grade drafting. Extraction failures are
 * non-fatal — callers get whatever could be read.
 */
class SubmissionTextService
{
    public function __construct(private readonly DocumentTextExtractor $extractor) {}

    public function textFor(AssignmentSubmission $submission, ?int $maxChars = null): string
    {
        $parts = [];

        $content = trim((string) $submission->content);
        if ($content !== '') {
            $parts[] = $content;
        }

        if ($submission->file_path !== null) {
            try {
                $extension = strtolower(pathinfo(
                    $submission->original_filename ?? $submission->file_path,
                    PATHINFO_EXTENSION,
                ));
                $pages = $this->extractor->extractFromDisk('local', $submission->file_path, $extension);
                $fileText = trim(implode("\n\n", array_column($pages, 'text')));
                if ($fileText !== '') {
                    $parts[] = $fileText;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $text = implode("\n\n", $parts);

        return $maxChars !== null ? mb_substr($text, 0, $maxChars) : $text;
    }
}

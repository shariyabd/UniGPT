<?php

namespace App\Domain\Chat\Document\Services;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Application service for the document knowledge base: upload, approval
 * workflow, library browsing, and engagement counters.
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentStorageService $storage,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Store an uploaded document in the pending queue.
     *
     * @param  array<string, mixed>  $data
     */
    public function upload(User $uploader, array $data, UploadedFile $file): Document
    {
        $stored = $this->storage->store($file);

        $document = Document::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? $uploader->department_id,
            'category' => $data['category'] ?? 'General',
            'visibility' => $data['visibility'] ?? 'students',
            'tags' => $data['tags'] ?? [],
            'file_path' => $stored['path'],
            'file_type' => $stored['file_type'],
            'file_size' => $stored['file_size'],
            'original_filename' => $stored['original_filename'],
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $uploader->id,
        ]);

        $this->activity->log('document.uploaded', "Uploaded \"{$document->title}\"", $document, [], $uploader);

        return $document;
    }

    public function approve(Document $document, User $reviewer, ?string $comment = null): Document
    {
        $document->update([
            'status' => DocumentStatus::PROCESSING,
            'approved_by' => $reviewer->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->recordReview($document, $reviewer, DocumentApproval::ACTION_APPROVED, $comment);
        $this->activity->log('document.approved', "Approved \"{$document->title}\"", $document, [], $reviewer);

        ProcessDocumentJob::dispatch($document->id);

        return $document;
    }

    public function reject(Document $document, User $reviewer, string $reason): Document
    {
        $document->update([
            'status' => DocumentStatus::REJECTED,
            'approved_by' => $reviewer->id,
            'approved_at' => null,
            'rejection_reason' => $reason,
        ]);

        $document->chunks()->delete();

        $this->recordReview($document, $reviewer, DocumentApproval::ACTION_REJECTED, $reason);
        $this->activity->log('document.rejected', "Rejected \"{$document->title}\"", $document, [], $reviewer);

        return $document;
    }

    public function requestChanges(Document $document, User $reviewer, string $comment): Document
    {
        $document->update(['status' => DocumentStatus::PENDING]);
        $this->recordReview($document, $reviewer, DocumentApproval::ACTION_CHANGES_REQUESTED, $comment);
        $this->activity->log('document.changes_requested', "Requested changes on \"{$document->title}\"", $document, [], $reviewer);

        return $document;
    }

    public function addComment(Document $document, User $reviewer, string $comment): DocumentApproval
    {
        $this->activity->log('document.commented', "Commented on \"{$document->title}\"", $document, [], $reviewer);

        return $this->recordReview($document, $reviewer, DocumentApproval::ACTION_COMMENTED, $comment);
    }

    public function recordView(Document $document): void
    {
        $document->increment('views');
    }

    public function recordDownload(Document $document): void
    {
        $document->increment('downloads');
    }

    public function delete(Document $document): void
    {
        $this->storage->delete($document->file_path);
        $document->delete();
    }

    /**
     * Documents awaiting review (pending / changes-requested).
     *
     * @return Collection<int, Document>
     */
    public function pendingQueue(): Collection
    {
        return Document::with(['uploader', 'department', 'approvals.reviewer'])
            ->where('status', DocumentStatus::PENDING->value)
            ->latest()
            ->get();
    }

    /**
     * Approved documents for the library, optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     */
    public function library(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->applyFilters(Document::approved()->with(['uploader', 'department']), $filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Approved documents visible to a specific user.
     *
     * @param  array<string, mixed>  $filters
     */
    public function libraryFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Document::approved()->visibleTo($user)->with(['uploader', 'department']);

        return $this->applyFilters($query, $filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => Document::count(),
            'approved' => Document::where('status', DocumentStatus::APPROVED->value)->count(),
            'pending' => Document::where('status', DocumentStatus::PENDING->value)->count(),
            'rejected' => Document::where('status', DocumentStatus::REJECTED->value)->count(),
            'processing' => Document::where('status', DocumentStatus::PROCESSING->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['file_type']) && $filters['file_type'] !== 'all') {
            $query->where('file_type', $filters['file_type']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    private function recordReview(Document $document, User $reviewer, string $action, ?string $comment): DocumentApproval
    {
        return $document->approvals()->create([
            'reviewer_id' => $reviewer->id,
            'action' => $action,
            'comment' => $comment,
        ]);
    }
}

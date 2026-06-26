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
        // Hash the contents before storing so we can detect duplicate uploads.
        $fileHash = hash_file('sha256', $file->getRealPath());

        $stored = $this->storage->store($file);

        $document = Document::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'General',
            'visibility' => $data['visibility'] ?? ['students'],
            'tags' => $data['tags'] ?? [],
            'file_path' => $stored['path'],
            'file_type' => $stored['file_type'],
            'file_size' => $stored['file_size'],
            'original_filename' => $stored['original_filename'],
            'file_hash' => $fileHash,
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $uploader->id,
        ]);

        // Empty selection ("All Departments") leaves the pivot empty; otherwise
        // target the chosen departments. Falls back to the uploader's department.
        $departmentIds = $data['department_ids'] ?? [];
        if (empty($departmentIds) && $uploader->department_id && ! array_key_exists('department_ids', $data)) {
            $departmentIds = [$uploader->department_id];
        }
        $document->departments()->sync($departmentIds);

        $this->activity->log('document.uploaded', "Uploaded \"{$document->title}\"", $document, [], $uploader);

        return $document;
    }

    /**
     * Every document a given user has submitted, newest first, with the review
     * trail so they can see decisions and reviewer comments on their own uploads.
     *
     * @return Collection<int, Document>
     */
    public function submissionsFor(User $user): Collection
    {
        return Document::with(['departments', 'approvals.reviewer'])
            ->where('uploaded_by', $user->id)
            ->latest()
            ->get();
    }

    /**
     * Edit a submission's metadata (and optionally its file). Any edit returns
     * the document to the pending queue for fresh review and clears the previous
     * decision, dropping stale indexed chunks.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSubmission(Document $document, array $data, User $editor, ?UploadedFile $file = null): Document
    {
        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? $document->category,
            'tags' => $data['tags'] ?? [],
            'status' => DocumentStatus::PENDING,
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];

        if ($file) {
            $this->storage->delete($document->file_path);
            $stored = $this->storage->store($file);
            $payload['file_path'] = $stored['path'];
            $payload['file_type'] = $stored['file_type'];
            $payload['file_size'] = $stored['file_size'];
            $payload['original_filename'] = $stored['original_filename'];
            $payload['file_hash'] = hash_file('sha256', $file->getRealPath());
        }

        $document->update($payload);
        $document->chunks()->delete();

        if (array_key_exists('department_ids', $data)) {
            $document->departments()->sync($data['department_ids'] ?? []);
        }

        $this->activity->log('document.updated', "Updated \"{$document->title}\"", $document, [], $editor);

        return $document->fresh(['departments', 'approvals.reviewer']);
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
        return Document::with(['uploader', 'departments', 'approvals.reviewer'])
            ->where('status', DocumentStatus::PENDING->value)
            ->latest()
            ->get();
    }

    /**
     * Every document for the approval workflow, in any status, so admins can
     * review the queue and revisit already-decided documents (filtered by
     * status client-side).
     */
    public function reviewQueue(): Collection
    {
        return Document::with(['uploader', 'departments', 'approvals.reviewer'])
            ->latest()
            ->get();
    }

    /**
     * Approved documents for the library, optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Admin document library — shows documents in every status (pending,
     * approved, rejected, …) so admins can see and manage their own uploads,
     * not just approved ones. Status can be narrowed via the `status` filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function library(array $filters = [], int $perPage = 12, ?User $user = null): LengthAwarePaginator
    {
        $query = Document::with(['uploader', 'departments']);

        if ($user) {
            $query->withExists([
                'bookmarkedBy as is_bookmarked' => fn (Builder $bookmarks) => $bookmarks->whereKey($user->id),
            ]);
        }

        return $this->applyFilters($query, $filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Toggle the current user's bookmark on a document.
     *
     * @return bool The bookmark state after toggling (true = bookmarked).
     */
    public function toggleBookmark(User $user, Document $document): bool
    {
        $changes = $user->bookmarkedDocuments()->toggle($document->id);

        return ! empty($changes['attached']);
    }

    /**
     * Approved documents visible to a specific user.
     *
     * @param  array<string, mixed>  $filters
     */
    public function libraryFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Document::approved()->visibleTo($user)
            ->with(['uploader', 'departments'])
            ->withExists([
                'bookmarkedBy as is_bookmarked' => fn (Builder $bookmarks) => $bookmarks->whereKey($user->id),
            ]);

        return $this->applyFilters($query, $filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Stats for the upload page sidebar (today / this week / pending / storage).
     *
     * @return array<string, mixed>
     */
    public function uploadStatistics(): array
    {
        return [
            'today' => Document::whereDate('created_at', today())->count(),
            'this_week' => Document::where('created_at', '>=', now()->startOfWeek())->count(),
            'pending_review' => Document::where('status', DocumentStatus::PENDING->value)->count(),
            'storage_used' => $this->humanBytes((int) Document::sum('file_size')),
        ];
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
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

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->forDepartment((int) $filters['department_id']);
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

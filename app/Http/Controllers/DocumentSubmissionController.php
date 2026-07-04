<?php

namespace App\Http\Controllers;

use App\Domain\Chat\Document\Services\DocumentService;
use App\Http\Requests\StoreDocumentSubmissionRequest;
use App\Http\Requests\UpdateDocumentSubmissionRequest;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty / student document submissions. Users upload documents into the same
 * approval queue admins review, and manage (edit / delete) their own uploads.
 * Shared by the student and faculty route groups; every action is scoped to the
 * authenticated owner.
 */
class DocumentSubmissionController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentStorageService $storage,
    ) {}

    public function index(Request $request): Response
    {
        $prefix = $this->routePrefix($request);

        return Inertia::render('Documents/MySubmissions', [
            'documents' => $this->documents->submissionsFor($request->user())
                ->map(fn (Document $document) => $this->present($document, $prefix))
                ->values(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'categories' => $this->categories(),
            'routeNames' => [
                'store' => $prefix.'my-documents.store',
                'update' => $prefix.'my-documents.update',
                'destroy' => $prefix.'my-documents.destroy',
            ],
        ]);
    }

    public function store(StoreDocumentSubmissionRequest $request): RedirectResponse
    {
        $this->documents->upload(
            $request->user(),
            [...$request->safe()->except('file'), 'visibility' => $this->defaultVisibility($request)],
            $request->file('file'),
        );

        return back()->with('success', 'Document submitted for review.');
    }

    public function update(UpdateDocumentSubmissionRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeOwner($request, $document);

        $this->documents->updateSubmission(
            $document,
            $request->safe()->except('file'),
            $request->user(),
            $request->file('file'),
        );

        return back()->with('success', 'Submission updated and re-queued for review.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOwner($request, $document);

        $this->documents->delete($document);

        return back()->with('success', 'Submission deleted.');
    }

    public function download(Request $request, Document $document)
    {
        $this->authorizeOwner($request, $document);

        $disk = Storage::disk($this->storage->disk());
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        return $disk->download(
            $document->file_path,
            $document->original_filename ?? $document->title,
            ['Content-Type' => DocumentStorageService::contentType($document->file_path)],
        );
    }

    public function preview(Request $request, Document $document)
    {
        $this->authorizeOwner($request, $document);

        $disk = Storage::disk($this->storage->disk());
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        return $disk->response(
            $document->file_path,
            $document->original_filename ?? $document->title,
            ['Content-Type' => DocumentStorageService::contentType($document->file_path)],
            'inline',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Document $document, string $prefix): array
    {
        // Reviewer feedback the uploader should act on (rejections + change
        // requests + general comments), oldest first.
        $comments = $document->approvals
            ->whereIn('action', ['commented', 'changes_requested', 'rejected'])
            ->sortBy('created_at')
            ->map(fn ($approval) => [
                'id' => $approval->id,
                'author' => $approval->reviewer?->name ?? 'Reviewer',
                'action' => $approval->action,
                'content' => $approval->comment,
                'timestamp' => $approval->created_at?->toIso8601String(),
            ])->values()->all();

        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description ?? '',
            'category' => $document->category,
            'departmentIds' => $document->departments->pluck('id')->values(),
            'departments' => $document->departments->isNotEmpty()
                ? $document->departments->pluck('name')->join(', ')
                : 'All Departments',
            'tags' => $document->tags ?? [],
            'status' => $document->status->value,
            'statusLabel' => $document->status->label(),
            'statusColor' => $document->status->color(),
            'rejectionReason' => $document->rejection_reason,
            'fileType' => strtoupper((string) $document->file_type),
            'fileSize' => $this->humanFileSize((int) $document->file_size),
            'originalFilename' => $document->original_filename,
            'version' => $document->version,
            'createdAt' => $document->created_at?->toIso8601String(),
            'updatedAt' => $document->updated_at?->toIso8601String(),
            'comments' => $comments,
            'downloadUrl' => route($prefix.'my-documents.download', $document->id),
            'previewUrl' => route($prefix.'my-documents.preview', $document->id),
        ];
    }

    private function authorizeOwner(Request $request, Document $document): void
    {
        abort_unless($document->uploaded_by === $request->user()->id, 403);
    }

    /**
     * Audience for a submission: students reach students; faculty reach students
     * and faculty. Admins can still re-target on approval.
     *
     * @return array<int, string>
     */
    private function defaultVisibility(Request $request): array
    {
        return $request->user()->isFaculty() ? ['students', 'faculty'] : ['students'];
    }

    /**
     * The student route group is unprefixed; the faculty group is `faculty.`.
     */
    private function routePrefix(Request $request): string
    {
        return $request->routeIs('faculty.*') ? 'faculty.' : '';
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }

    /**
     * @return array<int, string>
     */
    private function categories(): array
    {
        return ['Handbook', 'Syllabus', 'Policy', 'Schedule', 'Lecture Notes', 'Assignment', 'Reading', 'General'];
    }
}

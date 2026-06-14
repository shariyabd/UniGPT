<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Document\Services\DocumentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function upload(): Response
    {
        return Inertia::render('Admin/DocumentUpload', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'categories' => $this->categories(),
            'recentUploads' => DocumentResource::collection(
                Document::with(['uploader', 'department'])->latest()->limit(5)->get()
            ),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $this->documents->upload(
            $request->user(),
            $request->safe()->except('file'),
            $request->file('file'),
        );

        return back()->with('success', 'Document uploaded and queued for review.');
    }

    public function library(Request $request): Response
    {
        $filters = $request->only(['category', 'department_id', 'file_type', 'search']);

        return Inertia::render('Admin/DocumentLibrary', [
            'documents' => DocumentResource::collection($this->documents->library($filters)),
            'filters' => $filters,
            'categories' => $this->categories(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'stats' => $this->documents->statistics(),
        ]);
    }

    public function approvals(): Response
    {
        $pending = $this->documents->pendingQueue()->map(
            fn (Document $document) => $this->presentForApproval($document)
        );

        return Inertia::render('Admin/Approvals', [
            'pendingDocuments' => $pending,
            'stats' => $this->documents->statistics(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentForApproval(Document $document): array
    {
        $uploader = $document->uploader;

        $comments = $document->approvals
            ->whereIn('action', ['commented', 'changes_requested', 'rejected'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'author' => $a->reviewer?->name ?? 'Reviewer',
                'content' => $a->comment,
                'timestamp' => $a->created_at?->toIso8601String(),
                'type' => $a->action === 'rejected' ? 'rejection' : ($a->action === 'changes_requested' ? 'issue' : 'admin_comment'),
            ])->values()->all();

        $history = collect([[
            'action' => 'submitted',
            'by' => $uploader?->name ?? 'Unknown',
            'date' => $document->created_at?->toIso8601String(),
        ]])->merge(
            $document->approvals->map(fn ($a) => [
                'action' => $a->action,
                'by' => $a->reviewer?->name ?? 'Reviewer',
                'date' => $a->created_at?->toIso8601String(),
            ])
        )->all();

        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description ?? '',
            'department' => $document->department?->name ?? '—',
            'category' => $document->category,
            'uploadDate' => $document->created_at?->toIso8601String(),
            'size' => (new DocumentResource($document))->resolve()['fileSize'],
            'fileType' => strtoupper((string) $document->file_type),
            'priority' => 'normal',
            'status' => $document->status->value,
            'tags' => $document->tags ?? [],
            'version' => $document->version,
            'uploadedBy' => [
                'name' => $uploader?->name ?? 'Unknown',
                'email' => $uploader?->email ?? '',
                'role' => $uploader?->getPrimaryRole()?->name ?? 'Member',
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($uploader?->name ?? 'U').'&background=f97316&color=fff',
            ],
            'comments' => $comments,
            'approvalHistory' => $history,
            'downloadUrl' => route('admin.documents.download', $document->id),
        ];
    }

    public function approve(Request $request, Document $document): RedirectResponse
    {
        $this->documents->approve($document, $request->user(), $request->input('comment'));

        return back()->with('success', "\"{$document->title}\" approved and is being indexed.");
    }

    public function reject(Request $request, Document $document): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->documents->reject($document, $request->user(), $request->input('reason'));

        return back()->with('success', "\"{$document->title}\" rejected.");
    }

    public function requestChanges(Request $request, Document $document): RedirectResponse
    {
        $request->validate(['comment' => ['required', 'string', 'max:1000']]);
        $this->documents->requestChanges($document, $request->user(), $request->input('comment'));

        return back()->with('success', 'Changes requested.');
    }

    public function comment(Request $request, Document $document): RedirectResponse
    {
        $request->validate(['comment' => ['required', 'string', 'max:1000']]);
        $this->documents->addComment($document, $request->user(), $request->input('comment'));

        return back()->with('success', 'Comment added.');
    }

    public function download(Document $document)
    {
        $this->documents->recordDownload($document);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename ?? $document->title
        );
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->documents->delete($document);

        return back()->with('success', 'Document deleted.');
    }

    /**
     * @return array<int, string>
     */
    private function categories(): array
    {
        return ['Handbook', 'Syllabus', 'Policy', 'Schedule', 'Lecture Notes', 'Assignment', 'Reading', 'General'];
    }
}

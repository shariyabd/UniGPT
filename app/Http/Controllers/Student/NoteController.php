<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\NoteRequest;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notes = Note::where('user_id', $user->id)
            ->with('course:id,code')
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->get()
            ->map(fn (Note $note) => $this->present($note));

        return Inertia::render('Student/Notes', [
            'notes' => $notes,
            'courses' => $user->enrolledCourses()->get(['courses.id', 'courses.code', 'courses.name']),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $request->user()->notes()->create($request->validated());

        return back()->with('success', 'Note saved.');
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorizeOwner($request, $note);

        $note->update($request->validated());

        return back()->with('success', 'Note updated.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $this->authorizeOwner($request, $note);

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Note $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'isPinned' => $note->is_pinned,
            'courseId' => $note->course_id,
            'courseCode' => $note->course?->code,
            'updatedAt' => $note->updated_at?->diffForHumans(),
        ];
    }

    private function authorizeOwner(Request $request, Note $note): void
    {
        abort_unless($note->user_id === $request->user()->id, 403);
    }
}

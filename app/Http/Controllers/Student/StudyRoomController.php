<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Community\Services\StudyRoomService;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Group study rooms: create/join/leave section-scoped group chats. The chat
 * itself runs on the shared messenger endpoints (history, send, realtime),
 * which authorize by conversation membership.
 */
class StudyRoomController extends Controller
{
    public function __construct(private readonly StudyRoomService $rooms) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Student/StudyRooms', [
            'rooms' => $this->rooms->roomsFor($user),
            'sections' => $this->rooms->sectionsFor($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $this->rooms->create($request->user(), $validated);

        return back()->with('success', 'Study room created.');
    }

    public function join(Request $request, Conversation $room): RedirectResponse
    {
        $this->rooms->join($request->user(), $room);

        return back()->with('success', 'Joined the study room.');
    }

    public function leave(Request $request, Conversation $room): RedirectResponse
    {
        $this->rooms->leave($request->user(), $room);

        return back()->with('success', 'Left the study room.');
    }

    /**
     * Member directory (id → name) for rendering sender names in the room.
     */
    public function members(Request $request, Conversation $room): JsonResponse
    {
        abort_unless($room->isGroup(), 404);
        Gate::authorize('view', $room);

        return response()->json([
            'members' => $this->rooms->members($room),
        ]);
    }
}

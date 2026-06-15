<?php

namespace App\Http\Controllers;

use App\Domain\Notification\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $items = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Notification $n) => $this->notifications->present($n))
            ->values();

        return Inertia::render('Notifications/Index', [
            'notifications' => $items,
            'unreadCount' => $this->notifications->unreadCountFor($user),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorizeOwner($request, $notification);

        $this->notifications->markRead($notification);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorizeOwner($request, $notification);

        $notification->delete();

        return back();
    }

    private function authorizeOwner(Request $request, Notification $notification): void
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
    }
}

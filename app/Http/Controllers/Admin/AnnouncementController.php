<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendAnnouncementRequest;
use App\Models\Notification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $recent = Notification::where('type', NotificationType::ANNOUNCEMENT->value)
            ->latest()
            ->limit(50)
            ->get()
            ->groupBy(fn (Notification $n) => $n->created_at?->toIso8601String().'|'.$n->title)
            ->map(fn ($group) => [
                'title' => $group->first()->title,
                'message' => $group->first()->message,
                'recipients' => $group->count(),
                'time' => $group->first()->created_at?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Admin/Announcements', [
            'audiences' => [
                ['value' => 'all', 'label' => 'Everyone'],
                ['value' => 'student', 'label' => 'Students'],
                ['value' => 'faculty', 'label' => 'Faculty'],
                ['value' => 'admin', 'label' => 'Admins'],
            ],
            'recent' => $recent,
        ]);
    }

    public function store(SendAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $audience = $validated['audience'];

        $recipients = User::query()
            ->active()
            ->when($audience !== 'all', fn ($query) => $query->withRole($audience))
            ->get();

        $count = $this->notifications->notifyMany(
            users: $recipients,
            type: NotificationType::ANNOUNCEMENT,
            title: $validated['title'],
            message: $validated['message'],
            link: route('notifications.index'),
        );

        $this->activity->log('announcement.sent', 'Broadcast an announcement', null, [
            'audience' => $audience,
            'recipients' => $count,
        ], $request->user());

        return back()->with('success', "Announcement sent to {$count} recipient(s).");
    }
}

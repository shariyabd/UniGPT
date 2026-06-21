<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
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
                // Identifies the group for editing: every row in a broadcast shares
                // the same created_at + title, so the pair targets all recipients.
                'createdAt' => $group->first()->created_at?->format('Y-m-d H:i:s'),
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

    /**
     * Edit a sent announcement's title and message across every recipient row.
     * The audience (who received it) is fixed once broadcast — only the content
     * changes. The group is targeted by its shared created_at + original title.
     */
    public function update(UpdateAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $updated = Notification::where('type', NotificationType::ANNOUNCEMENT->value)
            ->where('created_at', $validated['created_at'])
            ->where('title', $validated['original_title'])
            ->update([
                'title' => $validated['title'],
                'message' => $validated['message'],
            ]);

        if ($updated === 0) {
            return back()->with('error', 'Announcement not found — it may have been removed.');
        }

        $this->activity->log('announcement.updated', 'Edited an announcement', null, [
            'recipients' => $updated,
        ], $request->user());

        return back()->with('success', "Announcement updated for {$updated} recipient(s).");
    }
}

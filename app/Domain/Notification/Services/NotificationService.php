<?php

namespace App\Domain\Notification\Services;

use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Support\Collection;

/**
 * In-app notification delivery and querying.
 *
 * Notifications are persisted per-recipient and surfaced through the shared
 * Inertia props (unread badge + dropdown) and a dedicated index page.
 */
class NotificationService
{
    /**
     * Deliver a notification to a single user.
     *
     * @param  array<string, mixed>  $data
     */
    public function notify(
        User $user,
        NotificationType $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        array $data = [],
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data ?: null,
        ]);
    }

    /**
     * Deliver the same notification to many users (e.g. an enrolled roster).
     *
     * @param  Collection<int, User>|array<int, User>  $users
     * @param  array<string, mixed>  $data
     * @return int number of notifications created
     */
    public function notifyMany(
        Collection|array $users,
        NotificationType $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        array $data = [],
    ): int {
        $rows = collect($users)
            ->filter()
            ->unique('id')
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'type' => $type->value,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'data' => $data ? json_encode($data) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return 0;
        }

        Notification::insert($rows);

        return count($rows);
    }

    /**
     * Recent notifications for a user (for the bell dropdown / shared props).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentFor(User $user, int $limit = 8): Collection
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Notification $n) => $this->present($n));
    }

    public function unreadCountFor(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }

    public function markRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type->value,
            'icon' => $notification->type->getIcon(),
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'read' => $notification->isRead(),
            'time' => $notification->created_at?->diffForHumans(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}

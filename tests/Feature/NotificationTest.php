<?php

namespace Tests\Feature;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    private function admin(): User
    {
        $admin = User::where('email', 'admin@university.edu')->first();

        if (! $admin) {
            $this->markTestSkipped('Demo admin not seeded; run php artisan db:seed.');
        }

        return $admin;
    }

    private function makeNotification(User $user, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => NotificationType::SYSTEM,
            'title' => 'Test notification',
            'message' => 'Body',
        ], $overrides));
    }

    public function test_service_reports_unread_count_and_recent(): void
    {
        $student = $this->student();
        $this->makeNotification($student);
        $this->makeNotification($student, ['read_at' => now()]);

        $service = app(NotificationService::class);

        $this->assertSame(1, $service->unreadCountFor($student));
        $this->assertGreaterThanOrEqual(2, $service->recentFor($student)->count());
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $student = $this->student();
        $notification = $this->makeNotification($student);

        $this->actingAs($student)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_read(): void
    {
        $student = $this->student();
        $this->makeNotification($student);
        $this->makeNotification($student);

        $this->actingAs($student)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertSame(0, app(NotificationService::class)->unreadCountFor($student));
    }

    public function test_user_cannot_modify_another_users_notification(): void
    {
        $student = $this->student();
        $admin = $this->admin();
        $notification = $this->makeNotification($admin);

        $this->actingAs($student)
            ->post("/notifications/{$notification->id}/read")
            ->assertForbidden();
    }

    public function test_user_can_delete_a_notification(): void
    {
        $student = $this->student();
        $notification = $this->makeNotification($student);

        $this->actingAs($student)
            ->delete("/notifications/{$notification->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_admin_can_broadcast_an_announcement_to_students(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->post('/admin/announcements', [
                'audience' => 'student',
                'title' => 'Reading week',
                'message' => 'Campus closed next week.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => NotificationType::ANNOUNCEMENT->value,
            'title' => 'Reading week',
        ]);
    }

    public function test_student_cannot_send_announcements(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->get('/admin/announcements')
            ->assertRedirect(); // role middleware bounces non-admins
    }

    public function test_notifications_index_loads(): void
    {
        $student = $this->student();
        $this->makeNotification($student);

        $this->actingAs($student)
            ->get('/notifications')
            ->assertOk();
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds admin-broadcast announcements. Each announcement fans out to one
 * notification row per recipient (exactly as AnnouncementController does via
 * NotificationService), so the admin Announcements page shows grouped broadcasts
 * with recipient counts, and every user's notification bell/index is populated.
 *
 * The demo student's copies are marked read so the unread-count contract in
 * NotificationTest stays exact; everyone else keeps them unread for a realistic
 * inbox.
 */
class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        if (Notification::where('type', NotificationType::ANNOUNCEMENT->value)->exists()) {
            $this->command->info('   Announcements already seeded; skipping.');

            return;
        }

        // [title, message, audience, days ago]
        $announcements = [
            ['Welcome to the Summer 2026 Semester', 'Classes begin this week. Check your dashboard for your enrolled courses, sections and class schedule.', 'all', 18],
            ['Library Extended Hours During Exams', 'The central library will remain open until midnight throughout the examination period. Plan your study sessions accordingly.', 'student', 12],
            ['Midterm Examination Schedule Published', 'Midterm examinations begin in two weeks. Review your personalised timetable on the Exams page.', 'student', 7],
            ['Faculty Meeting — Curriculum Review', 'All faculty members are requested to attend the curriculum review meeting this Thursday at 3:00 PM in the conference hall.', 'faculty', 5],
            ['Scheduled System Maintenance', 'UniNexus will undergo scheduled maintenance this weekend. Brief downtime is expected on Saturday night.', 'all', 2],
        ];

        $link = route('notifications.index');
        $now = Carbon::now();
        $total = 0;

        foreach ($announcements as [$title, $message, $audience, $daysAgo]) {
            $recipients = User::query()
                ->active()
                ->when($audience !== 'all', fn ($query) => $query->withRole($audience))
                ->pluck('id');

            $sentAt = $now->copy()->subDays($daysAgo);

            $rows = $recipients->map(fn (int $id): array => [
                'user_id' => $id,
                'type' => NotificationType::ANNOUNCEMENT->value,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'data' => null,
                'read_at' => null,
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ])->all();

            foreach (array_chunk($rows, 1000) as $chunk) {
                Notification::insert($chunk);
            }

            $total += count($rows);
        }

        // Keep the demo student's unread count deterministic for the test suite.
        $demoStudentId = User::where('email', 'student@university.edu')->value('id');
        if ($demoStudentId) {
            Notification::where('user_id', $demoStudentId)
                ->where('type', NotificationType::ANNOUNCEMENT->value)
                ->update(['read_at' => $now]);
        }

        $this->command->info("   ✓ Announcements seeded ({$total} notifications across ".count($announcements).' broadcasts)');
    }
}

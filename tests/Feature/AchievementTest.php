<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Analytics\Services\AchievementService;
use App\Domain\User\Models\User;
use App\Enums\Achievement;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\ChatSession;
use App\Models\Department;
use App\Models\Note;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Achievements: earned once when a student crosses a badge threshold, notified,
 * idempotent, and surfaced (earned + locked with progress) on the page.
 */
class AchievementTest extends TestCase
{
    use DatabaseTransactions;

    private function freshStudent(): User
    {
        $student = User::create([
            'name' => 'Badge Tester',
            'email' => 'badge.tester@university.edu',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => Department::query()->value('id'),
        ]);
        $student->assignRole(UserRole::STUDENT);

        return $student;
    }

    private function service(): AchievementService
    {
        return app(AchievementService::class);
    }

    public function test_crossing_a_threshold_awards_and_notifies_the_badge(): void
    {
        $student = $this->freshStudent();

        // A single chat session unlocks "Curious Mind".
        ChatSession::create(['user_id' => $student->id, 'title' => 'First question']);

        $newlyEarned = $this->service()->evaluate($student);

        $this->assertTrue($newlyEarned->contains(Achievement::CHAT_FIRST));
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement' => Achievement::CHAT_FIRST->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => NotificationType::ACHIEVEMENT->value,
        ]);
    }

    public function test_award_is_idempotent(): void
    {
        $student = $this->freshStudent();
        foreach (range(1, 10) as $i) {
            Note::create(['user_id' => $student->id, 'title' => "Note {$i}"]);
        }

        $first = $this->service()->evaluate($student);
        $this->assertTrue($first->contains(Achievement::NOTES_TEN));

        // A second pass earns nothing new and creates no duplicate row.
        $second = $this->service()->evaluate($student);
        $this->assertFalse($second->contains(Achievement::NOTES_TEN));
        $this->assertSame(
            1,
            UserAchievement::where('user_id', $student->id)
                ->where('achievement', Achievement::NOTES_TEN->value)
                ->count(),
        );
    }

    public function test_locked_badges_report_progress_but_are_not_awarded(): void
    {
        $student = $this->freshStudent();
        foreach (range(1, 4) as $i) {
            Note::create(['user_id' => $student->id, 'title' => "Note {$i}"]);
        }

        $payload = $this->service()->forUser($student);

        $notesBadge = collect($payload['badges'])->firstWhere('key', Achievement::NOTES_TEN->value);
        $this->assertNotNull($notesBadge);
        $this->assertFalse($notesBadge['earned']);
        $this->assertSame(4, $notesBadge['progress']);
        $this->assertSame(10, $notesBadge['threshold']);
        $this->assertSame(40, $notesBadge['percent']);

        // A fresh student has no activity, so the 30-day streak stays locked.
        $streakBadge = collect($payload['badges'])->firstWhere('key', Achievement::STREAK_30->value);
        $this->assertFalse($streakBadge['earned']);
    }

    public function test_achievements_page_loads_for_a_student(): void
    {
        $student = $this->freshStudent();

        $this->actingAs($student)
            ->get('/achievements')
            ->assertOk();
    }
}

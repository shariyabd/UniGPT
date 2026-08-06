<?php

declare(strict_types=1);

namespace App\Enums;

use App\Domain\Analytics\Services\AchievementService;

/**
 * The code-defined catalogue of student achievements ("badges").
 *
 * Each case maps to a single measurable signal (`metric`) and a `threshold` the
 * student must reach to earn it. Metrics are computed once per evaluation by
 * {@see AchievementService}, so adding a badge is
 * just adding a case whose metric the snapshot already produces.
 */
enum Achievement: string
{
    // Study streaks (consecutive active days).
    case STREAK_3 = 'streak_3';
    case STREAK_7 = 'streak_7';
    case STREAK_30 = 'streak_30';

    // Practice self-quizzing.
    case PRACTICE_FIRST = 'practice_first';
    case PRACTICE_TEN = 'practice_ten';
    case PRACTICE_PERFECT = 'practice_perfect';

    // Spaced-repetition flashcards.
    case FLASHCARDS_LEARNED = 'flashcards_learned';

    // Assignments.
    case ASSIGNMENT_FIRST = 'assignment_first';
    case ASSIGNMENT_TEN = 'assignment_ten';

    // Proctored class tests.
    case CLASS_TEST_FIRST = 'class_test_first';

    // Overall effort (leaderboard XP) and engagement.
    case XP_500 = 'xp_500';
    case XP_2000 = 'xp_2000';
    case NOTES_TEN = 'notes_ten';
    case CHAT_FIRST = 'chat_first';

    /**
     * The snapshot metric this badge is measured against.
     */
    public function metric(): string
    {
        return match ($this) {
            self::STREAK_3, self::STREAK_7, self::STREAK_30 => 'currentStreak',
            self::PRACTICE_FIRST, self::PRACTICE_TEN => 'practiceAttempts',
            self::PRACTICE_PERFECT => 'perfectPractice',
            self::FLASHCARDS_LEARNED => 'flashcardsLearned',
            self::ASSIGNMENT_FIRST, self::ASSIGNMENT_TEN => 'assignmentsSubmitted',
            self::CLASS_TEST_FIRST => 'classTestsTaken',
            self::XP_500, self::XP_2000 => 'xp',
            self::NOTES_TEN => 'notesCount',
            self::CHAT_FIRST => 'chatSessions',
        };
    }

    /**
     * The value of the metric at which the badge is earned.
     */
    public function threshold(): int
    {
        return match ($this) {
            self::STREAK_3 => 3,
            self::STREAK_7 => 7,
            self::STREAK_30 => 30,
            self::PRACTICE_FIRST => 1,
            self::PRACTICE_TEN => 10,
            self::PRACTICE_PERFECT => 1,
            self::FLASHCARDS_LEARNED => 10,
            self::ASSIGNMENT_FIRST => 1,
            self::ASSIGNMENT_TEN => 10,
            self::CLASS_TEST_FIRST => 1,
            self::XP_500 => 500,
            self::XP_2000 => 2000,
            self::NOTES_TEN => 10,
            self::CHAT_FIRST => 1,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::STREAK_3 => 'Getting Started',
            self::STREAK_7 => 'On a Roll',
            self::STREAK_30 => 'Unstoppable',
            self::PRACTICE_FIRST => 'First Steps',
            self::PRACTICE_TEN => 'Quiz Machine',
            self::PRACTICE_PERFECT => 'Flawless',
            self::FLASHCARDS_LEARNED => 'Memory Master',
            self::ASSIGNMENT_FIRST => 'Submitted!',
            self::ASSIGNMENT_TEN => 'Diligent',
            self::CLASS_TEST_FIRST => 'Test Taker',
            self::XP_500 => 'Rising Star',
            self::XP_2000 => 'Scholar',
            self::NOTES_TEN => 'Note Taker',
            self::CHAT_FIRST => 'Curious Mind',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::STREAK_3 => 'Stay active 3 days in a row.',
            self::STREAK_7 => 'Keep a 7-day study streak going.',
            self::STREAK_30 => 'Reach a 30-day study streak.',
            self::PRACTICE_FIRST => 'Complete your first practice quiz.',
            self::PRACTICE_TEN => 'Complete 10 practice quizzes.',
            self::PRACTICE_PERFECT => 'Score 100% on a practice quiz.',
            self::FLASHCARDS_LEARNED => 'Learn 10 flashcards with spaced repetition.',
            self::ASSIGNMENT_FIRST => 'Submit your first assignment.',
            self::ASSIGNMENT_TEN => 'Submit 10 assignments.',
            self::CLASS_TEST_FIRST => 'Complete your first class test.',
            self::XP_500 => 'Earn 500 leaderboard XP.',
            self::XP_2000 => 'Earn 2,000 leaderboard XP.',
            self::NOTES_TEN => 'Write 10 notes.',
            self::CHAT_FIRST => 'Ask the AI copilot your first question.',
        };
    }

    /**
     * Grouping shown in the UI.
     */
    public function category(): string
    {
        return match ($this) {
            self::STREAK_3, self::STREAK_7, self::STREAK_30 => 'Consistency',
            self::PRACTICE_FIRST, self::PRACTICE_TEN, self::PRACTICE_PERFECT => 'Practice',
            self::FLASHCARDS_LEARNED, self::NOTES_TEN => 'Study Tools',
            self::ASSIGNMENT_FIRST, self::ASSIGNMENT_TEN, self::CLASS_TEST_FIRST => 'Academics',
            self::XP_500, self::XP_2000, self::CHAT_FIRST => 'Engagement',
        };
    }

    /**
     * Tier drives the badge colour in the UI.
     */
    public function tier(): string
    {
        return match ($this) {
            self::STREAK_30, self::XP_2000, self::PRACTICE_TEN, self::ASSIGNMENT_TEN => 'gold',
            self::STREAK_7, self::XP_500, self::FLASHCARDS_LEARNED, self::PRACTICE_PERFECT, self::NOTES_TEN => 'silver',
            default => 'bronze',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::STREAK_3, self::STREAK_7, self::STREAK_30 => 'FireIcon',
            self::PRACTICE_FIRST, self::PRACTICE_TEN => 'ClipboardDocumentCheckIcon',
            self::PRACTICE_PERFECT => 'StarIcon',
            self::FLASHCARDS_LEARNED => 'RectangleStackIcon',
            self::ASSIGNMENT_FIRST, self::ASSIGNMENT_TEN => 'DocumentCheckIcon',
            self::CLASS_TEST_FIRST => 'PencilSquareIcon',
            self::XP_500, self::XP_2000 => 'TrophyIcon',
            self::NOTES_TEN => 'PencilIcon',
            self::CHAT_FIRST => 'SparklesIcon',
        };
    }

    /**
     * Serialisable definition for the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->value,
            'title' => $this->title(),
            'description' => $this->description(),
            'category' => $this->category(),
            'tier' => $this->tier(),
            'icon' => $this->icon(),
            'metric' => $this->metric(),
            'threshold' => $this->threshold(),
        ];
    }
}

# Achievements / Badges (student gamification)

**Status:** COMPLETE (live) · **Shipped:** 2026-08-06 · **Role:** Student

Opt-out-free badge layer built on top of the signals the app already records —
leaderboard XP, study streak, practice quizzes, flashcards, assignments, class
tests, notes, and AI chat. Awards are permanent, earned once, and delivered as
in-app notifications.

## How it works

- **Catalogue is code-defined.** `App\Enums\Achievement` holds 14 badges across
  5 categories (Consistency, Practice, Study Tools, Academics, Engagement) and 3
  tiers (bronze/silver/gold). Each case maps to a single `metric()` and a
  `threshold()`. Adding a badge = adding one enum case whose metric the snapshot
  already produces.
- **Evaluation.** `App\Domain\Analytics\Services\AchievementService`:
  - `snapshot(User)` computes every metric in a handful of bounded queries
    (`currentStreak`, `xp`, `practiceAttempts`, `perfectPractice`,
    `flashcardsLearned`, `assignmentsSubmitted`, `classTestsTaken`, `notesCount`,
    `chatSessions`). The `xp` calc mirrors `LeaderboardService` so badges and the
    leaderboard agree on "XP".
  - `evaluate(User)` awards any newly-qualified badge (unique row in
    `user_achievements`), sends a `NotificationType::ACHIEVEMENT` notification,
    and returns the freshly-earned set. Idempotent — a second pass awards nothing.
  - `forUser(User)` runs `evaluate()` then returns the full catalogue with
    earned state + per-badge progress/percent for the UI.
- **Where it runs.** The Achievements page (`achievements` route) awards on view,
  and `StudentDashboardController::index` also calls `evaluate()` so badges are
  earned during normal use, not only when the page is opened.

## Surfaces

- **Page:** `resources/js/pages/Student/Achievements.vue` — progress summary,
  tier-coloured medallions grouped by category; locked badges show a progress bar.
- **Nav:** AppLayout → Community → Achievements (`SparklesIcon`).
- **Notifications:** new `NotificationType::ACHIEVEMENT` (TrophyIcon), delivered
  through the existing `NotificationService`.

## Data

- Migration `2026_07_18_000001_create_user_achievements_table` — `user_achievements`
  (`user_id`, `achievement`, `earned_at`; unique `user_id`+`achievement`).
- Model `App\Models\UserAchievement` (`achievement` cast to the enum);
  `User::achievements()` relation.
- Definitions live in code, **not** the DB — awards stay valid even if a
  threshold later changes.

## Seeding

`AchievementSeeder` (last in `DatabaseSeeder`, after every signal-producing
seeder) evaluates the demo student + leaderboard opt-ins — a curated cohort, so
seeding stays fast and avoids a full-population notification storm.

## Files

- `app/Enums/Achievement.php`, `app/Enums/NotificationType.php`
- `app/Domain/Analytics/Services/AchievementService.php`
- `app/Http/Controllers/Student/AchievementController.php`
- `app/Models/UserAchievement.php`, `app/Domain/User/Models/User.php`
- `routes/web.php`, `resources/js/Layouts/AppLayout.vue`
- `resources/js/pages/Student/Achievements.vue`
- `database/migrations/2026_07_18_000001_create_user_achievements_table.php`
- `database/seeders/AchievementSeeder.php`, `database/seeders/DatabaseSeeder.php`

## Tests

`tests/Feature/AchievementTest.php` — award+notify on threshold, idempotency,
progress-on-locked badges, page load. (4 passed.)

## Notes

- The "study streak" is **real** (derived from `ActivityLog`); an earlier audit
  claim that it was faked was verified false, so badges were layered on top
  rather than "fixing" a non-bug.

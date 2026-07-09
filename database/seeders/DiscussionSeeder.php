<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a course/section discussion feed: posts by section members, replies and
 * likes, plus a couple of open reports for the admin moderation queue. The demo
 * student's own sections are always included so the demo login sees an active
 * feed.
 */
class DiscussionSeeder extends Seeder
{
    private const POSTS = [
        'Has anyone started the latest assignment yet? Struggling with where to begin.',
        'Great lecture today — the worked example on the board really cleared things up.',
        'Sharing my notes from this week in case anyone missed the session.',
        'Reminder: the class test window closes this weekend. Don\'t leave it to the last minute!',
        'Can someone explain the difference between the two approaches from Chapter 4?',
        'Study group forming for the upcoming exam — reply if you want in.',
        'Found a really helpful resource for this topic, linking it below.',
        'Is the deadline for the project the same as on the syllabus?',
    ];

    private const COMMENTS = [
        'Same here — let\'s figure it out together.',
        'Thanks for sharing, this is really helpful!',
        'I think the key is to focus on the core concept first.',
        'Office hours are on Tuesday if you want to ask directly.',
        'Good point, I hadn\'t thought of it that way.',
        'Count me in for the study group.',
    ];

    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            return;
        }

        $limit = (int) config('seeder.discussion_sections', 40);
        $postsPerSection = (int) config('seeder.posts_per_section', 4);
        $now = Carbon::now();

        $sectionIds = $this->targetSectionIds($term, $limit);

        $sections = Section::whereIn('id', $sectionIds)
            ->with(['students' => fn ($q) => $q->wherePivot('status', 'enrolled'), 'faculty'])
            ->get();

        $createdPosts = [];

        foreach ($sections as $section) {
            $members = $section->students->pluck('id')->all();
            if ($section->faculty) {
                $members[] = $section->faculty->id;
            }
            if ($members === []) {
                continue;
            }

            for ($i = 0; $i < $postsPerSection; $i++) {
                $authorId = $members[array_rand($members)];
                $createdAt = $now->copy()->subDays(random_int(0, 20))->subHours(random_int(0, 23));

                $post = Post::create([
                    'section_id' => $section->id,
                    'user_id' => $authorId,
                    'body' => self::POSTS[array_rand(self::POSTS)],
                    'is_pinned' => $i === 0 && random_int(1, 3) === 1,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $createdPosts[] = $post->id;

                $this->seedComments($post->id, $members, $createdAt);
                $this->seedLikes($post->id, $members, $now);
            }
        }

        $this->seedReports($createdPosts, $now);

        $this->command->info('   ✓ Discussion posts seeded ('.count($createdPosts).')');
    }

    /**
     * Demo student's sections first (so the demo login sees a live feed), then
     * fill up to the limit with other current-term sections that have students.
     *
     * @return array<int, int>
     */
    private function targetSectionIds(Term $term, int $limit): array
    {
        $demo = User::where('email', 'student@university.edu')->first();
        $demoSectionIds = $demo ? $demo->enrolledSectionIds()->all() : [];

        $others = Section::where('term_id', $term->id)
            ->whereHas('students', fn ($q) => $q->where('course_user.status', 'enrolled'))
            ->whereNotIn('id', $demoSectionIds)
            ->limit(max(0, $limit - count($demoSectionIds)))
            ->pluck('id')
            ->all();

        return array_values(array_unique([...$demoSectionIds, ...$others]));
    }

    /**
     * @param  array<int, int>  $members
     */
    private function seedComments(int $postId, array $members, Carbon $postedAt): void
    {
        $rows = [];
        $count = random_int(0, 3);
        for ($c = 0; $c < $count; $c++) {
            $at = $postedAt->copy()->addHours(random_int(1, 40));
            $rows[] = [
                'post_id' => $postId,
                'user_id' => $members[array_rand($members)],
                'body' => self::COMMENTS[array_rand(self::COMMENTS)],
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        if ($rows !== []) {
            DB::table('post_comments')->insert($rows);
        }
    }

    /**
     * @param  array<int, int>  $members
     */
    private function seedLikes(int $postId, array $members, Carbon $now): void
    {
        $likers = collect($members)->shuffle()->take(random_int(0, min(5, count($members))));

        $rows = $likers->map(fn (int $userId) => [
            'post_id' => $postId,
            'user_id' => $userId,
            'type' => 'like',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('post_reactions')->insertOrIgnore($rows);
        }
    }

    /**
     * @param  array<int, int>  $postIds
     */
    private function seedReports(array $postIds, Carbon $now): void
    {
        if (count($postIds) < 3) {
            return;
        }

        $reasons = ['Off-topic / spam', 'Possible plagiarism', 'Inappropriate language'];

        foreach (array_slice($postIds, 0, 3) as $index => $postId) {
            $post = Post::find($postId);
            if (! $post) {
                continue;
            }

            PostReport::firstOrCreate(
                ['post_id' => $postId],
                [
                    'reporter_id' => $post->user_id,
                    'reason' => $reasons[$index % count($reasons)],
                    'status' => 'open',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}

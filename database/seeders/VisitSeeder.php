<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds a realistic page-visit history so Admin → User Activity is populated on a
 * fresh install: authenticated visits across students/faculty/admins plus anonymous
 * landing-page hits arriving from external referrers, spread over the last two weeks
 * with varied devices and locations.
 *
 * Skips once the table already holds a meaningful volume (real traffic or a prior
 * seed), so it populates a fresh install but never piles onto live data.
 */
class VisitSeeder extends Seeder
{
    /** Below this row count the table is considered "empty enough" to seed. */
    private const SEED_THRESHOLD = 50;

    /** @var array<int, array{0: string, 1: string}> country / city pairs */
    private const LOCATIONS = [
        ['Bangladesh', 'Dhaka'], ['Bangladesh', 'Chittagong'], ['Bangladesh', 'Sylhet'],
        ['India', 'Kolkata'], ['India', 'Delhi'], ['United States', 'New York'],
        ['United Kingdom', 'London'], ['Canada', 'Toronto'], ['Malaysia', 'Kuala Lumpur'],
        ['Australia', 'Sydney'], ['Germany', 'Berlin'], ['United Arab Emirates', 'Dubai'],
    ];

    /** @var array<int, array{0: string, 1: string, 2: string}> device_type / platform / browser */
    private const DEVICES = [
        ['desktop', 'Windows', 'Chrome'], ['desktop', 'macOS', 'Safari'],
        ['desktop', 'Linux', 'Firefox'], ['desktop', 'Windows', 'Edge'],
        ['mobile', 'iOS', 'Safari'], ['mobile', 'Android', 'Chrome'],
        ['tablet', 'iOS', 'Safari'], ['mobile', 'Android', 'Samsung Internet'],
    ];

    /** @var array<int, string|null> external referrers (null = direct / internal) */
    private const REFERRERS = [
        'https://www.google.com/', 'https://www.google.com/', 'https://www.facebook.com/',
        'https://t.co/', 'https://www.linkedin.com/', 'https://l.instagram.com/',
        'https://www.bing.com/', 'https://chatgpt.com/', null, null, null,
    ];

    /** @var array<string, array<int, array{0: string, 1: string, 2: string}>> route / label / path per role */
    private const PAGES = [
        'student' => [
            ['dashboard', 'Student Dashboard', 'dashboard'],
            ['chat', 'AI Chat', 'chat'],
            ['progress', 'My Progress', 'progress'],
            ['practice.index', 'Practice Quizzes', 'practice'],
            ['leaderboard.index', 'Leaderboard', 'leaderboard'],
        ],
        'faculty' => [
            ['faculty.dashboard', 'Faculty Dashboard', 'faculty/dashboard'],
            ['faculty.analytics', 'Analytics', 'faculty/analytics'],
            ['faculty.ai-assistant', 'AI Assistant', 'faculty/ai-assistant'],
        ],
        'admin' => [
            ['admin.dashboard', 'Admin Dashboard', 'admin/dashboard'],
            ['admin.users', 'User Management', 'admin/users'],
            ['admin.analytics', 'Analytics', 'admin/analytics'],
        ],
    ];

    public function run(): void
    {
        if (Visit::query()->count() >= self::SEED_THRESHOLD) {
            $this->command->info('Visits already populated — skipping VisitSeeder.');

            return;
        }

        $this->command->info('Seeding demo page-visit history...');

        $rows = [];

        // Authenticated visits: a sample of each role browsing their own pages.
        foreach (['student' => 40, 'faculty' => 12, 'admin' => 4] as $role => $limit) {
            $users = User::query()
                ->whereHas('roles', fn ($q) => $q->where('slug', $role))
                ->inRandomOrder()
                ->limit($limit)
                ->get(['id']);

            foreach ($users as $user) {
                $sessionId = bin2hex(random_bytes(16));

                foreach (range(1, random_int(2, 7)) as $ignored) {
                    $page = self::PAGES[$role][array_rand(self::PAGES[$role])];
                    $rows[] = $this->row(
                        userId: $user->id,
                        sessionId: $sessionId,
                        page: $page,
                        // Logged-in navigation is mostly internal, so referrers stay null.
                        referrer: random_int(1, 5) === 1 ? self::REFERRERS[array_rand(self::REFERRERS)] : null,
                    );
                }
            }
        }

        // Anonymous landing/login hits arriving from external sources.
        foreach (range(1, 120) as $ignored) {
            $page = random_int(1, 4) === 1
                ? ['login', 'Login', 'login']
                : ['home', 'Home (Landing)', '/'];

            $rows[] = $this->row(
                userId: null,
                sessionId: bin2hex(random_bytes(16)),
                page: $page,
                referrer: self::REFERRERS[array_rand(self::REFERRERS)],
            );
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            Visit::insert($chunk);
        }

        $this->command->info('Seeded '.count($rows).' demo visits.');
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $page  route / label / path
     * @return array<string, mixed>
     */
    private function row(?int $userId, string $sessionId, array $page, ?string $referrer): array
    {
        [$country, $city] = self::LOCATIONS[array_rand(self::LOCATIONS)];
        [$device, $platform, $browser] = self::DEVICES[array_rand(self::DEVICES)];

        $when = Carbon::now()
            ->subDays(random_int(0, 13))
            ->setTime(random_int(7, 22), random_int(0, 59), random_int(0, 59));

        return [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'path' => $page[2],
            'route_name' => $page[0],
            'label' => $page[1],
            'referrer' => $referrer,
            'ip_address' => sprintf('%d.%d.%d.%d', random_int(11, 220), random_int(0, 255), random_int(0, 255), random_int(1, 254)),
            'device_type' => $device,
            'platform' => $platform,
            'browser' => $browser,
            'country' => $country,
            'city' => $city,
            'user_agent' => "{$browser} on {$platform}",
            'created_at' => $when,
        ];
    }
}

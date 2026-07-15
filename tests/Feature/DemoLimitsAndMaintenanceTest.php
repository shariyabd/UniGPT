<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Http\Middleware\HandleMaintenanceMode;
use App\Infrastructure\AI\MockProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Two operator-facing controls:
 *   1. Demo accounts are capped at User::DEMO_AI_REQUEST_LIMIT AI requests.
 *   2. A hidden ?live=false / ?live=true switch toggles Maintenance Mode.
 */
class DemoLimitsAndMaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic, keyless AI so the chat endpoint always succeeds.
        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function demoStudent(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        return $student;
    }

    public function test_demo_mode_caps_ai_requests_at_the_limit(): void
    {
        config(['app.demo' => true]); // APP_MODE=demo

        $student = $this->demoStudent();
        $student->forceFill(['ai_request_count' => 0])->save();

        $limit = User::DEMO_AI_REQUEST_LIMIT;

        // The first `limit` requests are allowed.
        for ($i = 0; $i < $limit; $i++) {
            $this->actingAs($student)
                ->postJson('/chat', ['message' => 'Hello there'])
                ->assertOk();
        }

        $this->assertSame($limit, $student->fresh()->ai_request_count);
        $this->assertTrue($student->fresh()->hasReachedAiRequestLimit());

        // The next one is refused with 429 and does not increment further.
        $this->actingAs($student)
            ->postJson('/chat', ['message' => 'One too many'])
            ->assertStatus(429)
            ->assertJson(['demo_limit_reached' => true]);

        $this->assertSame($limit, $student->fresh()->ai_request_count);
    }

    public function test_outside_demo_mode_ai_usage_is_not_capped(): void
    {
        config(['app.demo' => false]); // default (APP_MODE != demo)

        $user = new User(['email' => 'real.person@example.com']);
        $user->ai_request_count = 9999;

        $this->assertFalse($user->hasAiRequestQuota());
        $this->assertFalse($user->hasReachedAiRequestLimit());
        $this->assertSame(PHP_INT_MAX, $user->remainingAiRequests());
    }

    public function test_maintenance_switch_gates_the_app_and_live_true_restores_it(): void
    {
        // The middleware is exempt in the testing environment, so exercise the
        // toggle + state directly rather than through the HTTP gate.
        $middleware = new HandleMaintenanceMode;
        $ref = new \ReflectionMethod($middleware, 'isLive');

        Cache::forget('app.live_state');
        $this->assertTrue($ref->invoke($middleware), 'Defaults to live.');

        Cache::forever('app.live_state', false);
        $this->assertFalse($ref->invoke($middleware), 'live=false engages maintenance.');

        Cache::forever('app.live_state', true);
        $this->assertTrue($ref->invoke($middleware), 'live=true restores normal operation.');
    }

    public function test_live_toggle_route_exists(): void
    {
        $this->assertTrue(Route::has('live.toggle'));
    }
}

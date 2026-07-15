<?php

namespace App\Http\Middleware;

use App\Domain\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caps AI-feature usage while the app runs in demo mode (`APP_MODE=demo`). Each
 * account may then make at most {@see User::DEMO_AI_REQUEST_LIMIT} requests across
 * every AI surface — the student chat/agent, the faculty AI assistant and the AI
 * generators — after which further requests are refused so a public demo can never
 * exhaust the shared provider budget. Outside demo mode nothing is metered and
 * requests pass straight through.
 *
 * Applied to the request-consuming AI endpoints only (POST send/generate), never
 * to page loads or history reads, so browsing never spends quota.
 */
class EnforceDemoAiLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasAiRequestQuota()) {
            if ($user->hasReachedAiRequestLimit()) {
                return $this->limitReached($user);
            }

            // Charge the request up-front: a demo user gets a fixed number of
            // attempts regardless of whether the downstream provider succeeds.
            $user->recordAiRequest();
        }

        return $next($request);
    }

    private function limitReached(User $user): Response
    {
        $message = sprintf(
            'Demo AI limit reached — this demo account is capped at %d AI requests. '
            .'Thanks for trying it out!',
            User::DEMO_AI_REQUEST_LIMIT,
        );

        return response()->json([
            'message' => $message,
            'blocked' => true,
            'demo_limit_reached' => true,
            'limit' => User::DEMO_AI_REQUEST_LIMIT,
            'used' => (int) $user->ai_request_count,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}

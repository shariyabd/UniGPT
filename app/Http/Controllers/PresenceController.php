<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Presence heartbeat. Every authenticated page pings this on an interval while
 * the user is anywhere in the app; "active" is derived from how recently the
 * last ping landed ({@see User::isActive()}). This is the cap-safe alternative
 * to a persistent presence socket — a cheap periodic UPDATE, no open connection.
 */
class PresenceController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        // Direct UPDATE (no model events, no updated_at churn) — this fires
        // frequently and must stay cheap.
        User::whereKey($request->user()->id)->update(['last_seen_at' => now()]);

        return response()->json(['ok' => true]);
    }
}

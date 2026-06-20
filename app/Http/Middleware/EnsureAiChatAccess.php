<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a user from sending AI-chat messages when an admin has revoked their
 * AI-chat access (temporarily or permanently). Returns a 403 carrying the
 * admin's reason note so the chat UI can show why access was blocked.
 */
class EnsureAiChatAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAiChatBlocked()) {
            $message = $user->ai_chat_block_reason
                ?: 'Your access to the AI chat has been blocked. Please contact an administrator.';

            return response()->json([
                'message' => $message,
                'blocked' => true,
                'blocked_until' => $user->ai_chat_blocked_until?->toIso8601String(),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

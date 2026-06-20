<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\Services\AiUsageService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockAiChatRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin AI-usage monitor: per-user token usage, per-request queries, and
 * controls to block/restore a user's AI-chat access for cost control.
 */
class AiUsageController extends Controller
{
    public function __construct(private readonly AiUsageService $usage) {}

    public function index(): Response
    {
        return Inertia::render('Admin/AiUsage', [
            'summary' => $this->usage->summary(),
            'users' => $this->usage->perUser(),
            'requests' => $this->usage->recentRequests(),
        ]);
    }

    /**
     * Block a user from the AI chat. A null expiry is a permanent block.
     */
    public function block(BlockAiChatRequest $request, User $user): RedirectResponse
    {
        if ($user->email === User::PROTECTED_ADMIN_EMAIL) {
            return back()->with('error', 'The primary administrator cannot be blocked.');
        }

        $days = $request->integer('expires_in_days');
        $until = $days > 0 ? now()->addDays($days) : null;

        $user->blockAiChat(reason: $request->string('reason')->toString(), until: $until);

        return back()->with('success', "AI chat access blocked for {$user->name}.");
    }

    /**
     * Restore a user's AI-chat access.
     */
    public function unblock(User $user): RedirectResponse
    {
        $user->unblockAiChat();

        return back()->with('success', "AI chat access restored for {$user->name}.");
    }
}

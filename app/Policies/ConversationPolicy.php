<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Models\Conversation;

/**
 * Authorizes access to a direct conversation. The single rule: you may only
 * view or post to a conversation you participate in. Whether two users are
 * *allowed to start* a conversation is a separate eligibility check
 * ({@see User::canMessage()}), enforced at conversation-creation time.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }
}

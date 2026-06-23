<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/**
 * Broadcast channel authorization. This is the gate that stops a user
 * subscribing to a conversation they are not part of — it is separate from the
 * HTTP route middleware, and just as important. `{conversation}` is resolved by
 * implicit model binding, then we reuse the same participant check the HTTP
 * layer uses ({@see \App\Policies\ConversationPolicy}).
 */
Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation): bool {
    return $conversation->isParticipant($user);
});

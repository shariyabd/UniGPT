<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Models\ChatSession;

class ChatSessionPolicy
{
    public function view(User $user, ChatSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function delete(User $user, ChatSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}

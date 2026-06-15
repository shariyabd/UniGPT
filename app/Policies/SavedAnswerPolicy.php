<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Models\SavedAnswer;

class SavedAnswerPolicy
{
    public function update(User $user, SavedAnswer $answer): bool
    {
        return $answer->user_id === $user->id;
    }

    public function delete(User $user, SavedAnswer $answer): bool
    {
        return $answer->user_id === $user->id;
    }
}

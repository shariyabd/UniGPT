<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A direct (1:1) conversation between two users.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $last_message_at
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_message_id')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Conversations the given user participates in, most-recent first.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->whereKey($user->id))
            ->orderByDesc('last_message_at');
    }

    /**
     * Resolve the existing 1:1 conversation between two users, creating it (and
     * attaching both participants) on first contact. Idempotent — re-opening a
     * chat always reuses the same conversation.
     */
    public static function betweenOrCreate(User $first, User $second): self
    {
        $existing = self::query()
            ->whereHas('participants', fn (Builder $q) => $q->whereKey($first->id))
            ->whereHas('participants', fn (Builder $q) => $q->whereKey($second->id))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $conversation = self::create();
        $conversation->participants()->attach([$first->id, $second->id]);

        return $conversation;
    }

    /**
     * The participant who is not the given user (1:1 — there is exactly one).
     */
    public function otherParticipant(User $user): ?User
    {
        return $this->participants->firstWhere('id', '!=', $user->id);
    }

    /**
     * Whether the given user is one of this conversation's participants. Backs
     * both HTTP authorization and the broadcast channel auth check.
     */
    public function isParticipant(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }
}

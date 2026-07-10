<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A conversation thread: either a direct (1:1) student↔faculty chat or a
 * section-scoped group study room with N student members.
 *
 * @property int $id
 * @property string $type
 * @property string|null $title
 * @property int|null $section_id
 * @property int|null $created_by
 * @property Carbon|null $last_message_at
 */
class Conversation extends Model
{
    use HasFactory;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_GROUP = 'group';

    protected $fillable = [
        'type',
        'title',
        'section_id',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'section_id' => 'integer',
            'created_by' => 'integer',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    /**
     * Only direct (1:1) conversations — the original messenger surface.
     */
    public function scopeDirect(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DIRECT);
    }

    /**
     * Only group study rooms.
     */
    public function scopeGroup(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GROUP);
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
     * chat always reuses the same conversation. Strictly scoped to direct
     * conversations: a group room both users belong to must never satisfy it.
     */
    public static function betweenOrCreate(User $first, User $second): self
    {
        $existing = self::query()
            ->direct()
            ->whereHas('participants', fn (Builder $q) => $q->whereKey($first->id))
            ->whereHas('participants', fn (Builder $q) => $q->whereKey($second->id))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $conversation = self::create(['type' => self::TYPE_DIRECT]);
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

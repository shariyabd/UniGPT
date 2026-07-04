<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single text message within a {@see Conversation}.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $body
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
    ];

    /**
     * Cast the foreign keys to int so the JSON wire shape is deterministic.
     *
     * Some MySQL/PDO configurations (notably shared hosting) return non-primary
     * integer columns as strings. Without these casts, `sender_id`/`conversation_id`
     * ship as "2" on those hosts, breaking the client's strict `sender_id === userId`
     * alignment check and the `conversation_id === openId` de-dupe guard — so
     * messages render on the wrong side and realtime updates get silently dropped.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'sender_id' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

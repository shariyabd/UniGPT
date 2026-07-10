<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasFactory;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'confidence',
        'confidence_level',
        'sources',
        'follow_ups',
        'tool_activity',
        'model',
        'tokens',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'follow_ups' => 'array',
            'tool_activity' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(MessageCitation::class);
    }
}

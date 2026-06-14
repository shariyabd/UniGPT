<?php

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_message_id',
        'title',
        'question',
        'answer',
        'category',
        'tags',
        'sources',
        'confidence',
        'notes',
        'folder',
        'starred',
        'view_count',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'sources' => 'array',
            'starred' => 'boolean',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }
}

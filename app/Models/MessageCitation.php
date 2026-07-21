<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageCitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_message_id',
        'document_id',
        'document_chunk_id',
        'relevance',
    ];

    protected function casts(): array
    {
        return [
            'chat_message_id' => 'integer',
            'document_id' => 'integer',
            'document_chunk_id' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

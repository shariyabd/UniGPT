<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'page',
        'section',
        'token_count',
    ];

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(Embedding::class);
    }
}

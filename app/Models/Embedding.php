<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Embedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_chunk_id',
        'document_id',
        'model',
        'dimensions',
        'vector',
    ];

    protected function casts(): array
    {
        return [
            'document_chunk_id' => 'integer',
            'document_id' => 'integer',
            'vector' => 'array',
        ];
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(DocumentChunk::class, 'document_chunk_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

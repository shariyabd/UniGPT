<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'deck_id',
        'front',
        'back',
        'position',
        'ease_factor',
        'interval_days',
        'repetitions',
        'due_at',
        'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(FlashcardDeck::class, 'deck_id');
    }

    /**
     * Cards that are due for review now (never-reviewed cards are always due).
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('due_at')->orWhere('due_at', '<=', now()));
    }
}

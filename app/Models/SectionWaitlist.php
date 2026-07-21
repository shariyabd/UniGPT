<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student queued for a seat in a full section. FIFO by id; the head is
 * auto-promoted to a pending placement when a seat frees up.
 */
class SectionWaitlist extends Model
{
    protected $fillable = [
        'section_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'section_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

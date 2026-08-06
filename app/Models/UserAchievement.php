<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use App\Enums\Achievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single badge earned by a user. The definition (title, threshold, icon…)
 * lives in the {@see Achievement} enum; this row is the permanent award record.
 */
class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'achievement' => Achievement::class,
            'earned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

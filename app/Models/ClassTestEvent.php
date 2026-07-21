<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single proctoring/behaviour event recorded during a class-test attempt
 * (fullscreen exit, tab blur, clipboard attempt, focus loss, answer, idle span,
 * resize, click …). The typed replacement for the old bare violation counter —
 * this is the evidence trail faculty and admins review.
 *
 * @property int $id
 * @property int $attempt_id
 * @property int|null $question_id
 * @property string $type
 * @property string $severity
 * @property Carbon $occurred_at
 * @property int|null $duration_ms
 * @property array<string, mixed>|null $meta
 * @property string|null $ip_address
 */
class ClassTestEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'type',
        'severity',
        'occurred_at',
        'duration_ms',
        'meta',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'attempt_id' => 'integer',
            'question_id' => 'integer',
            'occurred_at' => 'datetime',
            'duration_ms' => 'integer',
            'meta' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ClassTestAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ClassTestQuestion::class, 'question_id');
    }
}

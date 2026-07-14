<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One snapshot-evidence frame captured from the webcam during a proctored
 * attempt — either at a flagged moment (violation, face loss, phone detection,
 * second face, identity verification) or as a randomised periodic sample.
 * Stored on a private disk; only faculty/admin may view it.
 *
 * @property int $id
 * @property int $attempt_id
 * @property string $trigger
 * @property int $sequence
 * @property string $disk
 * @property string $path
 * @property int $size_bytes
 */
class ClassTestSnapshot extends Model
{
    protected $fillable = [
        'attempt_id',
        'trigger',
        'sequence',
        'disk',
        'path',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ClassTestAttempt::class, 'attempt_id');
    }
}

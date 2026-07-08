<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One chunk of an exam media recording (webcam or screen). Recordings are
 * chunked so a mid-exam disconnect still preserves whatever was captured; a
 * faculty reviewer streams the chunks back in `sequence` order. Files live on a
 * private disk — only faculty/admin may read them.
 *
 * @property int $id
 * @property int $attempt_id
 * @property string $kind
 * @property int $sequence
 * @property string $disk
 * @property string $path
 * @property string|null $mime
 * @property int $size_bytes
 * @property int|null $duration_seconds
 */
class ClassTestRecording extends Model
{
    use HasFactory;

    public const KIND_WEBCAM = 'webcam';

    public const KIND_SCREEN = 'screen';

    protected $fillable = [
        'attempt_id',
        'kind',
        'sequence',
        'disk',
        'path',
        'mime',
        'size_bytes',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ClassTestAttempt::class, 'attempt_id');
    }

    /**
     * Delete the backing file when the row is removed (attempt cascade delete
     * only drops the row, not the blob — clean it up here).
     */
    protected static function booted(): void
    {
        static::deleting(function (ClassTestRecording $recording): void {
            Storage::disk($recording->disk)->delete($recording->path);
        });
    }
}

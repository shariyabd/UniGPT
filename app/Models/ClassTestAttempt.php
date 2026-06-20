<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $class_test_id
 * @property int $user_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property int $score
 * @property int $total_marks
 * @property int $violation_count
 */
class ClassTestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_test_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
        'score',
        'total_marks',
        'violation_count',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'integer',
            'total_marks' => 'integer',
            'violation_count' => 'integer',
        ];
    }

    public function classTest(): BelongsTo
    {
        return $this->belongsTo(ClassTest::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ClassTestAnswer::class, 'attempt_id');
    }

    public function isFinalised(): bool
    {
        return in_array($this->status, ['submitted', 'disqualified', 'expired'], true);
    }
}

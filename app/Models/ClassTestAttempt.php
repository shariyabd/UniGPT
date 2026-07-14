<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $class_test_id
 * @property int $user_id
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property int $score
 * @property int $total_marks
 * @property int $violation_count
 * @property array<string, mixed>|null $fingerprint
 * @property string|null $fingerprint_hash
 * @property string|null $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $risk_score
 * @property array<int, array<string, mixed>>|null $risk_factors
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
        'fingerprint',
        'fingerprint_hash',
        'session_id',
        'ip_address',
        'user_agent',
        'risk_score',
        'risk_factors',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'integer',
            'total_marks' => 'integer',
            'violation_count' => 'integer',
            'fingerprint' => 'array',
            'risk_score' => 'integer',
            'risk_factors' => 'array',
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

    public function events(): HasMany
    {
        return $this->hasMany(ClassTestEvent::class, 'attempt_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(ClassTestRecording::class, 'attempt_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ClassTestSnapshot::class, 'attempt_id');
    }

    public function isFinalised(): bool
    {
        return in_array($this->status, ['submitted', 'disqualified', 'expired'], true);
    }
}

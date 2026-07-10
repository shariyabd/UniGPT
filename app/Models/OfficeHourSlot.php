<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-capacity office-hours slot published by a faculty member. Open
 * while `booked_by` is null; a student booking claims it atomically.
 */
class OfficeHourSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'starts_at',
        'ends_at',
        'location',
        'note',
        'booked_by',
        'booked_at',
    ];

    protected function casts(): array
    {
        return [
            'faculty_id' => 'integer',
            'booked_by' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'booked_at' => 'datetime',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function isBooked(): bool
    {
        return $this->booked_by !== null;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('booked_by');
    }
}

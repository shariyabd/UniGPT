<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use BelongsToSection;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'type',
        'total_points',
        'due_at',
        'rubric',
        'peer_review_enabled',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rubric' => 'array',
            'peer_review_enabled' => 'boolean',
            'due_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

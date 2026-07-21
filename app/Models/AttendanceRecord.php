<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use BelongsToSection;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'user_id',
        'date',
        'status',
        'notes',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'section_id' => 'integer',
            'user_id' => 'integer',
            'marked_by' => 'integer',
            'date' => 'date',
            'status' => AttendanceStatus::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}

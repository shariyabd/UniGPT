<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's anonymous feedback for a section. ⚠️ user_id exists solely to
 * enforce one-response-per-student and let the student edit while the window
 * is open — never join it into anything faculty or admin can see.
 */
class CourseFeedback extends Model
{
    protected $table = 'course_feedback';

    protected $fillable = [
        'section_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'section_id' => 'integer',
            'user_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}

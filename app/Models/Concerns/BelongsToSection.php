<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behaviour for course-scoped academic records (materials, assignments,
 * attendance, exams) that now belong to a Section. When such a record is created
 * with only a course_id, its section_id is auto-filled from the course's
 * section — so existing write paths need no changes.
 */
trait BelongsToSection
{
    public static function bootBelongsToSection(): void
    {
        static::creating(function (Model $model): void {
            if ($model->section_id === null && $model->course_id !== null) {
                $model->section_id = Section::query()
                    ->where('course_id', $model->course_id)
                    ->orderBy('id')
                    ->value('id');
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}

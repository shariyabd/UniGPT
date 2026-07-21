<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    use BelongsToSection;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'document_id',
        'file_path',
        'original_filename',
        'file_size',
        'title',
        'description',
        'type',
        'week',
        'is_published',
        'downloads',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'section_id' => 'integer',
            'document_id' => 'integer',
            'uploaded_by' => 'integer',
            'is_published' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Whether a downloadable file is attached (uploaded file or linked document).
     */
    public function hasFile(): bool
    {
        return $this->file_path !== null || $this->document_id !== null;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

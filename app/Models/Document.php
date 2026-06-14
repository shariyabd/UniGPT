<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'category',
        'file_type',
        'file_path',
        'original_filename',
        'file_size',
        'pages',
        'version',
        'status',
        'visibility',
        'tags',
        'downloads',
        'views',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'status' => DocumentStatus::class,
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(Embedding::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::APPROVED->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DocumentStatus::PENDING->value,
            DocumentStatus::PROCESSING->value,
            DocumentStatus::PROCESSED->value,
        ]);
    }

    /**
     * Limit documents to those a given user is allowed to see.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $allowed = ['public'];
        if ($user->isStudent()) {
            $allowed[] = 'students';
        }
        if ($user->isFaculty()) {
            $allowed[] = 'students';
            $allowed[] = 'faculty';
        }

        return $query->whereIn('visibility', array_unique($allowed));
    }
}

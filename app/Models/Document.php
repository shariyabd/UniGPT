<?php

namespace App\Models;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category',
        'file_type',
        'file_path',
        'original_filename',
        'file_hash',
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
            'uploaded_by' => 'integer',
            'approved_by' => 'integer',
            'tags' => 'array',
            'visibility' => 'array',
            'status' => DocumentStatus::class,
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Departments this document targets. An empty set means it is visible to
     * every department ("All Departments").
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'document_department');
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

    /**
     * Users who have bookmarked this document.
     */
    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_bookmarks')->withTimestamps();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::APPROVED->value);
    }

    /**
     * Documents targeting a given department (or every department when the
     * document has no department pivot rows).
     */
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where(function (Builder $q) use ($departmentId) {
            $q->whereDoesntHave('departments')
                ->orWhereHas('departments', fn (Builder $d) => $d->whereKey($departmentId));
        });
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
     * Limit documents to those a given user is allowed to see. Visibility is a
     * multi-audience JSON array, so a document is visible when any audience the
     * user belongs to is present in that array (admins see everything).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $allowed = [];
        if ($user->isStudent()) {
            $allowed[] = 'students';
        }
        if ($user->isFaculty()) {
            $allowed[] = 'students';
            $allowed[] = 'faculty';
        }

        return $query->where(function (Builder $audiences) use ($allowed) {
            foreach (array_unique($allowed) as $audience) {
                $audiences->orWhereJsonContains('visibility', $audience);
            }
        });
    }
}

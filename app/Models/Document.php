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

    /**
     * Global scope name that restricts every ordinary query to library
     * documents. RAG ingestion/retrieval code opts out explicitly via
     * {@see allSources()} so personal shadow documents (notes, course
     * materials) never leak into the admin library, approvals, analytics,
     * dashboards or route-model binding.
     */
    public const LIBRARY_SCOPE = 'librarySource';

    public const SOURCE_LIBRARY = 'library';

    public const SOURCE_NOTE = 'note';

    public const SOURCE_MATERIAL = 'material';

    protected $fillable = [
        'source_type',
        'source_id',
        'owner_id',
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

    protected static function booted(): void
    {
        static::addGlobalScope(self::LIBRARY_SCOPE, function (Builder $query) {
            $query->where($query->qualifyColumn('source_type'), self::SOURCE_LIBRARY);
        });
    }

    /**
     * Query across every source (library + personal shadows). The entry point
     * for RAG code that must see shadow documents.
     */
    public static function allSources(): Builder
    {
        return static::query()->withoutGlobalScope(self::LIBRARY_SCOPE);
    }

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'owner_id' => 'integer',
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

    /**
     * Owner of a personal shadow document (the note's author).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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

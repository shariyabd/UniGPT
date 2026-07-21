<?php

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    use HasFactory;

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_CHANGES_REQUESTED = 'changes_requested';

    public const ACTION_COMMENTED = 'commented';

    protected $fillable = [
        'document_id',
        'reviewer_id',
        'action',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'reviewer_id' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}

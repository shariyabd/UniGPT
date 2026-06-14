<?php

namespace App\Services;

use App\Domain\User\Models\User;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Lightweight audit/activity recorder reused by dashboards and analytics.
 */
class ActivityLogger
{
    /**
     * Record an activity entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $metadata = [],
        ?User $user = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user?->id ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}

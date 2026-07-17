<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single tracked page view. Append-only audit trail (no updated_at) that powers
 * the Admin → User Activity report: who visited, from where, on what device, where.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $path
 * @property string|null $route_name
 * @property string|null $label
 * @property string|null $referrer
 * @property string|null $ip_address
 * @property string|null $device_type
 * @property string|null $platform
 * @property string|null $browser
 * @property string|null $country
 * @property string|null $city
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class Visit extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'session_id',
        'path',
        'route_name',
        'label',
        'referrer',
        'ip_address',
        'device_type',
        'platform',
        'browser',
        'country',
        'city',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForRole(Builder $query, string $roleSlug): Builder
    {
        return $query->whereHas('user.roles', fn (Builder $q) => $q->where('slug', $roleSlug));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Tracking;

use App\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read side of visit tracking: builds the filtered, paginated feed and the summary
 * stats consumed by the Admin → User Activity page.
 */
class VisitReportService
{
    /**
     * @param  array{search?: ?string, role?: ?string, device?: ?string, country?: ?string, from?: ?string, to?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['user:id,name,email'])
            ->latest('created_at')
            ->paginate($perPage)
            ->through(fn (Visit $visit) => $this->present($visit));
    }

    /**
     * @param  array{search?: ?string, role?: ?string, device?: ?string, country?: ?string, from?: ?string, to?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function stats(array $filters): array
    {
        $base = $this->query($filters);

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'uniqueVisitors' => (clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'guestVisits' => (clone $base)->whereNull('user_id')->count(),
            'devices' => (clone $base)->whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as total')
                ->groupBy('device_type')
                ->pluck('total', 'device_type'),
            'topCountries' => (clone $base)->whereNotNull('country')
                ->selectRaw('country, COUNT(*) as total')
                ->groupBy('country')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'country'),
            'topPages' => (clone $base)->whereNotNull('label')
                ->selectRaw('label, COUNT(*) as total')
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'label'),
        ];
    }

    /**
     * Distinct filter option lists for the UI dropdowns.
     *
     * @return array{countries: array<int, string>, devices: array<int, string>}
     */
    public function filterOptions(): array
    {
        return [
            'countries' => Visit::query()->whereNotNull('country')
                ->distinct()->orderBy('country')->pluck('country')->all(),
            'devices' => Visit::query()->whereNotNull('device_type')
                ->distinct()->orderBy('device_type')->pluck('device_type')->all(),
        ];
    }

    /**
     * @param  array{search?: ?string, role?: ?string, device?: ?string, country?: ?string, from?: ?string, to?: ?string}  $filters
     */
    private function query(array $filters): Builder
    {
        return Visit::query()
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->whereHas(
                'user',
                fn (Builder $u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($filters['role'] ?? null, fn (Builder $q, string $role) => $q->forRole($role))
            ->when($filters['device'] ?? null, fn (Builder $q, string $device) => $q->where('device_type', $device))
            ->when($filters['country'] ?? null, fn (Builder $q, string $country) => $q->where('country', $country))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Visit $visit): array
    {
        $location = array_filter([$visit->city, $visit->country]);

        return [
            'id' => $visit->id,
            'user' => $visit->user
                ? ['name' => $visit->user->name, 'email' => $visit->user->email]
                : null,
            'role' => $visit->user?->getPrimaryRole()?->slug,
            'page' => $visit->label ?? $visit->path,
            'path' => $visit->path,
            'referrer' => $visit->referrer,
            'device' => $visit->device_type,
            'platform' => $visit->platform,
            'browser' => $visit->browser,
            'location' => $location === [] ? null : implode(', ', $location),
            'ip' => $visit->ip_address,
            'visitedAt' => $visit->created_at?->diffForHumans(),
            'visitedAtExact' => $visit->created_at?->toDayDateTimeString(),
        ];
    }
}

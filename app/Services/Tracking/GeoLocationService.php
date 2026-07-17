<?php

declare(strict_types=1);

namespace App\Services\Tracking;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves an IP address to a coarse { country, city } using the free ip-api.com
 * endpoint. Results are cached per-IP (30 days) so repeat visitors never trigger a
 * second network call, and private/loopback ranges short-circuit to "Local" without
 * hitting the network. Any failure degrades gracefully to nulls — geolocation must
 * never break a page render.
 */
class GeoLocationService
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 30; // 30 days

    private const ENDPOINT = 'http://ip-api.com/json/';

    /**
     * @return array{country: string|null, city: string|null}
     */
    public function locate(?string $ip): array
    {
        $empty = ['country' => null, 'city' => null];

        if ($ip === null || $ip === '') {
            return $empty;
        }

        if ($this->isPrivate($ip)) {
            return ['country' => 'Local', 'city' => null];
        }

        return Cache::remember("geoip:{$ip}", self::CACHE_TTL_SECONDS, function () use ($ip, $empty) {
            try {
                $response = Http::timeout(3)->get(self::ENDPOINT.$ip, [
                    'fields' => 'status,country,city',
                ]);

                if ($response->ok() && $response->json('status') === 'success') {
                    return [
                        'country' => $response->json('country') ?: null,
                        'city' => $response->json('city') ?: null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug('GeoLocation lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
            }

            return $empty;
        });
    }

    private function isPrivate(string $ip): bool
    {
        // Loopback, private, and reserved ranges never resolve to a public location.
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}

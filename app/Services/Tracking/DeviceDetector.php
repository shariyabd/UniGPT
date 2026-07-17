<?php

declare(strict_types=1);

namespace App\Services\Tracking;

/**
 * Dependency-free User-Agent parser. Extracts a coarse device type, OS platform,
 * and browser family — enough for the admin activity report without pulling in a
 * heavyweight UA library. Deliberately conservative: unknown UAs degrade to null.
 */
class DeviceDetector
{
    /**
     * @return array{device_type: string|null, platform: string|null, browser: string|null}
     */
    public function parse(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return ['device_type' => null, 'platform' => null, 'browser' => null];
        }

        return [
            'device_type' => $this->deviceType($ua),
            'platform' => $this->platform($ua),
            'browser' => $this->browser($ua),
        ];
    }

    private function deviceType(string $ua): string
    {
        if (preg_match('/bot|crawler|spider|crawling|slurp|bingpreview|facebookexternalhit/i', $ua)) {
            return 'bot';
        }

        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|Opera Mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function platform(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/Windows Phone/i', $ua) => 'Windows Phone',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Mac OS X|Macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => null,
        };
    }

    private function browser(string $ua): ?string
    {
        // Order matters: Edge/Opera/Chrome all embed "Chrome" or "Safari" tokens.
        return match (true) {
            (bool) preg_match('/Edg[eA]?\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox\/|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome\/|CriOS/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari\//i', $ua) => 'Safari',
            (bool) preg_match('/MSIE|Trident/i', $ua) => 'Internet Explorer',
            default => null,
        };
    }
}

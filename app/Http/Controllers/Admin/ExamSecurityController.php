<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\ExamSecurityService;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Global gate over the exam-security layer registry. An admin decides which
 * layers faculty may use at all (`available`) and which are pre-ticked on a new
 * test (`default`). Overrides are stored in the `exam_security` setting and
 * overlaid on the config defaults by {@see ExamSecurityService::registry()}.
 */
class ExamSecurityController extends Controller
{
    private const SETTING_KEY = 'exam_security';

    public function __construct(private readonly ExamSecurityService $security) {}

    public function index(): Response
    {
        $layers = collect($this->security->registry())
            ->map(fn (array $layer, string $key) => [
                'key' => $key,
                'label' => $layer['label'] ?? $key,
                'description' => $layer['description'] ?? '',
                'category' => $layer['category'] ?? 'lockdown',
                'media' => (bool) ($layer['media'] ?? false),
                'available' => (bool) ($layer['available'] ?? false),
                'default' => (bool) ($layer['default'] ?? false),
            ])
            ->values();

        return Inertia::render('Admin/ExamSecurity', [
            'layers' => $layers,
            'maxWarningsDefault' => (int) config('exam_security.max_warnings_default'),
            // Real tuning values for the layer guide so the docs never drift
            // from the behaviour that ships.
            'guideConfig' => [
                'softWarningSeconds' => (int) config('exam_security.liveness.soft_warning_seconds'),
                'graceSeconds' => (int) config('exam_security.liveness.grace_seconds'),
                'freeWarnings' => (int) config('exam_security.liveness.free_warnings'),
                'noBlinkSpoofSeconds' => (int) config('exam_security.liveness.no_blink_spoof_seconds'),
                'gateBypassSeconds' => (int) config('exam_security.liveness.gate_bypass_seconds'),
                'recordingChunkSeconds' => (int) config('exam_security.recording.chunk_seconds'),
                'recordingBitrateKbps' => (int) round(((int) config('exam_security.recording.video_bits_per_second')) / 1000),
                'retentionDays' => (int) config('exam_security.evidence_retention_days'),
                'snapshotBurstCount' => (int) config('exam_security.snapshots.burst_count'),
                'snapshotPeriodicMin' => (int) config('exam_security.snapshots.periodic_min_seconds'),
                'snapshotPeriodicMax' => (int) config('exam_security.snapshots.periodic_max_seconds'),
                'snapshotMaxPerAttempt' => (int) config('exam_security.snapshots.max_per_attempt'),
                'phoneConsecutiveHits' => (int) config('exam_security.phone.consecutive_hits'),
                'phoneCooldownSeconds' => (int) config('exam_security.phone.cooldown_seconds'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layers' => ['array'],
            'layers.*.available' => ['boolean'],
            'layers.*.default' => ['boolean'],
        ]);

        // Only persist overrides for layer keys the config actually declares.
        $known = array_keys((array) config('exam_security.layers', []));
        $overrides = [];
        foreach ((array) ($validated['layers'] ?? []) as $key => $values) {
            if (! in_array($key, $known, true)) {
                continue;
            }
            $overrides[$key] = [
                'available' => (bool) ($values['available'] ?? false),
                'default' => (bool) ($values['default'] ?? false),
            ];
        }

        Setting::put(self::SETTING_KEY, ['layers' => $overrides]);

        return back()->with('success', 'Exam security settings saved.');
    }
}

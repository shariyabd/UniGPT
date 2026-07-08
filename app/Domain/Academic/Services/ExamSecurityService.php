<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\ClassTestEvent;
use App\Models\ClassTestRecording;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The spine of the exam-security feature. Owns the layer registry, resolves the
 * *effective* set of layers for a test (config defaults → admin global gate →
 * per-test faculty selection), and persists the evidence trail (behaviour
 * events, fingerprints, media recordings) plus the derived risk score.
 *
 * Every layer is independent and configurable — nothing here hard-codes a layer
 * key that isn't declared in config/exam_security.php.
 */
class ExamSecurityService
{
    /** Setting key holding the admin's global overrides of the config registry. */
    private const SETTING_KEY = 'exam_security';

    // ---------------------------------------------------------------------
    // Registry (config + admin overrides)
    // ---------------------------------------------------------------------

    /**
     * The full layer registry, config defaults overlaid with the admin's global
     * overrides (which may flip a layer's `available` gate or its `default`).
     *
     * @return array<string, array<string, mixed>>
     */
    public function registry(): array
    {
        $base = (array) config('exam_security.layers', []);
        $overrides = (array) (Setting::get(self::SETTING_KEY, [])['layers'] ?? []);

        foreach ($base as $key => &$layer) {
            $override = (array) ($overrides[$key] ?? []);
            if (array_key_exists('available', $override)) {
                $layer['available'] = (bool) $override['available'];
            }
            if (array_key_exists('default', $override)) {
                $layer['default'] = (bool) $override['default'];
            }
        }

        return $base;
    }

    /**
     * Layers an admin has left globally available, shaped for the faculty
     * authoring form (camelCase, grouped-friendly).
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableLayers(): array
    {
        $layers = [];
        foreach ($this->registry() as $key => $layer) {
            if (! ($layer['available'] ?? false)) {
                continue;
            }
            $layers[] = [
                'key' => $key,
                'label' => $layer['label'] ?? $key,
                'description' => $layer['description'] ?? '',
                'category' => $layer['category'] ?? 'lockdown',
                'default' => (bool) ($layer['default'] ?? false),
                'media' => (bool) ($layer['media'] ?? false),
                'privacyNotice' => (bool) ($layer['privacy_notice'] ?? false),
            ];
        }

        return $layers;
    }

    /**
     * The pre-ticked checkbox state for a *new* test (available layers only).
     *
     * @return array<string, bool>
     */
    public function defaultSelection(): array
    {
        $selection = [];
        foreach ($this->registry() as $key => $layer) {
            if ($layer['available'] ?? false) {
                $selection[$key] = (bool) ($layer['default'] ?? false);
            }
        }

        return $this->applyDependencies($selection);
    }

    /**
     * Normalise a faculty-submitted selection: keep only globally-available
     * layers, cast to bool, and enforce inter-layer dependencies.
     *
     * @param  array<string, mixed>|null  $input
     * @return array<string, bool>
     */
    public function sanitizeSelection(?array $input): array
    {
        $input ??= [];
        $selection = [];
        foreach ($this->registry() as $key => $layer) {
            if ($layer['available'] ?? false) {
                $selection[$key] = (bool) ($input[$key] ?? false);
            }
        }

        return $this->applyDependencies($selection);
    }

    /**
     * The effective on/off state of every available layer for a saved test.
     * A test with no stored selection falls back to the config defaults (so
     * pre-feature tests keep their historical behaviour, e.g. shuffling).
     *
     * @return array<string, bool>
     */
    public function selectionFor(ClassTest $test): array
    {
        $saved = $test->security_config;
        $selection = [];

        foreach ($this->registry() as $key => $layer) {
            if (! ($layer['available'] ?? false)) {
                continue;
            }
            $selection[$key] = $saved !== null
                ? (bool) ($saved[$key] ?? false)
                : (bool) ($layer['default'] ?? false);
        }

        return $this->applyDependencies($selection);
    }

    public function isEnabled(ClassTest $test, string $key): bool
    {
        return $this->selectionFor($test)[$key] ?? false;
    }

    /**
     * The runtime security payload handed to the student's proctored screen:
     * which layers are live, the warning threshold, recording tuning and (when
     * an attempt is given and the watermark layer is on) the identity overlay.
     *
     * @return array<string, mixed>
     */
    public function clientConfig(ClassTest $test, ?ClassTestAttempt $attempt = null): array
    {
        $selection = $this->selectionFor($test);

        $watermark = null;
        if ($selection['watermark'] ?? false) {
            $student = $attempt?->student;
            $watermark = [
                'name' => $student?->name,
                'studentId' => $student?->student_id,
                'sessionId' => $attempt?->session_id,
            ];
        }

        return [
            'layers' => $selection,
            'maxWarnings' => $test->max_warnings,
            'integrityNotice' => ($selection['integrity_notice'] ?? false)
                ? (string) config('exam_security.integrity_notice_text')
                : null,
            'watermark' => $watermark,
            'recording' => [
                'webcam' => (bool) ($selection['webcam'] ?? false),
                'screen' => (bool) ($selection['screen_record'] ?? false),
                'chunkSeconds' => (int) config('exam_security.recording.chunk_seconds'),
                'mime' => (string) config('exam_security.recording.mime'),
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Evidence capture during an attempt
    // ---------------------------------------------------------------------

    /**
     * Persist the browser fingerprint (raw components + stable hash) and request
     * identity at the start of an attempt.
     *
     * @param  array<string, mixed>  $components
     */
    public function captureFingerprint(ClassTestAttempt $attempt, array $components): void
    {
        $attempt->forceFill([
            'fingerprint' => $components,
            'fingerprint_hash' => hash('sha256', json_encode($components)),
            'session_id' => $attempt->session_id ?: (string) Str::uuid(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
        ])->save();
    }

    /**
     * Bulk-record behaviour/violation events. Violation-severity events also bump
     * the attempt's warning counter; returns the running count and whether the
     * configured threshold has now been crossed (caller disqualifies).
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array{violationCount: int, disqualified: bool, stored: int}
     */
    public function recordEvents(ClassTestAttempt $attempt, array $events): array
    {
        // Resolve the test's real question ids once so a stray/foreign id can't
        // break the batch insert (FK) — unknown ids are stored as null context.
        $validQuestionIds = $attempt->classTest
            ? $attempt->classTest->questions()->pluck('id')->all()
            : [];
        $validQuestionIds = array_flip($validQuestionIds);

        $now = now()->toDateTimeString();
        $ip = request()->ip();
        $rows = [];
        $violations = 0;

        foreach ($events as $event) {
            $type = trim((string) ($event['type'] ?? ''));
            if ($type === '') {
                continue;
            }

            $severity = in_array($event['severity'] ?? 'info', ['info', 'warning', 'violation'], true)
                ? (string) $event['severity']
                : 'info';
            if ($severity === 'violation') {
                $violations++;
            }

            $questionId = isset($event['questionId']) ? (int) $event['questionId'] : null;
            if ($questionId !== null && ! isset($validQuestionIds[$questionId])) {
                $questionId = null;
            }

            $rows[] = [
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'type' => Str::limit($type, 60, ''),
                'severity' => $severity,
                'occurred_at' => $this->clientTime($event['occurredAt'] ?? null),
                'duration_ms' => isset($event['durationMs']) ? max(0, (int) $event['durationMs']) : null,
                'meta' => isset($event['meta']) && $event['meta'] !== [] ? json_encode($event['meta']) : null,
                'ip_address' => $ip,
                'created_at' => $now,
            ];
        }

        if ($rows !== []) {
            ClassTestEvent::insert($rows);
        }

        $disqualified = false;
        if ($violations > 0) {
            $attempt->increment('violation_count', $violations);
            $max = $attempt->classTest?->max_warnings ?? (int) config('exam_security.max_warnings_default');
            $disqualified = $attempt->violation_count > $max;
        }

        return [
            'violationCount' => $attempt->violation_count,
            'disqualified' => $disqualified,
            'stored' => count($rows),
        ];
    }

    /**
     * Store one chunk of a media recording on the private disk and index it.
     * Chunked so a mid-exam disconnect still preserves earlier footage.
     */
    public function storeRecordingChunk(
        ClassTestAttempt $attempt,
        string $kind,
        int $sequence,
        UploadedFile $file,
    ): ClassTestRecording {
        $disk = (string) config('exam_security.recording.disk');
        $directory = trim((string) config('exam_security.recording.directory'), '/')
            ."/{$attempt->id}/{$kind}";
        $filename = sprintf('%06d.webm', $sequence);

        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

        return ClassTestRecording::updateOrCreate(
            ['attempt_id' => $attempt->id, 'kind' => $kind, 'sequence' => $sequence],
            [
                'disk' => $disk,
                'path' => $path,
                'mime' => $file->getClientMimeType() ?: (string) config('exam_security.recording.mime'),
                'size_bytes' => $file->getSize() ?: 0,
                'duration_seconds' => (int) config('exam_security.recording.chunk_seconds'),
            ],
        );
    }

    // ---------------------------------------------------------------------
    // Risk analysis
    // ---------------------------------------------------------------------

    /**
     * Derive a 0–100 suspicion score from the attempt's logged behaviour and
     * persist it (with the contributing factors) on the attempt. Best-effort —
     * only meaningful when the `behavior_log`/`risk_analysis` layers were on.
     *
     * @return array{score: int, factors: array<int, array<string, mixed>>}
     */
    public function computeRisk(ClassTestAttempt $attempt): array
    {
        $weights = (array) config('exam_security.risk.weights', []);
        $fastSeconds = (int) config('exam_security.risk.fast_answer_seconds', 3);
        $idleSeconds = (int) config('exam_security.risk.long_idle_seconds', 60);

        $events = $attempt->events()->get();
        $factors = [];
        $score = 0;

        $add = function (string $key, string $label, string $detail, int $multiplier = 1) use (&$score, &$factors, $weights): void {
            $weight = (int) ($weights[$key] ?? 0) * $multiplier;
            if ($weight <= 0) {
                return;
            }
            $score += $weight;
            $factors[] = ['key' => $key, 'label' => $label, 'detail' => $detail, 'weight' => $weight];
        };

        // 1. Proctoring violations (capped contribution).
        $violations = $attempt->violation_count;
        if ($violations > 0) {
            $add('per_violation', 'Proctoring violations', "{$violations} recorded", min($violations, 3));
        }

        // 2. Implausibly fast answers.
        $answerDurations = $events->where('type', 'answer')
            ->map(fn (ClassTestEvent $e) => $e->duration_ms)
            ->filter(fn (?int $ms) => $ms !== null && $ms > 0)
            ->values();
        if ($answerDurations->isNotEmpty()) {
            $fast = $answerDurations->filter(fn (int $ms) => $ms < $fastSeconds * 1000)->count();
            if ($fast > 0 && $fast >= $answerDurations->count() / 2) {
                $add('fast_answer', 'Very fast answers', "{$fast} answered under {$fastSeconds}s");
            }

            // 3. Near-uniform timing across questions (robotic pacing).
            if ($answerDurations->count() >= 3) {
                $mean = $answerDurations->avg();
                $variance = $answerDurations->reduce(fn (float $carry, int $ms) => $carry + (($ms - $mean) ** 2), 0.0)
                    / $answerDurations->count();
                $stdDev = sqrt($variance);
                if ($mean > 0 && ($stdDev / $mean) < 0.1) {
                    $add('uniform_timing', 'Uniform answer timing', 'Near-identical time per question');
                }
            }
        }

        // 4. Long idle stretches.
        $longIdle = $events->where('type', 'idle')
            ->filter(fn (ClassTestEvent $e) => ($e->duration_ms ?? 0) >= $idleSeconds * 1000)
            ->count();
        if ($longIdle > 0) {
            $add('long_idle', 'Long idle periods', "{$longIdle} idle span(s) over {$idleSeconds}s");
        }

        // 5. Frequent focus loss beyond the tolerated warnings.
        $focusLoss = $events->whereIn('type', ['tab_blur', 'focus_loss', 'fullscreen_exit', 'visibility_hidden'])->count();
        $maxWarnings = $attempt->classTest?->max_warnings ?? 0;
        if ($focusLoss > $maxWarnings && $focusLoss > 0) {
            $add('frequent_focus_loss', 'Frequent focus loss', "{$focusLoss} focus-loss events");
        }

        // 6. No pointer activity despite an active session.
        $hasPointer = $events->whereIn('type', ['mouse_move', 'click'])->isNotEmpty();
        if (! $hasPointer && $events->isNotEmpty()) {
            $add('no_mouse_movement', 'No mouse movement', 'No pointer activity logged');
        }

        $score = max(0, min(100, $score));

        $attempt->forceFill(['risk_score' => $score, 'risk_factors' => $factors])->save();

        return ['score' => $score, 'factors' => $factors];
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * Enforce inter-layer dependencies (e.g. "disable going back" is meaningless
     * without "one question at a time").
     *
     * @param  array<string, bool>  $selection
     * @return array<string, bool>
     */
    private function applyDependencies(array $selection): array
    {
        if (! empty($selection['lock_back']) && empty($selection['sequential'])) {
            $selection['lock_back'] = false;
        }

        return $selection;
    }

    /**
     * Parse a client-reported event time (ms epoch or ISO string) into a
     * DB-ready datetime, defaulting to the server clock when absent/unparseable.
     */
    private function clientTime(mixed $value): string
    {
        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value)->toDateTimeString();
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->toDateTimeString();
            } catch (\Throwable) {
                // fall through to now()
            }
        }

        return now()->toDateTimeString();
    }
}

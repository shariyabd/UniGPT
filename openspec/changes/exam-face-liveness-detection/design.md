## Context

The exam proctoring stack is a config-driven layer registry:

- `config/exam_security.php` declares layers (label/description/category/default/available/media/privacy_notice) plus tuning blocks (`recording`, `risk`).
- `ExamSecurityService` merges config with admin overrides (`Setting::put('exam_security', ...)`), sanitizes per-test selections (`class_tests.security_config` JSON), and builds the runtime `clientConfig(test, attempt)` payload consumed by `Student/ClassTests/Take.vue`.
- Frontend: `Take.vue` has a pre-start consent gate (`begin()` → `useExamRecorder.requestPermissions()` → `started=true` → questions render). Events flow through `useExamEvents` (`log`/`warn`/`report`) to `POST /class-tests/{id}/events` → `ExamSecurityService::recordEvents()`, which bumps `class_test_attempts.violation_count` for `violation`-severity events and disqualifies past `max_warnings`. Faculty sees everything in `Faculty/ClassTests/Attempt.vue`.

Constraint from the current code: `useExamRecorder` keeps its `MediaStream`s private — the liveness detector must share the webcam stream, so the composable needs to expose it (a second `getUserMedia` would prompt twice on some browsers and waste the camera).

## Goals / Non-Goals

**Goals:**
- `face_liveness` as a first-class registry layer (admin-gated, per-test, consent-listed) requiring the `webcam` layer.
- Client-side MediaPipe Face Landmarker detection: verification gate (face + blink) before questions render; continuous monitoring until the attempt ends.
- Two-stage no-face response: 3s → banner + question blur (no penalty); 8s → full-screen blocking overlay; warnings 1–2 are local + `warning` events; incident 3+ additionally reports a `violation` event into the existing disqualification pipeline.
- Liveness events visible in the faculty attempt timeline; face loss feeds `computeRisk()`.
- Model + wasm assets served from `public/` — no CDN dependency at exam time.

**Non-Goals:**
- No identity verification (matching the face to the enrolled student's photo).
- No gaze tracking / head-pose analytics, no multiple-face detection (future layers).
- No server-side video analysis; recording upload stays unchanged.
- No changes to how other layers behave.

## Decisions

### 1. Blink detection via blendshapes + EAR fallback (eye-focused)
Use `@mediapipe/tasks-vision` `FaceLandmarker` with `outputFaceBlendshapes: true`, running `detectForVideo()` on a `requestAnimationFrame`-ish loop (throttled to ~8–10 fps via elapsed-time check — full fps wastes CPU during a 1–3h exam). A blink completes when either signal finishes a close/re-open hysteresis cycle:

- **Blendshapes**: `eyeBlinkLeft`/`eyeBlinkRight` ≥ 0.5 close, < 0.25 re-open.
- **EAR fallback**: eye-aspect-ratio from the raw eye landmarks (canonical mesh indices), < 0.2 close, > 0.25 re-open — masks muffle blendshape scores but the eye geometry still moves.

Detection/presence confidences are lowered to 0.3 (`minFaceDetectionConfidence`/`minFacePresenceConfidence`) so partially covered faces (surgical masks, niqab) still register. "Live face" for the verification gate = face landmarks present + ≥1 blink observed. This defeats printed photos (photos don't blink).

*Revision (user feedback 2026-07-14):* originally blendshapes-only; EAR fallback + low confidences added because students wearing face coverings were the priority concern — the blink signal is deliberately eye-only.

### 2. Self-hosted model assets
Add `@mediapipe/tasks-vision` to npm deps. Copy the wasm bundle (`node_modules/@mediapipe/tasks-vision/wasm/*`) and the `face_landmarker.task` model (~3.7 MB, downloaded once at build/setup time) into `public/vendor/mediapipe/`. `FilesetResolver.forVisionTasks('/vendor/mediapipe/wasm')` + model path in the layer's client config. Rationale: an exam must not depend on a third-party CDN, and CSP/offline-lab environments are common.

*Alternative considered:* Google CDN — simpler but a runtime external dependency during exams; rejected.

### 3. New composable `useFaceLiveness.js`, detector state machine
`resources/js/composables/useFaceLiveness.js` encapsulates everything MediaPipe:

```
useFaceLiveness({ stream, config, onEvent })
  → { state, verified, blocked, warningsUsed, secondsWithoutFace, start, verify, stopDetection }
```

- Internally attaches the shared webcam `MediaStream` to a hidden `<video>` element.
- States: `loading` (model init) → `verifying` (gate) → `monitoring` ⇄ `blocked` → `stopped`.
- A no-face timer counts consecutive seconds without a detected face, two-stage: at `soft_warning_seconds` (3) a non-blocking banner asks the student back (no penalty); at `grace_seconds` (8) it transitions to `blocked` and emits an incident. Face re-detected → back to `monitoring`.
- Incident escalation lives here: incidents 1–2 emit `{ type: 'face_lost', severity: 'warning' }`; 3+ also emit `{ type: 'face_liveness_violation', severity: 'violation' }`. `Take.vue` maps these onto `events.warn()` / `events.report()` (reusing the existing disqualify handling in `handleViolation`-style flow).
- **No-blink spoof window**: a face continuously visible for `no_blink_spoof_seconds` (90) without one blink emits `no_blink_suspicion` (warning severity — review signal, no penalty) and re-arms.
- **Gate bypass**: after `gate_bypass_seconds` (30) of failed verification the gate offers "Continue without face verification" → `face_verification_bypassed` (warning) is logged, the exam starts, monitoring continues. Without this, a student whose face the detector cannot register (niqab) would be locked out of a graded exam; the webcam recording remains the ground truth.
- Detector failure (model fails to load, no WebGL/wasm) → `onEvent({ type: 'face_liveness_unavailable' })`, layer degrades to webcam-recording-only and the exam proceeds. An unsupported browser must not lock a student out of a graded exam; faculty still sees the degradation event in the timeline.

### 4. Expose the webcam stream from `useExamRecorder`
Add `getStream(kind)` to `useExamRecorder`'s return value (one-line accessor over the private `streams` map). The liveness composable consumes `getStream('webcam')` after `requestPermissions()` succeeds. Selecting `face_liveness` implies `webcam` (sanitizer guarantees it), so the stream always exists when the layer is on.

### 5. Take.vue flow — verification phase between consent and start
`begin()` gains a phase when `face_liveness` is on:

1. `requestPermissions()` (unchanged, user gesture).
2. **New `verifying` phase**: render a camera preview + guidance card ("Position your face in the frame and blink"); recording (`recorder.start()`) begins here so the verification period is also on tape, but `started` stays false → questions never render.
3. On `verified` → existing start sequence runs (fingerprint, guards, events, timer, question entry) and `face_verified` is logged.

While `monitoring`, a `softWarning` state renders a top banner AND blurs the questions (content protection, no penalty — the photographable window shrinks to ~3s); a `blocked` state renders a full-screen fixed overlay (z-index above the watermark layer) covering the questions with the warning text, seconds-without-face counter, and remaining-warning count. Pointer events blocked; the exam countdown keeps running (server deadline is unaffected anyway). The gate card shows a "Continue without face verification" button once `canBypass` fires. Detection stops on submit/auto-submit/unmount via `stopDetection()`.

### 6. Config + registry entry
`config/exam_security.php`:

```php
'face_liveness' => [
    'label' => 'Face liveness detection',
    'description' => 'Verify a live face (eye-blink check) before questions show and continuously during the exam.',
    'category' => 'media', 'default' => false, 'available' => true,
    'media' => true, 'privacy_notice' => true,
],
// tuning block 'liveness' (top-level, sibling of 'recording' / 'risk')
'liveness' => [
    'soft_warning_seconds' => 3,   // non-blocking banner
    'grace_seconds' => 8,          // blocking overlay + incident
    'free_warnings' => 2,
    'blink_close_threshold' => 0.5,
    'blink_open_threshold' => 0.25,
    'ear_close_threshold' => 0.2,  // EAR fallback for masked faces
    'ear_open_threshold' => 0.25,
    'min_detection_confidence' => 0.3,
    'min_presence_confidence' => 0.3,
    'no_blink_spoof_seconds' => 90,
    'gate_bypass_seconds' => 30,
    'detect_interval_ms' => 120,
],
```

`sanitizeSelection()` gains `face_liveness ⇒ webcam` mirroring the `lock_back ⇒ sequential` rule; `clientConfig()` ships the tuning block + asset paths when the layer is on. Admin/Faculty UIs pick the layer up automatically from the registry (dependency auto-tick handled in `Form.vue`'s existing watcher pattern).

### 7. Risk scoring + timeline labels
`computeRisk()` gains a `face_loss` factor (e.g., +15 when ≥1 `face_lost`/`face_liveness_violation` beyond the free warnings, mirroring `frequent_focus_loss` weighting via `risk.weights.face_loss`). `Attempt.vue` event-label map gains all liveness event types, including `face_verification_bypassed` and `no_blink_suspicion`.

## Risks / Trade-offs

- [False negatives: glasses/low light/masks cause missed faces → unfair warnings] → two-stage response (3s free banner before the 8s block), detection confidences lowered to 0.3, EAR blink fallback, overlay clears instantly on re-detection, first two incidents cost nothing, blink only required once at the gate, 30s gate bypass as the last resort. All thresholds config-tunable without deploys.
- [Gate bypass could be abused to dodge verification] → the bypass is a logged warning event in the faculty timeline, the webcam recording still runs for the full attempt, and monitoring (face-loss blocking) continues after bypass.
- [CPU load of a 1–3h vision loop on weak laptops] → throttle inference to ~8 fps (`detect_interval_ms`), reuse the recorder's stream, `VIDEO` running mode with GPU delegate + CPU fallback.
- [Model/wasm fails to load (old browser, blocked wasm)] → graceful degradation to webcam-recording-only with a timeline event; never blocks exam start after consent (Decision 3).
- [Client-side detection is bypassable by a tampered client] → same trust level as every existing lockdown layer; the webcam recording remains the ground truth for faculty review, and liveness events are corroborating signal, not a verdict.
- [Blink gate could frustrate students who don't blink "on cue"] → guidance text explicitly asks for a blink; average spontaneous blink rate (~15/min) verifies most students within seconds.
- [~4 MB model + wasm added to `public/`] → loaded only on liveness-enabled exams, cacheable; not part of the main Vite bundle.

## Migration Plan

1. Ship config + service changes (layer defaults `available: true, default: false`) — inert until faculty enables it on a test.
2. `npm install @mediapipe/tasks-vision`; add a post-install/copy step for wasm + model into `public/vendor/mediapipe/` (documented in README; assets committed or fetched via script).
3. No DB migration. Rollback = mark the layer unavailable in Admin → Exam Security (existing kill switch) or revert the config entry.

## Open Questions

- None blocking. (If offline labs can't download the model at setup time, commit the `.task` file to the repo — decide during implementation based on repo-size preference.)

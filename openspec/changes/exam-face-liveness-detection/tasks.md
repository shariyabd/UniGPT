## 1. Dependencies & assets

- [x] 1.1 `npm install @mediapipe/tasks-vision`; copy the wasm bundle from `node_modules/@mediapipe/tasks-vision/wasm/` into `public/vendor/mediapipe/wasm/`
- [x] 1.2 Obtain the `face_landmarker.task` model (~3.7 MB) and place it at `public/vendor/mediapipe/face_landmarker.task`; document the asset setup in README (commit the file or add a fetch script)

## 2. Backend — config & service

- [x] 2.1 Add the `face_liveness` layer entry to `config/exam_security.php` (`category: media`, `default: false`, `available: true`, `media: true`, `privacy_notice: true`) plus a top-level `liveness` tuning block (`grace_seconds: 15`, `free_warnings: 2`, blink thresholds, `detect_interval_ms`)
- [x] 2.2 In `ExamSecurityService::sanitizeSelection()`, enforce the dependency: `face_liveness` is forced off unless `webcam` is selected (mirror the `lock_back ⇒ sequential` rule)
- [x] 2.3 In `ExamSecurityService::clientConfig()`, when `face_liveness` is enabled include the tuning block + asset paths (wasm dir, model URL) in the payload sent to `Take.vue`
- [x] 2.4 Add a `face_loss` weight to the `risk` config and extend `ExamSecurityService::computeRisk()` to count `face_lost` / `face_liveness_violation` events beyond the free warnings
- [x] 2.5 Add the friendly consent-screen rule text for `face_liveness` wherever layer rule descriptions are built (so the pre-exam rules list discloses local face/blink checking)

## 3. Frontend — liveness composable

- [x] 3.1 Create `resources/js/composables/useFaceLiveness.js`: FaceLandmarker init (`FilesetResolver` from local wasm path, `outputFaceBlendshapes: true`, VIDEO mode, GPU delegate with CPU fallback), hidden `<video>` fed by the shared webcam stream, throttled `detectForVideo` loop
- [x] 3.2 Implement the blink hysteresis state machine (eyeBlinkLeft/Right ≥ close threshold then < open threshold) and the state flow `loading → verifying → monitoring ⇄ blocked → stopped`, with the consecutive no-face timer against `grace_seconds`
- [x] 3.3 Implement incident escalation and the `onEvent` callback: `face_verified` (info), `face_lost` (warning, with outage duration), `face_restored` (info), `face_liveness_violation` (violation, incidents beyond `free_warnings`), `face_liveness_unavailable` (info, on init failure → graceful degradation)
- [x] 3.4 Expose the webcam stream from `useExamRecorder` via a `getStream(kind)` accessor

## 4. Frontend — Take.vue integration

- [x] 4.1 Wire the composable in `Student/ClassTests/Take.vue`: instantiate when the `face_liveness` layer is on, start after `requestPermissions()` succeeds, stop on submit/auto-submit/unmount
- [x] 4.2 Add the verification-gate phase between consent and start: camera preview + "position your face and blink" guidance card; recording starts during verification but `started` stays false until `verified`; log `face_verified` and then run the existing start sequence
- [x] 4.3 Add the full-screen blocking overlay for the `blocked` state (covers questions, blocks pointer events, shows no-face duration and remaining warnings; timer keeps running); clear automatically on `face_restored`
- [x] 4.4 Route incidents into the event pipeline: warnings via `events.warn()`, violations via `events.report()` with the existing disqualified-response handling (auto-submit on disqualify)
- [x] 4.5 On detector init failure, show a non-blocking notice, log `face_liveness_unavailable`, and continue the exam with webcam recording only

## 5. Faculty & admin surfaces

- [x] 5.1 Verify Admin → Exam Security and the faculty class-test form pick up the new layer from the registry; add the `face_liveness ⇒ webcam` auto-tick/clear watcher in `Faculty/ClassTests/Form.vue` (mirroring the sequential/lock_back watcher)
- [x] 5.2 Add human-readable labels for the four new event types in the `Faculty/ClassTests/Attempt.vue` timeline; confirm risk factors display the face-loss factor

## 6. Tests & verification

- [x] 6.1 Feature tests: sanitization (`face_liveness` off without `webcam`), clientConfig includes liveness payload when enabled, `face_liveness_violation` events increment `violation_count` and disqualify past `max_warnings`, risk score includes the face-loss factor
- [x] 6.2 Run `./vendor/bin/pint`, `php artisan test`, `npm run build`; manually exercise the flow (verification gate, 15s block overlay, two-warning escalation) with the webcam layer on a draft test

## 7. Feedback revisions (2026-07-14 — two-stage timing + eye focus)

- [x] 7.1 Two-stage timing: `soft_warning_seconds` (3) non-blocking banner + `grace_seconds` lowered to 8 for the blocking overlay; config, clientConfig, composable and Take.vue banner
- [x] 7.2 Eye-focused detection: lower `minFaceDetectionConfidence`/`minFacePresenceConfidence` to 0.3; EAR blink fallback from raw eye landmarks alongside blendshapes
- [x] 7.3 No-blink spoof signal: `no_blink_suspicion` warning event after 90s of a visible face with zero blinks (re-arming window)
- [x] 7.4 Gate bypass: "Continue without face verification" after 30s of failed verification, logging `face_verification_bypassed`; monitoring continues after bypass
- [x] 7.5 Attempt.vue labels for the two new event types; FaceLivenessTest updated for the expanded liveness payload
- [x] 7.6 Content protection: questions blur (unreadable, pointer/select disabled) during the 3s soft stage — shrinks the camera-covered photo window from 8s to ~3s with no penalty; banner copy updated

## 8. Feedback round 2 (2026-07-14 — admin guide + fullscreen gate)

- [x] 8.1 Fullscreen hole fix: `isFullscreen` tracking + "Fullscreen required" gate overlay in Take.vue — a silently refused initial fullscreen request (no fullscreenchange event, so no violation ever fired) previously let students answer outside fullscreen; the paper now stays blurred/unusable until fullscreen is entered (not counted as a warning)
- [x] 8.2 Admin layer-guide offcanvas on the Exam Security page: slide-over panel explaining every layer's step-by-step flow (warnings/disqualification model, all lockdown/integrity/monitoring/media layers, full face-liveness lifecycle) with live config timings passed from the controller (`guideConfig`)

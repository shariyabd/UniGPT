## 1. Backend — config, split, storage

- [x] 1.1 Registry entries `snapshot_evidence` + `phone_detection` (media, consent, default off) + tuning blocks (`snapshots`: burst count/interval, periodic min/max, jpeg quality/max px, max_per_attempt, max_kb; `phone`: score_threshold, consecutive_hits, cooldown_seconds, detect_interval_ms, model_path) + `recording.video_bits_per_second` + `evidence_retention_days`
- [x] 1.2 `ExamSecurityService`: remove `face_liveness ⇒ webcam` rule; `clientConfig` gains `recording.camera` (any camera layer), `recording.videoBitsPerSecond`, `snapshots` + `phone` payloads; `storeSnapshot()`; `phone_activity` risk factor
- [x] 1.3 Migration + `ClassTestSnapshot` model (`class_test_snapshots`: attempt FK, trigger, sequence, disk, path, size_bytes)
- [x] 1.4 `Student/ClassTestController::snapshot` + `UploadClassTestSnapshotRequest` + route `class-tests.snapshot` (layer-gated, size + per-attempt caps)
- [x] 1.5 Faculty: snapshots in attempt-review payload + streaming route `faculty.class-tests.snapshot` (same authz as recordings)
- [x] 1.6 `exam:prune-evidence` command (recordings + snapshots of finalised attempts older than retention) + weekly schedule

## 2. Frontend — composables

- [x] 2.1 `useExamRecorder`: `camera` request flag vs recorded kinds; `videoBitsPerSecond` cap
- [x] 2.2 `useExamSnapshots.js`: canvas JPEG capture, `capture(trigger)` bursts, jittered periodic timer, client-side cap, best-effort upload
- [x] 2.3 `usePhoneDetection.js`: ObjectDetector init (GPU→CPU), ~2fps loop, consecutive-hit debounce + cooldown, graceful degradation
- [x] 2.4 `useFaceLiveness`: `numFaces: 2` + `multiple_faces` event with cooldown

## 3. Take.vue + consent + faculty form

- [x] 3.1 Wire snapshots (identity at verification, face_lost, violations) + phone detection (`phone_detected` warn + burst) + `multiple_faces`; stop everything on finalise/unmount
- [x] 3.2 `Instructions.vue`: camera-access consent wording + rule texts for the two new layers
- [x] 3.3 `Form.vue`: drop `face_liveness ⇒ webcam` watcher/disable

## 4. Review surfaces

- [x] 4.1 `Attempt.vue`: photo-strip section (trigger label + timestamp thumbnails, full-size view) + timeline labels for new events
- [x] 4.2 `Admin/ExamSecurity.vue` guide entries for both layers (+ webcam entry mentions bitrate cap/retention)

## 5. Verification

- [x] 5.1 `SnapshotEvidenceTest` (layer gating, caps, storage, faculty payload, risk factor, prune command); update `FaceLivenessTest` dependency test
- [x] 5.2 pint, `php artisan migrate`, full exam tests, `npm run build`, browser smoke

## 6. Audible alerts (follow-up, 2026-07-14)

- [x] 6.1 `useExamSounds.js` — Web Audio synthesised tones (no assets): soft double-beep `warning()`, harsher descending `danger()`; unlocked from the Begin-test gesture (autoplay policy); config kill-switch `exam_security.sounds_enabled` → `clientConfig().soundsEnabled`
- [x] 6.2 Take.vue hooks: danger on counted violations + face-block overlay; warning on soft face-loss stage, phone detection, second face, fullscreen gate; admin guide mentions audible alerts

## Why

Continuous webcam+screen recording does not scale: a 20-minute exam produces ~150–500 MB per student (no bitrate cap is set today), which at a 5–7k-student university means terabytes of storage and 10+ Gbps of concurrent chunk-upload ingest per exam sitting — and faculty never watch the footage anyway unless a flag points them at a moment. The evidence that matters is *moments*, not hours.

## What Changes

- **Decouple camera access from recording**: `webcam` layer = "record the webcam"; camera-based detection layers (face liveness, snapshots, phone detection) request the camera themselves via consent without forcing recording. `face_liveness` no longer requires the `webcam` layer.
- **New `snapshot_evidence` layer**: instead of continuous video, capture JPEG snapshot bursts from the webcam at high-signal moments — proctoring violations (tab switch, fullscreen exit), face-loss incidents, phone/second-face detections, identity at the verification gate — plus randomized periodic samples. ~3–4 MB per student vs ~150–500 MB.
- **New `phone_detection` layer**: MediaPipe ObjectDetector (EfficientDet-Lite0, self-hosted, COCO "cell phone" class) runs client-side on the webcam stream; a debounced confirmed detection logs a `phone_detected` warning event and triggers a snapshot burst. A review signal with photographic evidence — not an auto-violation (false positives exist).
- **Second-face flag**: Face Landmarker now tracks 2 faces; more than one face in frame logs `multiple_faces` (warning) + snapshot burst.
- **Recording cost caps**: `videoBitsPerSecond` cap (default 250 kbps) on MediaRecorder; `exam:prune-evidence` command deletes recordings/snapshots of finalised attempts older than a configurable retention window (scheduled weekly).
- Faculty attempt review gains a **photo-strip evidence section** (trigger-labelled thumbnails, full-size view); admin layer guide documents the new layers.

## Capabilities

### New Capabilities
- `exam-snapshot-evidence`: event-triggered + periodic snapshot capture, storage, caps, retention, and faculty review.
- `exam-phone-detection`: client-side phone/second-face detection with debounce, cooldown, and evidence capture.

### Modified Capabilities
- `exam-face-liveness`: the layer now requires camera access (self-provided via consent) instead of the `webcam` recording layer.

## Impact

- Backend: `ExamSecurityService` (registry entries, camera/recording split in `clientConfig`, snapshot storage, risk factor), new `ClassTestSnapshot` model + migration, `Student/ClassTestController::snapshot`, faculty review payload + snapshot streaming route, `exam:prune-evidence` command.
- Frontend: new `useExamSnapshots.js`, `usePhoneDetection.js`; `useFaceLiveness.js` (numFaces 2), `useExamRecorder.js` (camera vs record split, bitrate cap), `Take.vue` wiring, `Faculty/ClassTests/Attempt.vue` photo strip, `Instructions.vue` consent, `Admin/ExamSecurity.vue` guide entries, faculty Form watcher update.
- Assets: `public/vendor/mediapipe/efficientdet_lite0.tflite` (~7 MB, committed).
- DB: new `class_test_snapshots` table. Tests: `SnapshotEvidenceTest`; `FaceLivenessTest` dependency test updated.

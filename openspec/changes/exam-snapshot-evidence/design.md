## Context

Continuous recording (`useExamRecorder` → 15s webm chunks → private disk) has no bitrate cap and is the storage/ingest bottleneck at scale (~150–500 MB/student/20min; TBs + 10 Gbps ingest at 5–7k students). Camera-based detection layers currently piggyback on the `webcam` *recording* layer (`face_liveness ⇒ webcam` in `sanitizeSelection`), so the camera cannot be used without recording.

## Goals / Non-Goals

**Goals:** moment-based photographic evidence at ~3–4 MB/student; phone + second-face detection as review signals; camera decoupled from recording; recording bitrate cap + retention pruning; faculty photo-strip review.

**Non-Goals:** detecting phones outside the camera's field of view; treating detections as automatic violations; audio; server-side vision.

## Decisions

### 1. Camera vs recording split
`clientConfig().recording` gains `camera: bool` = webcam ∨ face_liveness ∨ snapshot_evidence ∨ phone_detection. `useExamRecorder` requests the webcam stream when `camera` is true but creates a MediaRecorder only for kinds actually recorded (`webcam`, `screen`). The `face_liveness ⇒ webcam` sanitize rule and Form.vue watcher are removed (consent still shown — all camera layers are `media: true, privacy_notice: true`). `Instructions.vue` consent copy distinguishes "camera access" from "recording".

### 2. Snapshots: canvas capture, trigger + periodic
`useExamSnapshots.js`: draws the shared webcam `<video>` to a canvas (capped 640px wide), `toBlob('image/jpeg', 0.7)` ≈ 40–100 KB. `capture(trigger)` fires a burst (default 3 frames / 700ms apart); a jittered timer (60–90s) fires `periodic` singles. Client stops at the per-attempt cap. Uploads POST `class-tests/{id}/snapshot` (trigger, sequence, blob) — best-effort like recording chunks. Server: `ClassTestSnapshot` model, `class_test_snapshots` table (attempt_id, trigger, sequence, disk, path, size_bytes), stored `exam-snapshots/{attempt}/`, endpoint gated on the layer + finalisation + size + count cap. Trigger vocabulary: `violation`, `face_lost`, `phone_detected`, `multiple_faces`, `identity`, `periodic`.

### 3. Phone detection: ObjectDetector, debounce + cooldown
`usePhoneDetection.js`: MediaPipe `ObjectDetector` (efficientdet_lite0.tflite, self-hosted beside the face model), VIDEO mode, own throttled loop (~2 fps — object detection is heavier than landmarks), watches category `cell phone` ≥ `score_threshold` (0.5). N consecutive hits (3) → fire `onDetected`; then cooldown (30s). Init failure → `phone_detection_unavailable`, exam proceeds. Second faces: `useFaceLiveness` sets `numFaces: 2`; >1 face → `onEvent('multiple_faces')` with its own 30s cooldown (face presence/blink logic keys off face[0]).

### 4. Severity: warning + evidence, not violation
`phone_detected` / `multiple_faces` are `warning` severity — object detection has false positives (calculators, posters), so a human judges the attached photos. Both add a `phone_activity` risk factor (weight 20) so flagged attempts sort up in review.

### 5. Cost controls regardless of layers
`recording.video_bits_per_second` (default 250_000) passed to MediaRecorder. `exam:prune-evidence` artisan command deletes recording chunks + snapshots (files + rows) for finalised attempts older than `evidence_retention_days` (default 30, 0 = keep forever); scheduled weekly.

## Risks / Trade-offs

- [Snapshots miss what happens between captures] → triggers cover the high-signal moments; periodic jitter prevents timing; `webcam` recording remains available per-test for high-stakes exams.
- [ObjectDetector false positives/negatives] → debounce, cooldown, warning-severity, photo evidence for human judgement; thresholds config-tunable.
- [Extra model (~7 MB) + second inference loop CPU] → loaded only when the layer is on; ~2 fps loop; GPU delegate with CPU fallback.
- [Privacy: photos of students] → same consent + private-disk + faculty-only access model as recordings; retention pruning applies to both.

## Migration Plan

1. Migration `class_test_snapshots`; layers ship `available: true, default: false` (inert until enabled).
2. `face_liveness ⇒ webcam` rule removal is behaviour-relaxing only (previously-forced-off configs stay off until faculty re-tick).
3. Rollback: mark layers unavailable in Admin → Exam Security.

## Open Questions

- None blocking.

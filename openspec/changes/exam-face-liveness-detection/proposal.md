## Why

The exam proctoring suite already records the student's webcam, but recording alone can't tell whether a live person is actually sitting the exam — a student can point the camera at a photo, an empty chair, or cover the lens and the recording layer stays silent. We need active face-liveness verification: confirm a real, blinking face is present before questions are revealed, and keep confirming it for the whole exam.

## What Changes

- New **`face_liveness` exam-security layer** in the existing config-driven registry (`config/exam_security.php`), admin-gated globally (Admin → Exam Security) and assignable per test by faculty in the class-test form — exactly like the existing lockdown/monitoring/media layers.
- New frontend composable wrapping **MediaPipe Face Landmarker** (`@mediapipe/tasks-vision`) that runs on the webcam stream during the exam and detects face presence and eye blinks (via eye-blendshape scores).
- **Pre-exam verification gate**: when the layer is on, questions stay hidden until the student's face and at least one blink are successfully detected. Only after verification does the exam content render and the timer-backed attempt begin on screen.
- **Continuous monitoring until the exam ends**, two-stage: after **3 seconds** without a face a non-blocking banner warns the student back (no penalty); after **8 seconds** the exam screen is blocked by a full-screen warning overlay until the face is re-detected. Each blocking counts as one warning; after **2 warnings**, subsequent losses are reported as `violation`-severity events, feeding the existing `violation_count` / `max_warnings` disqualification pipeline.
- **Eye-focused, covered-face tolerant**: blinks detected via eye blendshapes OR eye-aspect-ratio landmark geometry (works with masks); detection confidences lowered so partially covered faces register; a face visible for 90s with no blink logs a `no_blink_suspicion` review signal (photo-spoof pattern); after 30s of failed verification a logged "continue without verification" bypass prevents locking anyone out.
- Liveness events (`face_verified`, `face_lost`, `face_restored`, `face_liveness_violation`, `face_verification_bypassed`, `no_blink_suspicion`) flow through the existing `POST /class-tests/{id}/events` pipeline and appear in the faculty attempt-review timeline; risk scoring gains a face-loss factor.
- Layer dependency: `face_liveness` requires the `webcam` layer (same stream); enforced in `sanitizeSelection()` like the existing `lock_back → sequential` rule.

## Capabilities

### New Capabilities
- `exam-face-liveness`: Face-presence and blink-based liveness verification on the student exam screen — pre-exam verification gate, continuous 15-second-window monitoring with a two-warning escalation, per-test enablement via the exam-security layer registry, and event reporting into the existing proctoring timeline.

### Modified Capabilities

<!-- none: the exam-security layer registry, event pipeline, and disqualification rules
     are extended through their existing extension points (no openspec/specs exist yet) -->

## Impact

- **Config**: `config/exam_security.php` — new layer entry + `face_liveness` tuning block (grace seconds, max warnings, blink thresholds).
- **Backend**: `app/Domain/Academic/Services/ExamSecurityService.php` (dependency rule, client config payload, risk factor), `LogClassTestEventsRequest` (already generic — new event types pass through).
- **Frontend**: new `resources/js/composables/useFaceLiveness.js`; `resources/js/pages/Student/ClassTests/Take.vue` (verification gate + warning overlay); `Faculty/ClassTests/Form.vue` and `Admin/ExamSecurity.vue` pick the new layer up automatically from the registry; `Faculty/ClassTests/Attempt.vue` timeline labels for new event types.
- **Dependencies**: adds `@mediapipe/tasks-vision` npm package; Face Landmarker model asset (`.task` file) served locally from `public/` (no third-party CDN at exam time).
- **No schema changes**: reuses `class_tests.security_config`, `class_test_events`, and `class_test_attempts.violation_count`.
- **Privacy**: no video frames leave the browser for liveness; detection is fully client-side on the existing consented webcam stream.

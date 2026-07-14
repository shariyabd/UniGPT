## ADDED Requirements

### Requirement: Face-liveness is a configurable exam-security layer
The system SHALL expose face-liveness detection as a layer named `face_liveness` in the exam-security layer registry, gated globally by admins (available / on-by-default) and selectable per class test by faculty, identically to existing layers. The layer SHALL depend on the `webcam` layer: a per-test selection enabling `face_liveness` without `webcam` MUST be normalized so `face_liveness` is disabled.

#### Scenario: Admin gates the layer globally
- **WHEN** an admin marks `face_liveness` as unavailable on the Admin → Exam Security page
- **THEN** the layer no longer appears in the faculty class-test form and is disabled for newly configured tests

#### Scenario: Faculty enables the layer on a test
- **WHEN** faculty creates or edits a class test and ticks Face-liveness detection (with Webcam recording enabled)
- **THEN** the test's `security_config` stores `face_liveness: true` and the take-exam page receives it in the security client config

#### Scenario: Dependency on webcam layer is enforced
- **WHEN** a submitted per-test selection has `face_liveness: true` but `webcam: false`
- **THEN** sanitization stores `face_liveness: false`

### Requirement: Pre-exam face verification gate
When `face_liveness` is enabled for a test, the exam questions SHALL remain hidden after the student grants camera permission until the liveness detector confirms a live face — defined as a detected face plus at least one detected eye blink. Only after successful verification SHALL the questions render and the on-screen exam begin. A `face_verified` info event SHALL be logged on success.

#### Scenario: Student verifies successfully
- **WHEN** the student starts the exam, grants camera access, and the detector observes their face and an eye blink
- **THEN** the verification gate clears, the questions become visible, and a `face_verified` event is logged

#### Scenario: No face presented at start
- **WHEN** the camera is on but no live face (or no blink) is detected
- **THEN** the questions stay hidden and the gate keeps showing guidance (e.g., "Position your face in the camera and blink") until verification succeeds

#### Scenario: Layer disabled
- **WHEN** `face_liveness` is not enabled for the test
- **THEN** the exam starts exactly as today with no verification gate and no liveness processing

### Requirement: Continuous liveness monitoring until exam end
While the attempt is in progress, the system SHALL keep running face-liveness detection on the webcam stream until the exam is submitted, expires, or the student leaves the page. Detection SHALL be entirely client-side; no video frames are transmitted for liveness purposes.

#### Scenario: Monitoring spans the whole exam
- **WHEN** the student is answering questions with `face_liveness` enabled
- **THEN** the detector continuously evaluates face presence, and stops (releasing camera processing) when the attempt ends

### Requirement: Two-stage no-face response blocks the exam screen
Face loss during the exam SHALL be handled in two stages. After `soft_warning_seconds` (default 3) without a detected face, a banner SHALL warn the student to return to the camera AND the questions SHALL blur to an unreadable state (content protection against photographing the paper while the camera is covered) — with no penalty and no event; the blur SHALL lift the instant a face is re-detected. After `grace_seconds` (default 8) the system SHALL block the exam screen with a full-screen warning overlay (questions obscured, interaction prevented) and log a `face_lost` warning event. The overlay SHALL clear automatically as soon as a face is re-detected, logging a `face_restored` info event. The exam timer SHALL continue running while blocked.

#### Scenario: Soft warning at 3 seconds hides the paper
- **WHEN** the detector finds no face for 3 consecutive seconds mid-exam
- **THEN** a warning banner appears, the questions blur beyond readability, no warning is consumed, and the blur lifts immediately when the face returns

#### Scenario: Screen blocks at the grace threshold
- **WHEN** the detector finds no face for 8 consecutive seconds mid-exam
- **THEN** a blocking warning overlay covers the questions and a `face_lost` warning event is logged with the outage duration

#### Scenario: Face returns
- **WHEN** the student's face is detected again while the overlay is shown
- **THEN** the overlay clears, the exam is usable again, and a `face_restored` event is logged

#### Scenario: Brief look-away does not trigger
- **WHEN** the face is undetected for less than the soft-warning threshold (e.g., glancing at the keyboard)
- **THEN** no warning is shown and no event is logged

### Requirement: Two-warning escalation to violation
The first two blocking face-loss incidents in an attempt SHALL be warnings only. Each subsequent blocking incident SHALL additionally be reported as a `face_liveness_violation` event with `violation` severity, which increments the attempt's violation count and is subject to the test's existing `max_warnings` disqualification rule.

#### Scenario: First and second incidents are warnings
- **WHEN** the student's face is lost past the grace threshold a first and then a second time
- **THEN** the blocking overlay appears each time with the remaining-warning count, and only warning-severity events are recorded

#### Scenario: Third incident becomes a violation
- **WHEN** a third blocking face-loss incident occurs in the same attempt
- **THEN** a `face_liveness_violation` event (severity `violation`) is reported immediately and the attempt's violation count increments, feeding the existing disqualification threshold

### Requirement: Eye-focused detection tolerates covered faces
Blink detection SHALL combine eye-blendshape scores with an eye-aspect-ratio (EAR) computed from raw eye landmarks, so a blink registers when either signal completes a close/re-open cycle — masks muffle blendshape scores while eye geometry still moves. Face detection and presence confidence thresholds SHALL be kept low (default 0.3) so partially covered faces (masks, niqab) still register.

#### Scenario: Masked student blinks
- **WHEN** a student wearing a face mask blinks and the blendshape scores stay muted but the EAR cycle completes
- **THEN** the blink counts for verification and for the no-blink spoof window

### Requirement: No-blink spoof signal
If a face remains continuously visible for `no_blink_spoof_seconds` (default 90) without a single detected blink, the system SHALL log a `no_blink_suspicion` warning event (a review signal — it does not consume a warning or increment the violation count) and re-arm the window so a persisting photo keeps flagging.

#### Scenario: Photo held in front of the camera
- **WHEN** a static face is visible for 90 seconds with no blink
- **THEN** a `no_blink_suspicion` warning event is logged for faculty review and the exam continues unaffected

### Requirement: Verification-gate bypass prevents lockout
If verification has not succeeded after `gate_bypass_seconds` (default 30), the gate SHALL offer a "Continue without face verification" action. Using it SHALL log a `face_verification_bypassed` warning event visible in the faculty timeline, start the exam, and continue liveness monitoring as normal. Webcam recording runs throughout regardless.

#### Scenario: Covered face cannot be detected
- **WHEN** a student whose face the detector cannot register waits 30 seconds at the gate and chooses to continue
- **THEN** the exam starts, a `face_verification_bypassed` event is recorded, and monitoring plus recording continue

### Requirement: Faculty visibility of liveness events
Face-liveness events (`face_verified`, `face_lost`, `face_restored`, `face_liveness_violation`, `face_verification_bypassed`, `no_blink_suspicion`, `face_liveness_unavailable`) SHALL appear in the faculty attempt-review timeline with human-readable labels, and repeated face loss SHALL contribute to the attempt's risk score when risk analysis is enabled.

#### Scenario: Faculty reviews an attempt with face-loss incidents
- **WHEN** faculty opens the attempt detail page for an attempt that had face-loss warnings
- **THEN** the timeline shows the liveness events in order with durations, and the risk factors include face loss when `risk_analysis` was enabled

### Requirement: Student-facing disclosure
When `face_liveness` is enabled, the pre-exam rules/consent screen SHALL disclose that face presence and eye blinks are checked during the exam and that detection runs locally in the browser.

#### Scenario: Consent screen lists the layer
- **WHEN** a student opens a test with `face_liveness` enabled
- **THEN** the rules list includes a face-liveness notice alongside the existing webcam-recording notice

## MODIFIED Requirements

### Requirement: Face-liveness is a configurable exam-security layer
The system SHALL expose face-liveness detection as a layer named `face_liveness` in the exam-security layer registry, gated globally by admins (available / on-by-default) and selectable per class test by faculty, identically to existing layers. The layer SHALL require camera access (requested with consent at exam start) but SHALL NOT require the `webcam` recording layer — detection runs on the camera stream whether or not it is being recorded.

#### Scenario: Admin gates the layer globally
- **WHEN** an admin marks `face_liveness` as unavailable on the Admin → Exam Security page
- **THEN** the layer no longer appears in the faculty class-test form and is disabled for newly configured tests

#### Scenario: Faculty enables the layer on a test
- **WHEN** faculty creates or edits a class test and ticks Face-liveness detection
- **THEN** the test's `security_config` stores `face_liveness: true` and the take-exam page receives it in the security client config

#### Scenario: Liveness without recording
- **WHEN** a test enables `face_liveness` but not `webcam`
- **THEN** the student consents to camera access, liveness detection runs, and no continuous recording is stored

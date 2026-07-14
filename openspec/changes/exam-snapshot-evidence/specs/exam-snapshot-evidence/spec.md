## ADDED Requirements

### Requirement: Snapshot evidence is a configurable exam-security layer
The system SHALL expose `snapshot_evidence` as a media-category layer in the exam-security registry (admin-gated, per-test, consent-listed). When enabled it SHALL request camera access at exam start (shared with other camera layers) without requiring the `webcam` recording layer.

#### Scenario: Snapshots without recording
- **WHEN** a test enables `snapshot_evidence` but not `webcam`
- **THEN** the student consents to camera access, no continuous recording is made, and snapshots are still captured

### Requirement: Event-triggered snapshot bursts
When `snapshot_evidence` is enabled, the client SHALL capture a JPEG burst (configurable count/interval, default 3 frames over ~2s) from the webcam stream when: a proctoring violation is reported (tab switch, fullscreen exit), a face-loss incident crosses the soft threshold, a phone or second face is detected, and once at successful face verification (identity shot). Each snapshot SHALL be uploaded with its trigger type and stored on a private disk under the attempt.

#### Scenario: Tab switch captures the moment
- **WHEN** a student switches tabs mid-exam with `snapshot_evidence` on
- **THEN** a snapshot burst tagged `violation` is captured and uploaded alongside the violation event

### Requirement: Randomized periodic sampling
The client SHALL additionally capture single snapshots at randomized intervals (default 60–90s jitter) so capture moments cannot be timed, tagged `periodic`.

#### Scenario: Unpredictable sampling
- **WHEN** an exam runs for 20 minutes with the layer enabled
- **THEN** roughly 15–20 periodic snapshots exist at non-uniform timestamps

### Requirement: Server-side caps and validation
The snapshot endpoint SHALL reject uploads when the layer is not enabled for the test, when the attempt is finalised, when the file exceeds the size cap, or when the attempt has reached the per-attempt snapshot cap (default 60). Uploads are best-effort — failures never interrupt the exam.

#### Scenario: Cap reached
- **WHEN** an attempt already has the maximum number of snapshots
- **THEN** further uploads are refused and the exam continues unaffected

### Requirement: Faculty photo-strip review
Faculty SHALL see the attempt's snapshots on the attempt-review page as a trigger-labelled, timestamped thumbnail strip with full-size viewing, streamed through an authorised route (same access rules as recordings).

#### Scenario: Reviewing a flagged attempt
- **WHEN** faculty opens an attempt that logged a phone detection
- **THEN** the photo strip shows the burst captured at that moment next to the event timeline

### Requirement: Recording cost controls
MediaRecorder uploads SHALL be bitrate-capped via config (default 250 kbps). An `exam:prune-evidence` command SHALL delete recordings and snapshots belonging to finalised attempts older than a configurable retention window (default 30 days), and SHALL be schedulable.

#### Scenario: Retention pruning
- **WHEN** `exam:prune-evidence` runs and finalised attempts are older than the window
- **THEN** their recording chunks and snapshots are deleted from disk and their index rows removed

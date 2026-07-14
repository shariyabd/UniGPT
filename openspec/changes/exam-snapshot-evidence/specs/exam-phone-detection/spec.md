## ADDED Requirements

### Requirement: Phone detection is a configurable exam-security layer
The system SHALL expose `phone_detection` as a media-category layer running MediaPipe ObjectDetector (self-hosted EfficientDet-Lite0) client-side on the webcam stream, watching for the COCO `cell phone` class. No frames SHALL leave the browser for detection.

#### Scenario: Layer disabled
- **WHEN** `phone_detection` is not enabled
- **THEN** the object-detection model is never loaded

### Requirement: Debounced detection with cooldown
A phone detection SHALL require N consecutive positive frames (default 3) above a confidence threshold (default 0.5) before firing, and after firing SHALL cool down (default 30s) before it can fire again. A confirmed detection SHALL log a `phone_detected` event with `warning` severity (a review signal with evidence, not an automatic violation) and trigger a snapshot burst when `snapshot_evidence` is enabled.

#### Scenario: Student raises a phone to photograph the screen
- **WHEN** a phone is visible in the webcam frame for the required consecutive frames
- **THEN** a `phone_detected` warning event is logged, a snapshot burst is captured, and no violation is counted

#### Scenario: Transient false positive
- **WHEN** a phone-like object appears for a single frame
- **THEN** nothing is logged

### Requirement: Second-face detection
When face liveness is active, the detector SHALL track up to two faces; more than one face in frame SHALL log a `multiple_faces` warning event (with cooldown) and trigger a snapshot burst when `snapshot_evidence` is enabled.

#### Scenario: A second person leans into frame
- **WHEN** two faces are detected simultaneously
- **THEN** a `multiple_faces` warning event is logged with photographic evidence

### Requirement: Detection failure degrades gracefully
If the object-detection model fails to load, the layer SHALL log `phone_detection_unavailable` (info) and the exam SHALL proceed unaffected.

#### Scenario: Unsupported browser
- **WHEN** the model cannot initialise
- **THEN** the exam continues and the degradation event appears in the timeline

### Requirement: Faculty visibility and risk contribution
`phone_detected` and `multiple_faces` events SHALL appear with readable labels in the attempt timeline, and SHALL contribute a `phone_activity` factor to the risk score when risk analysis is enabled.

#### Scenario: Risk score reflects phone activity
- **WHEN** an attempt logged phone detections and risk analysis is on
- **THEN** the computed risk factors include phone activity

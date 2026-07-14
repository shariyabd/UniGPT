import { ref } from 'vue';
import { FaceLandmarker, FilesetResolver } from '@mediapipe/tasks-vision';

/**
 * Face-liveness detection for a proctored exam (the `face_liveness` layer).
 *
 * Runs MediaPipe Face Landmarker entirely client-side on the webcam stream the
 * exam recorder already opened — no frames ever leave the browser. Jobs:
 *
 *  1. Verification gate: before the questions render, require a detected face
 *     plus at least one eye blink (a printed photo doesn't blink). After
 *     `gateBypassSeconds` of failed verification the student may continue
 *     without it (logged for faculty review) — a covered face must never lock
 *     anyone out of a graded exam.
 *  2. Continuous monitoring, two-stage: after `softWarningSeconds` without a
 *     face a non-blocking banner asks the student back; at `graceSeconds` the
 *     screen blocks and the incident counts. The first `freeWarnings` incidents
 *     are warnings; each one after that is also a violation (feeding the
 *     max_warnings disqualification pipeline).
 *  3. Spoof signal: a visible "face" that never blinks for `noBlinkSpoofSeconds`
 *     is the photo-in-front-of-camera pattern — flagged for review, not punished.
 *
 * Eye focus: blinks are detected from eye blendshapes OR the eye-aspect-ratio
 * computed from raw eye landmarks (EAR) — masks muffle blendshape scores but
 * the eye geometry still moves. Detection confidences are kept low so
 * partially covered faces (masks, niqab) still register.
 *
 * States: loading → verifying → monitoring ⇄ blocked → stopped.
 * Init failure (old browser, blocked wasm) emits `face_liveness_unavailable`
 * and settles in `unavailable` — the exam proceeds without the detector.
 *
 * @param {object}   opts
 * @param {object}   opts.config    liveness payload from clientConfig()
 * @param {function} opts.onEvent   ({ type, severity, meta }) — caller routes into the event pipeline
 */

// Canonical MediaPipe face-mesh indices for the EAR calculation:
// [outer corner, upper 1, upper 2, inner corner, lower 2, lower 1]
const LEFT_EYE = [33, 160, 158, 133, 153, 144];
const RIGHT_EYE = [362, 385, 387, 263, 373, 380];

export function useFaceLiveness({ config, onEvent = () => {} }) {
    const number = (value, fallback) => (Number.isFinite(+value) ? +value : fallback);

    const softMs = Math.max(1, number(config?.softWarningSeconds, 3)) * 1000;
    const graceMs = Math.max(2, number(config?.graceSeconds, 8)) * 1000;
    const freeWarnings = Math.max(0, number(config?.freeWarnings, 2));
    const closeThreshold = number(config?.blinkCloseThreshold, 0.5);
    const openThreshold = number(config?.blinkOpenThreshold, 0.25);
    const earClose = number(config?.earCloseThreshold, 0.2);
    const earOpen = number(config?.earOpenThreshold, 0.25);
    const spoofMs = Math.max(30, number(config?.noBlinkSpoofSeconds, 90)) * 1000;
    const bypassMs = Math.max(10, number(config?.gateBypassSeconds, 30)) * 1000;
    const intervalMs = Math.max(50, number(config?.detectIntervalMs, 120));

    const state = ref('loading'); // loading | verifying | monitoring | blocked | stopped | unavailable
    const verified = ref(false);
    const blocked = ref(false);
    const softWarning = ref(false);
    const faceVisible = ref(false);
    const blinked = ref(false);
    const canBypass = ref(false);
    const warningsUsed = ref(0);
    const secondsWithoutFace = ref(0);

    let landmarker = null;
    let video = null;
    let rafId = null;
    let lastDetectAt = 0;
    let lastVideoTime = -1;
    let noFaceSince = null; // ms timestamp of the moment the face was last seen
    let outageStartedAt = null; // set when an incident (grace crossed) begins
    let eyesClosed = false; // blink hysteresis state
    let verifyingSince = null;
    let spoofWindowStart = null; // face continuously visible without a blink since
    let multiFaceCooledAt = 0;

    const MULTI_FACE_COOLDOWN_MS = 30_000;

    const emit = (type, severity, meta = {}) => onEvent({ type, severity, meta });

    /**
     * Initialise the landmarker and attach the shared webcam stream to a hidden
     * <video>. Resolves { ok } — on failure the layer degrades gracefully.
     */
    const start = async (stream) => {
        try {
            const fileset = await FilesetResolver.forVisionTasks(config.wasmPath);
            landmarker = await createLandmarker(fileset);

            video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.srcObject = stream;
            await video.play();

            state.value = 'verifying';
            verifyingSince = performance.now();
            loop();

            return { ok: true };
        } catch (e) {
            state.value = 'unavailable';
            emit('face_liveness_unavailable', 'info', { reason: e?.message || 'init_failed' });
            return { ok: false };
        }
    };

    const createLandmarker = async (fileset) => {
        const options = (delegate) => ({
            baseOptions: { modelAssetPath: config.modelPath, delegate },
            runningMode: 'VIDEO',
            // Two faces so a second person leaning into frame is detectable;
            // presence/blink logic keys off the first (primary) face.
            numFaces: 2,
            outputFaceBlendshapes: true,
            minFaceDetectionConfidence: number(config?.minDetectionConfidence, 0.3),
            minFacePresenceConfidence: number(config?.minPresenceConfidence, 0.3),
        });
        try {
            return await FaceLandmarker.createFromOptions(fileset, options('GPU'));
        } catch {
            return await FaceLandmarker.createFromOptions(fileset, options('CPU'));
        }
    };

    const loop = () => {
        if (state.value === 'stopped' || state.value === 'unavailable') return;
        rafId = requestAnimationFrame(loop);

        const now = performance.now();
        if (now - lastDetectAt < intervalMs) return;
        lastDetectAt = now;

        if (state.value === 'verifying' && !canBypass.value && now - verifyingSince >= bypassMs) {
            canBypass.value = true;
        }

        // detectForVideo must see a fresh frame; re-running on the same frame throws off timestamps.
        if (!video || video.readyState < 2 || video.currentTime === lastVideoTime) return;
        lastVideoTime = video.currentTime;

        let result;
        try {
            result = landmarker.detectForVideo(video, now);
        } catch {
            return; // transient inference error — skip this frame
        }

        const faceCount = result?.faceLandmarks?.length ?? 0;
        const face = faceCount > 0;
        faceVisible.value = face;

        // A second person leaning into frame — review signal with cooldown.
        if (faceCount > 1 && state.value === 'monitoring' && now >= multiFaceCooledAt) {
            multiFaceCooledAt = now + MULTI_FACE_COOLDOWN_MS;
            emit('multiple_faces', 'warning', { faces: faceCount });
        }

        if (face) {
            trackBlink(result, now);
            onFaceSeen(now);
        } else {
            onFaceMissing(now);
        }
    };

    // Eye-aspect-ratio for one eye: (‖p2−p6‖ + ‖p3−p5‖) / (2·‖p1−p4‖).
    const eyeAspectRatio = (landmarks, indices) => {
        const p = indices.map((i) => landmarks[i]);
        if (p.some((point) => !point)) return null;
        const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
        const horizontal = dist(p[0], p[3]);
        if (horizontal === 0) return null;
        return (dist(p[1], p[5]) + dist(p[2], p[4])) / (2 * horizontal);
    };

    const trackBlink = (result, now) => {
        const shapes = result.faceBlendshapes?.[0]?.categories;
        let left = 0;
        let right = 0;
        if (shapes) {
            for (const shape of shapes) {
                if (shape.categoryName === 'eyeBlinkLeft') left = shape.score;
                else if (shape.categoryName === 'eyeBlinkRight') right = shape.score;
            }
        }

        const landmarks = result.faceLandmarks[0];
        const leftEar = eyeAspectRatio(landmarks, LEFT_EYE);
        const rightEar = eyeAspectRatio(landmarks, RIGHT_EYE);
        const ear = leftEar !== null && rightEar !== null ? (leftEar + rightEar) / 2 : null;

        // Hysteresis on either signal: blendshapes for open faces, EAR geometry
        // when a mask muffles the blendshape scores. A full close/open cycle
        // counts as one blink.
        const blendClosed = left >= closeThreshold && right >= closeThreshold;
        const blendOpen = left < openThreshold && right < openThreshold;
        const earClosed = ear !== null && ear < earClose;
        const earOpened = ear === null || ear > earOpen;

        if (!eyesClosed && (blendClosed || earClosed)) {
            eyesClosed = true;
        } else if (eyesClosed && blendOpen && earOpened) {
            eyesClosed = false;
            blinked.value = true;
            spoofWindowStart = now;
        }
    };

    const onFaceSeen = (now) => {
        noFaceSince = null;
        secondsWithoutFace.value = 0;
        softWarning.value = false;

        if (state.value === 'verifying' && blinked.value) {
            verified.value = true;
            state.value = 'monitoring';
            spoofWindowStart = now;
            emit('face_verified', 'info');
        } else if (state.value === 'blocked') {
            blocked.value = false;
            state.value = 'monitoring';
            emit('face_restored', 'info', {
                outageMs: outageStartedAt ? Math.round(now - outageStartedAt) : null,
            });
            outageStartedAt = null;
        }

        // A "face" that stays visible but never blinks reads as a photo. The
        // window re-arms after each flag so a persisting photo keeps flagging.
        if (state.value === 'monitoring') {
            if (spoofWindowStart === null) spoofWindowStart = now;
            if (now - spoofWindowStart >= spoofMs) {
                emit('no_blink_suspicion', 'warning', { windowMs: Math.round(now - spoofWindowStart) });
                spoofWindowStart = now;
            }
        }
    };

    const onFaceMissing = (now) => {
        // The gate has its own UI ("position your face…"); outage timing only
        // applies once the exam is actually underway.
        if (state.value !== 'monitoring' && state.value !== 'blocked') return;

        spoofWindowStart = null; // the spoof window requires continuous visibility

        if (noFaceSince === null) noFaceSince = now;
        const elapsed = now - noFaceSince;
        secondsWithoutFace.value = Math.floor(elapsed / 1000);

        if (state.value === 'monitoring') {
            softWarning.value = elapsed >= softMs;

            if (elapsed >= graceMs) {
                softWarning.value = false;
                state.value = 'blocked';
                blocked.value = true;
                outageStartedAt = now;
                warningsUsed.value += 1;

                const isViolation = warningsUsed.value > freeWarnings;
                emit('face_lost', 'warning', { graceMs, incident: warningsUsed.value });
                if (isViolation) {
                    emit('face_liveness_violation', 'violation', { incident: warningsUsed.value });
                }
            }
        }
    };

    /**
     * Skip the verification gate after repeated failure (covered face, poor
     * camera). Logged for faculty review; monitoring continues as normal.
     */
    const bypassGate = () => {
        if (state.value !== 'verifying') return;
        emit('face_verification_bypassed', 'warning', {
            waitedMs: verifyingSince ? Math.round(performance.now() - verifyingSince) : null,
        });
        state.value = 'monitoring';
        spoofWindowStart = performance.now();
        verified.value = true;
    };

    /** Stop detection and release everything (submit / unmount). */
    const stopDetection = () => {
        state.value = 'stopped';
        if (rafId) cancelAnimationFrame(rafId);
        rafId = null;
        try {
            landmarker?.close();
        } catch {
            // ignore
        }
        landmarker = null;
        if (video) {
            video.srcObject = null;
            video = null;
        }
    };

    return {
        state,
        verified,
        blocked,
        softWarning,
        faceVisible,
        blinked,
        canBypass,
        warningsUsed,
        secondsWithoutFace,
        freeWarnings,
        start,
        bypassGate,
        stopDetection,
    };
}

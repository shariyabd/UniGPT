/**
 * Webcam / screen media capture for a proctored exam.
 *
 * Two distinct jobs, deliberately decoupled:
 *   - CAMERA ACCESS: detection layers (face liveness, snapshots, phone
 *     detection) need the webcam stream without storing any video. The
 *     `camera` flag requests the stream; `getStream('webcam')` shares it.
 *   - RECORDING: the `webcam` / `screen` flags additionally create a
 *     MediaRecorder per stream and upload timeslice chunks.
 *
 * Permissions must be requested from a user gesture (the "Begin" click), so the
 * flow is: requestPermissions() → start() → stop(). Recording is chunked — each
 * MediaRecorder `timeslice` blob is uploaded on its own so a mid-exam disconnect
 * still preserves the earlier footage. All uploads are best-effort; a failed
 * chunk never interrupts the exam. Bitrate is capped server-side via config —
 * browser defaults (~1–2.5 Mbps) are untenable at university scale.
 *
 * @param {object}   opts
 * @param {number}   opts.testId
 * @param {object}   opts.recording           { webcam, screen, camera, chunkSeconds, mime, videoBitsPerSecond }
 * @param {function}[opts.onEnded]            called with the kind when a stream ends early
 */
export function useExamRecorder({ testId, recording, onEnded = () => {} }) {
    const recordWebcam = !!recording?.webcam;
    const recordScreen = !!recording?.screen;
    const wantCamera = !!recording?.camera || recordWebcam;
    const chunkMs = Math.max(5, Number(recording?.chunkSeconds) || 15) * 1000;
    const videoBitsPerSecond = Math.max(50_000, Number(recording?.videoBitsPerSecond) || 250_000);

    const streams = {}; // kind -> MediaStream
    const recorders = {}; // kind -> MediaRecorder
    const sequences = {}; // kind -> next chunk index

    const needsMedia = wantCamera || recordScreen;

    const pickMimeType = () => {
        const candidates = [
            'video/webm;codecs=vp9',
            'video/webm;codecs=vp8',
            'video/webm',
        ];
        if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) return 'video/webm';
        return candidates.find((type) => MediaRecorder.isTypeSupported(type)) || 'video/webm';
    };

    /**
     * Request whichever streams the test requires. Returns { ok, error }.
     * Must be called synchronously from a user gesture.
     */
    const requestPermissions = async () => {
        if (!needsMedia) return { ok: true };

        try {
            if (wantCamera) {
                streams.webcam = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }
            if (recordScreen) {
                streams.screen = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
            }
        } catch (e) {
            releaseStreams();
            return { ok: false, error: e?.name || 'PermissionDenied' };
        }

        // Detect the student stopping the share / unplugging the camera mid-exam.
        Object.entries(streams).forEach(([kind, stream]) => {
            stream.getVideoTracks().forEach((track) => {
                track.addEventListener('ended', () => onEnded(kind));
            });
        });

        return { ok: true };
    };

    const uploadChunk = async (kind, blob) => {
        if (!blob || blob.size === 0) return;
        const sequence = sequences[kind] ?? 0;
        sequences[kind] = sequence + 1;

        const form = new FormData();
        form.append('kind', kind);
        form.append('sequence', String(sequence));
        form.append('chunk', blob, `${kind}-${sequence}.webm`);

        try {
            await window.axios.post(route('class-tests.recording', testId), form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        } catch {
            // best-effort — drop this chunk, keep recording
        }
    };

    /** Begin recording every stream that is flagged for recording. */
    const start = () => {
        const mimeType = pickMimeType();
        const recordKinds = [
            ...(recordWebcam ? ['webcam'] : []),
            ...(recordScreen ? ['screen'] : []),
        ];

        recordKinds.forEach((kind) => {
            const stream = streams[kind];
            if (!stream) return;
            sequences[kind] = 0;
            let recorder;
            try {
                recorder = new MediaRecorder(stream, { mimeType, videoBitsPerSecond });
            } catch {
                recorder = new MediaRecorder(stream);
            }
            recorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) uploadChunk(kind, event.data);
            };
            recorder.start(chunkMs);
            recorders[kind] = recorder;
        });
    };

    const releaseStreams = () => {
        Object.values(streams).forEach((stream) => stream.getTracks().forEach((track) => track.stop()));
        Object.keys(streams).forEach((kind) => delete streams[kind]);
    };

    /** Stop recording and release the camera/screen. */
    const stop = () => {
        Object.values(recorders).forEach((recorder) => {
            try {
                if (recorder.state !== 'inactive') recorder.stop();
            } catch {
                // ignore
            }
        });
        Object.keys(recorders).forEach((kind) => delete recorders[kind]);
        releaseStreams();
    };

    /** Shared access to a granted stream (detection layers reuse the webcam). */
    const getStream = (kind) => streams[kind] ?? null;

    return { needsMedia, requestPermissions, start, stop, getStream };
}

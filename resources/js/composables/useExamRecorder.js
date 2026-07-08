/**
 * Webcam / screen media recording for a proctored exam.
 *
 * Permissions must be requested from a user gesture (the "Begin" click), so the
 * flow is: requestPermissions() → start() → stop(). Recording is chunked — each
 * MediaRecorder `timeslice` blob is uploaded on its own so a mid-exam disconnect
 * still preserves the earlier footage. All uploads are best-effort; a failed
 * chunk never interrupts the exam.
 *
 * @param {object}   opts
 * @param {number}   opts.testId
 * @param {object}   opts.recording           { webcam, screen, chunkSeconds, mime }
 * @param {function}[opts.onEnded]            called with the kind when a stream ends early
 */
export function useExamRecorder({ testId, recording, onEnded = () => {} }) {
    const wantWebcam = !!recording?.webcam;
    const wantScreen = !!recording?.screen;
    const chunkMs = Math.max(5, Number(recording?.chunkSeconds) || 15) * 1000;

    const streams = {}; // kind -> MediaStream
    const recorders = {}; // kind -> MediaRecorder
    const sequences = {}; // kind -> next chunk index

    const needsMedia = wantWebcam || wantScreen;

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
            if (wantWebcam) {
                streams.webcam = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }
            if (wantScreen) {
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

    /** Begin recording every granted stream. */
    const start = () => {
        const mimeType = pickMimeType();

        Object.entries(streams).forEach(([kind, stream]) => {
            sequences[kind] = 0;
            let recorder;
            try {
                recorder = new MediaRecorder(stream, { mimeType });
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

    return { needsMedia, requestPermissions, start, stop };
}

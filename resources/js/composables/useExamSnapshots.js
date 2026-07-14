/**
 * Snapshot evidence for a proctored exam (the `snapshot_evidence` layer).
 *
 * Replaces continuous video with moment-based photographic evidence: JPEG
 * bursts captured from the shared webcam stream when something is flagged
 * (violation, face loss, phone / second face, identity verification) plus
 * randomised periodic samples so capture moments can't be timed. A 20-minute
 * exam produces ~3–4 MB instead of ~150+ MB of recording.
 *
 * Uploads are best-effort (like recording chunks) and the client stops at the
 * per-attempt cap; the server enforces the same cap independently.
 *
 * @param {object} opts
 * @param {number} opts.testId
 * @param {object} opts.config   snapshots payload from clientConfig() —
 *                               { burstCount, burstIntervalMs, periodicMinSeconds,
 *                                 periodicMaxSeconds, maxWidth, jpegQuality, maxPerAttempt }
 */
export function useExamSnapshots({ testId, config }) {
    const burstCount = Math.max(1, Number(config?.burstCount) || 3);
    const burstIntervalMs = Math.max(100, Number(config?.burstIntervalMs) || 700);
    const periodicMinMs = Math.max(10, Number(config?.periodicMinSeconds) || 60) * 1000;
    const periodicMaxMs = Math.max(periodicMinMs / 1000, Number(config?.periodicMaxSeconds) || 90) * 1000;
    const maxWidth = Math.max(160, Number(config?.maxWidth) || 640);
    const jpegQuality = Math.min(1, Math.max(0.3, Number(config?.jpegQuality) || 0.7));
    const maxPerAttempt = Math.max(1, Number(config?.maxPerAttempt) || 60);

    let video = null;
    let canvas = null;
    let sequence = 0;
    let periodicTimer = null;
    let stopped = false;

    /** Attach the shared webcam stream and begin periodic sampling. */
    const start = async (stream) => {
        if (!stream) return;
        video = document.createElement('video');
        video.muted = true;
        video.playsInline = true;
        video.srcObject = stream;
        try {
            await video.play();
        } catch {
            video = null;
            return;
        }
        schedulePeriodic();
    };

    const schedulePeriodic = () => {
        if (stopped) return;
        const delay = periodicMinMs + Math.random() * (periodicMaxMs - periodicMinMs);
        periodicTimer = window.setTimeout(async () => {
            await captureFrame('periodic');
            schedulePeriodic();
        }, delay);
    };

    const captureFrame = async (trigger) => {
        if (stopped || !video || video.readyState < 2 || sequence >= maxPerAttempt) return;

        if (!canvas) canvas = document.createElement('canvas');
        const scale = Math.min(1, maxWidth / (video.videoWidth || maxWidth));
        canvas.width = Math.round((video.videoWidth || maxWidth) * scale);
        canvas.height = Math.round((video.videoHeight || maxWidth * 0.75) * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', jpegQuality));
        if (!blob) return;

        const seq = sequence;
        sequence += 1;

        const form = new FormData();
        form.append('trigger', trigger);
        form.append('sequence', String(seq));
        form.append('frame', blob, `${seq}-${trigger}.jpg`);

        try {
            const { data } = await window.axios.post(route('class-tests.snapshot', testId), form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (data?.capped) sequence = maxPerAttempt; // server said stop
        } catch {
            // best-effort — evidence capture never interrupts the exam
        }
    };

    /**
     * Capture a burst for a flagged moment. Fire-and-forget from event
     * handlers; frames upload independently.
     */
    const capture = (trigger) => {
        for (let i = 0; i < burstCount; i++) {
            window.setTimeout(() => captureFrame(trigger), i * burstIntervalMs);
        }
    };

    /** Stop sampling and release everything (submit / unmount). */
    const stopCapture = () => {
        stopped = true;
        if (periodicTimer) window.clearTimeout(periodicTimer);
        periodicTimer = null;
        if (video) {
            video.srcObject = null;
            video = null;
        }
        canvas = null;
    };

    return { start, capture, stopCapture };
}

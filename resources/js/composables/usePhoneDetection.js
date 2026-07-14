import { ref } from 'vue';
import { ObjectDetector, FilesetResolver } from '@mediapipe/tasks-vision';

/**
 * Phone detection for a proctored exam (the `phone_detection` layer).
 *
 * Runs MediaPipe ObjectDetector (EfficientDet-Lite0, self-hosted) client-side
 * on the shared webcam stream, watching for the COCO "cell phone" class. To
 * photograph the screen a student must raise the phone between their face and
 * the monitor — squarely in the webcam's field of view.
 *
 * Object detection has false positives (calculators, dark rectangles), so a
 * detection only fires after `consecutiveHits` positive frames and then cools
 * down. Confirmed detections are review signals with photo evidence — the
 * caller logs a warning event, never an automatic violation. No frames leave
 * the browser. Init failure degrades gracefully; the exam is never blocked.
 *
 * @param {object}   opts
 * @param {object}   opts.config      phone payload from clientConfig() —
 *                                    { scoreThreshold, consecutiveHits, cooldownSeconds,
 *                                      detectIntervalMs, wasmPath, modelPath }
 * @param {function} opts.onDetected  ({ score }) — confirmed phone in frame
 * @param {function} opts.onUnavailable  (reason) — model failed to initialise
 */
export function usePhoneDetection({ config, onDetected = () => {}, onUnavailable = () => {} }) {
    const scoreThreshold = Number(config?.scoreThreshold) || 0.5;
    const consecutiveHits = Math.max(1, Number(config?.consecutiveHits) || 3);
    const cooldownMs = Math.max(5, Number(config?.cooldownSeconds) || 30) * 1000;
    const intervalMs = Math.max(200, Number(config?.detectIntervalMs) || 500);

    const active = ref(false);

    let detector = null;
    let video = null;
    let rafId = null;
    let lastDetectAt = 0;
    let lastVideoTime = -1;
    let hits = 0;
    let cooledDownAt = 0;

    /** Initialise the detector on the shared webcam stream. Returns { ok }. */
    const start = async (stream) => {
        try {
            const fileset = await FilesetResolver.forVisionTasks(config.wasmPath);
            detector = await createDetector(fileset);

            video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.srcObject = stream;
            await video.play();

            active.value = true;
            loop();

            return { ok: true };
        } catch (e) {
            onUnavailable(e?.message || 'init_failed');
            return { ok: false };
        }
    };

    const createDetector = async (fileset) => {
        const options = (delegate) => ({
            baseOptions: { modelAssetPath: config.modelPath, delegate },
            runningMode: 'VIDEO',
            scoreThreshold,
            categoryAllowlist: ['cell phone'],
        });
        try {
            return await ObjectDetector.createFromOptions(fileset, options('GPU'));
        } catch {
            return await ObjectDetector.createFromOptions(fileset, options('CPU'));
        }
    };

    const loop = () => {
        if (!active.value) return;
        rafId = requestAnimationFrame(loop);

        const now = performance.now();
        if (now - lastDetectAt < intervalMs) return;
        lastDetectAt = now;

        if (!video || video.readyState < 2 || video.currentTime === lastVideoTime) return;
        lastVideoTime = video.currentTime;

        let result;
        try {
            result = detector.detectForVideo(video, now);
        } catch {
            return; // transient inference error — skip this frame
        }

        const detection = result?.detections?.find((d) =>
            d.categories?.some((c) => c.categoryName === 'cell phone' && c.score >= scoreThreshold)
        );

        if (!detection) {
            hits = 0;
            return;
        }

        if (now < cooledDownAt) return; // recently fired — ignore until cooldown ends

        hits += 1;
        if (hits >= consecutiveHits) {
            hits = 0;
            cooledDownAt = now + cooldownMs;
            const score = detection.categories.find((c) => c.categoryName === 'cell phone')?.score ?? 0;
            onDetected({ score: Math.round(score * 100) / 100 });
        }
    };

    /** Stop detection and release everything (submit / unmount). */
    const stopDetection = () => {
        active.value = false;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = null;
        try {
            detector?.close();
        } catch {
            // ignore
        }
        detector = null;
        if (video) {
            video.srcObject = null;
            video = null;
        }
    };

    return { active, start, stopDetection };
}

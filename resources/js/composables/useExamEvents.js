import { onBeforeUnmount } from 'vue';

/**
 * Buffered proctoring/behaviour event channel for a live class-test attempt.
 *
 * Events are queued client-side and flushed in batches (to keep the exam
 * responsive under heavy logging), except *violations* which are sent
 * immediately and awaited so the UI gets the disqualify verdict at once.
 *
 * Severities:
 *   info      — behaviour signal (answer, mouse, click, resize, idle …)
 *   warning   — blocked action that does not by itself disqualify (copy attempt)
 *   violation — counts toward the warning threshold (fullscreen exit, tab switch)
 *
 * @param {object}   opts
 * @param {number}   opts.testId
 * @param {number}  [opts.flushMs=8000]
 * @param {function}[opts.onDisqualified]  called with the server response when the threshold is crossed
 */
export function useExamEvents({ testId, flushMs = 8000, onDisqualified = () => {} }) {
    let buffer = [];
    let flushTimer = null;
    let lastActivity = Date.now();
    let idleReported = false;
    let sending = false;

    const push = (type, severity, meta, extra) => {
        buffer.push({ type, severity, occurredAt: Date.now(), meta: meta ?? null, ...(extra ?? {}) });
    };

    const post = async (events) => {
        if (!events.length) return null;
        try {
            const { data } = await window.axios.post(route('class-tests.events', testId), { events });
            if (data?.disqualified) onDisqualified(data);
            return data;
        } catch {
            return null;
        }
    };

    /** Send everything currently buffered. Safe to call repeatedly. */
    const flush = async () => {
        if (sending || buffer.length === 0) return null;
        sending = true;
        const batch = buffer;
        buffer = [];
        try {
            return await post(batch);
        } finally {
            sending = false;
        }
    };

    /** Record an informational behaviour event (batched). */
    const log = (type, meta = null, extra = null) => {
        lastActivity = Date.now();
        idleReported = false;
        push(type, 'info', meta, extra);
    };

    /** Record a blocked-but-not-disqualifying action (batched). */
    const warn = (type, meta = null, extra = null) => push(type, 'warning', meta, extra);

    /** Record a violation and flush immediately; resolves to the server verdict. */
    const report = async (type, meta = null, extra = null) => {
        push(type, 'violation', meta, extra);
        return flush();
    };

    /** Mark generic user activity (resets the idle window without logging). */
    const markActivity = () => {
        lastActivity = Date.now();
        idleReported = false;
    };

    /** Emit a single idle event once a no-activity span exceeds idleMs. */
    const checkIdle = (idleMs) => {
        if (idleReported) return;
        const span = Date.now() - lastActivity;
        if (span >= idleMs) {
            push('idle', 'info', null, { durationMs: span });
            idleReported = true;
        }
    };

    const start = () => {
        if (!flushTimer) flushTimer = window.setInterval(() => { flush(); }, flushMs);
    };

    const stop = () => {
        if (flushTimer) window.clearInterval(flushTimer);
        flushTimer = null;
    };

    onBeforeUnmount(stop);

    return { log, warn, report, flush, start, stop, markActivity, checkIdle };
}

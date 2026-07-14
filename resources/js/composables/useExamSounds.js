/**
 * Audible alerts for proctoring events on the exam screen.
 *
 * Sound matters most exactly when the screen doesn't: a student whose face
 * left the frame is not looking at the banner. Tones are synthesised with the
 * Web Audio API (no assets, works offline):
 *
 *   warning() — two short soft beeps (soft face-loss stage, phone flag, gate)
 *   danger()  — three descending harsher beeps (blocking overlay, violations)
 *
 * Browsers only allow audio after a user gesture — call unlock() from the
 * "Begin test" click; every later beep is then permitted. All methods are
 * safe no-ops when audio is unavailable or blocked.
 */
export function useExamSounds({ enabled = true } = {}) {
    let ctx = null;

    const ensureContext = () => {
        if (!enabled) return null;
        try {
            ctx ??= new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();
            return ctx;
        } catch {
            return null;
        }
    };

    /** Must be called from a user gesture (the Begin click) to permit audio. */
    const unlock = () => {
        ensureContext();
    };

    const tone = ({ frequency, startAt, duration, type = 'sine', volume = 0.12 }) => {
        const audio = ensureContext();
        if (!audio) return;
        try {
            const oscillator = audio.createOscillator();
            const gain = audio.createGain();
            const t = audio.currentTime + startAt;

            oscillator.type = type;
            oscillator.frequency.value = frequency;
            // Quick attack, exponential release — a beep, not a click.
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(volume, t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + duration);

            oscillator.connect(gain).connect(audio.destination);
            oscillator.start(t);
            oscillator.stop(t + duration + 0.05);
        } catch {
            // audio is best-effort — never interrupt the exam
        }
    };

    /** Two short soft beeps — attention, no penalty. */
    const warning = () => {
        tone({ frequency: 880, startAt: 0, duration: 0.15 });
        tone({ frequency: 880, startAt: 0.22, duration: 0.15 });
    };

    /** Three descending harsher beeps — blocking / counted incident. */
    const danger = () => {
        tone({ frequency: 660, startAt: 0, duration: 0.2, type: 'square', volume: 0.09 });
        tone({ frequency: 520, startAt: 0.26, duration: 0.2, type: 'square', volume: 0.09 });
        tone({ frequency: 392, startAt: 0.52, duration: 0.3, type: 'square', volume: 0.09 });
    };

    return { unlock, warning, danger };
}

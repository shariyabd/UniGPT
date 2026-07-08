/**
 * Collect a lightweight browser/device fingerprint for exam attribution.
 *
 * This is deliberately best-effort and privacy-modest: it gathers only the
 * ambient signals a browser already exposes (user agent, screen, timezone,
 * language, hardware hints, and stable canvas/WebGL hashes). The server hashes
 * the whole bundle into a single comparison key — two attempts sharing a hash
 * came from a very similar environment.
 */

const hashString = (input) => {
    // djb2 — small, stable, non-cryptographic; the server re-hashes for storage.
    let hash = 5381;
    for (let i = 0; i < input.length; i++) {
        hash = (hash * 33) ^ input.charCodeAt(i);
    }
    return (hash >>> 0).toString(16);
};

const canvasFingerprint = () => {
    try {
        const canvas = document.createElement('canvas');
        canvas.width = 240;
        canvas.height = 60;
        const ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.textBaseline = 'top';
        ctx.font = "16px 'Arial'";
        ctx.fillStyle = '#f60';
        ctx.fillRect(10, 10, 100, 30);
        ctx.fillStyle = '#069';
        ctx.fillText('UniGPT-proctor', 12, 18);
        ctx.strokeStyle = 'rgba(0,120,200,0.6)';
        ctx.arc(60, 30, 20, 0, Math.PI * 2);
        ctx.stroke();
        return hashString(canvas.toDataURL());
    } catch {
        return null;
    }
};

const webglInfo = () => {
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return null;
        const debug = gl.getExtension('WEBGL_debug_renderer_info');
        const vendor = debug ? gl.getParameter(debug.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR);
        const renderer = debug ? gl.getParameter(debug.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);
        return hashString(`${vendor}~${renderer}`);
    } catch {
        return null;
    }
};

export function collectFingerprint() {
    const nav = window.navigator;
    const scr = window.screen;

    return {
        userAgent: nav.userAgent,
        language: nav.language,
        languages: Array.isArray(nav.languages) ? nav.languages.join(',') : null,
        platform: nav.platform,
        hardwareConcurrency: nav.hardwareConcurrency ?? null,
        deviceMemory: nav.deviceMemory ?? null,
        maxTouchPoints: nav.maxTouchPoints ?? 0,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
        timezoneOffset: new Date().getTimezoneOffset(),
        screen: `${scr.width}x${scr.height}x${scr.colorDepth}`,
        pixelRatio: window.devicePixelRatio ?? 1,
        canvas: canvasFingerprint(),
        webgl: webglInfo(),
    };
}

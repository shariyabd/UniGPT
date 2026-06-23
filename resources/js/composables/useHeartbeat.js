import { onMounted, onUnmounted } from 'vue';
import axios from 'axios';

/**
 * Presence heartbeat. Pings the server while the user has the app open (any
 * route) so their "active" status stays fresh. Pings on mount, on an interval,
 * and whenever the tab becomes visible again — but never while hidden, so a
 * backgrounded tab naturally lapses to "offline" after the server's window.
 *
 * Interval is well under User::ACTIVE_WINDOW_SECONDS (120s) so an open tab never
 * flickers offline between pings.
 */
export function useHeartbeat({ intervalMs = 60000 } = {}) {
    let timer = null;

    const ping = () => {
        if (typeof document !== 'undefined' && document.hidden) {
            return;
        }
        axios.post(route('heartbeat')).catch(() => {});
    };

    const onVisibility = () => {
        if (!document.hidden) {
            ping();
        }
    };

    onMounted(() => {
        ping();
        timer = window.setInterval(ping, intervalMs);
        document.addEventListener('visibilitychange', onVisibility);
    });

    onUnmounted(() => {
        if (timer) {
            window.clearInterval(timer);
        }
        document.removeEventListener('visibilitychange', onVisibility);
    });
}

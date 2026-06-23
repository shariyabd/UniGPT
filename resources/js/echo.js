import Echo from '@ably/laravel-echo';
import * as Ably from 'ably';

/**
 * Realtime client — Ably (ably-js), paired with the server-side
 * `ably/laravel-broadcaster`. This is the matched pair: both ends agree on
 * channel naming and use Ably token auth, fixing the channel-name mismatch that
 * a Pusher-protocol client had against the native Ably broadcaster.
 *
 * The browser never receives an Ably key — Echo fetches a short-lived token from
 * Laravel's session-guarded /broadcasting/auth (CSRF via the <meta name="csrf-token">
 * tag). Echo only initialises when realtime is switched on; otherwise
 * `window.Echo` stays undefined and the messenger relies on its polling fallback.
 */
window.Ably = Ably;

if (import.meta.env.VITE_BROADCAST_CONNECTION === 'ably') {
    window.Echo = new Echo({
        broadcaster: 'ably',
        // authEndpoint defaults to /broadcasting/auth; the CSRF token is read
        // from the meta tag automatically.
    });
}

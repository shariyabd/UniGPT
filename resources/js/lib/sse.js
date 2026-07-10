/**
 * POST a JSON payload and consume the server-sent-events response.
 *
 * Used by the AI chat surfaces to stream assistant text: the backend emits
 * `delta` events while generating, then a single `done` event with the same
 * payload the non-streaming endpoint returns (or an `error` event).
 *
 * Calls handlers.onEvent(eventName, data) for every event. Throws on transport
 * or HTTP errors — including before the stream starts (e.g. a 403 from the
 * AI-access middleware), in which case the thrown error carries `status` and
 * the parsed JSON `body` so callers can show the server's message.
 */
export async function postEventStream(url, payload, { onEvent, signal } = {}) {
    const xsrf = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)?.[1];

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        signal,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'text/event-stream',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}),
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const error = new Error(`Stream request failed (${response.status})`);
        error.status = response.status;
        try {
            error.body = await response.json();
        } catch {
            error.body = null;
        }
        throw error;
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    const dispatch = (block) => {
        let event = 'message';
        const dataLines = [];
        for (const line of block.split('\n')) {
            if (line.startsWith('event:')) event = line.slice(6).trim();
            else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
        }
        if (dataLines.length === 0) return;
        let data = null;
        try {
            data = JSON.parse(dataLines.join('\n'));
        } catch {
            return;
        }
        onEvent?.(event, data);
    };

    // SSE frames are separated by a blank line; dispatch each complete frame
    // and keep the trailing partial in the buffer.
    for (;;) {
        const { done, value } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });

        let boundary;
        while ((boundary = buffer.indexOf('\n\n')) !== -1) {
            dispatch(buffer.slice(0, boundary));
            buffer = buffer.slice(boundary + 2);
        }
    }

    if (buffer.trim() !== '') dispatch(buffer);
}

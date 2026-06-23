import { ref, computed } from 'vue';
import axios from 'axios';

/**
 * Drives a single open direct-message conversation: resolves/creates it for a
 * contact, holds the message list, sends new messages, and keeps the thread
 * fresh.
 *
 * The database is the source of truth. New messages arrive two ways:
 *   1. Realtime — the messenger page subscribes to the conversation's private
 *      channel and calls {@link receive} when an event lands (see Phase 3).
 *   2. Polling — a low-frequency fallback (every `pollMs`) so messages still
 *      appear if the websocket is unavailable or the connection cap is hit.
 *
 * Both paths funnel through {@link receive}, which de-dupes by id, so a message
 * delivered by both realtime and the poll is only ever shown once.
 */
export function useConversation({ pollMs = 4000 } = {}) {
    const conversationId = ref(null);
    const messages = ref([]);
    const loading = ref(false);
    const sending = ref(false);
    const otherTyping = ref(false);

    let pollTimer = null;
    let channelName = null;
    let channel = null;
    let typingClearTimer = null;
    let whisperCoolingDown = false;

    const lastId = computed(() =>
        messages.value.length ? messages.value[messages.value.length - 1].id : 0,
    );

    /** Append a message unless we already have it (id de-dupe). */
    function receive(message) {
        if (!message || message.conversation_id !== conversationId.value) {
            return;
        }
        if (!messages.value.some((existing) => existing.id === message.id)) {
            messages.value.push(message);
        }
    }

    /** Subscribe to the conversation's private channel. Returns true if realtime
     *  is available, false when no Echo client is configured (poll-only mode). */
    function subscribe() {
        if (!window.Echo || !conversationId.value) {
            return false;
        }
        channelName = `conversation.${conversationId.value}`;
        channel = window.Echo.private(channelName);
        channel.listen('.message.sent', (event) => receive(event));
        // Typing indicator — peer-to-peer client event (no server/DB hit).
        channel.listenForWhisper('typing', () => {
            otherTyping.value = true;
            if (typingClearTimer) {
                window.clearTimeout(typingClearTimer);
            }
            typingClearTimer = window.setTimeout(() => { otherTyping.value = false; }, 3000);
        });
        return true;
    }

    function unsubscribe() {
        if (channelName && window.Echo) {
            window.Echo.leave(channelName);
        }
        channelName = null;
        channel = null;
    }

    /** Tell the other participant we're typing (throttled; best-effort whisper). */
    function notifyTyping() {
        if (!channel || whisperCoolingDown) {
            return;
        }
        channel.whisper('typing', {});
        whisperCoolingDown = true;
        window.setTimeout(() => { whisperCoolingDown = false; }, 1500);
    }

    /** Open (resolve-or-create) the conversation with a contact (target user id). */
    async function open(contactUserId) {
        close();
        loading.value = true;
        try {
            const { data } = await axios.post(route('messenger.conversations.resolve'), {
                with: contactUserId,
            });
            conversationId.value = data.conversation_id;
            messages.value = data.messages;
            // Realtime is primary; the poll becomes a slow safety net (covers a
            // dropped socket or the provider's connection cap). Poll-only when
            // there's no Echo client.
            const realtime = subscribe();
            startPolling(realtime ? 15000 : pollMs);
        } finally {
            loading.value = false;
        }
    }

    /** Persist and optimistically render an outgoing message. */
    async function send(body) {
        const text = (body ?? '').trim();
        if (!text || !conversationId.value || sending.value) {
            return false;
        }
        sending.value = true;
        try {
            // X-Socket-ID lets the server's broadcast()->toOthers() skip echoing
            // back to us — we render our own message optimistically below.
            const headers = window.Echo ? { 'X-Socket-ID': window.Echo.socketId() } : {};
            const { data } = await axios.post(
                route('messenger.messages.store', conversationId.value),
                { body: text },
                { headers },
            );
            receive(data.message);
            return true;
        } finally {
            sending.value = false;
        }
    }

    /** Fetch anything newer than the last message we hold. */
    async function poll() {
        if (!conversationId.value) {
            return;
        }
        const { data } = await axios.get(
            route('messenger.messages.index', conversationId.value),
            { params: { after: lastId.value } },
        );
        data.messages.forEach(receive);
    }

    function startPolling(intervalMs = pollMs) {
        stopPolling();
        pollTimer = window.setInterval(() => poll().catch(() => {}), intervalMs);
    }

    function stopPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function close() {
        stopPolling();
        unsubscribe();
        if (typingClearTimer) {
            window.clearTimeout(typingClearTimer);
            typingClearTimer = null;
        }
        otherTyping.value = false;
        conversationId.value = null;
        messages.value = [];
    }

    return {
        conversationId,
        messages,
        loading,
        sending,
        otherTyping,
        notifyTyping,
        open,
        send,
        poll,
        receive,
        close,
        startPolling,
        stopPolling,
    };
}

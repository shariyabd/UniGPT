import { reactive } from 'vue';
import axios from 'axios';

/**
 * Keeps the contact LIST fresh without a per-user socket (cap-safe): on a slow
 * poll it fetches presence (who's active) + per-conversation last-message
 * preview and unread count for the current user. The open conversation updates
 * instantly via Ably; this only covers the closed ones.
 *
 * `meta` is keyed by the other participant's user id, so the messenger page can
 * merge it onto its directory-derived contacts by id.
 */
export function useMessengerOverview({ pollMs = 10000 } = {}) {
    // userId -> { online, lastBody, lastAt, lastSenderId, unread }
    const meta = reactive({});
    let timer = null;
    let ids = [];

    function entry(userId) {
        if (!meta[userId]) {
            meta[userId] = { online: false, lastBody: null, lastAt: null, lastSenderId: null, unread: 0 };
        }
        return meta[userId];
    }

    async function refresh() {
        if (!ids.length) {
            return;
        }
        const { data } = await axios.get(route('messenger.overview'), {
            params: { ids: ids.join(',') },
        });
        Object.entries(data.presence ?? {}).forEach(([id, online]) => {
            entry(Number(id)).online = Boolean(online);
        });
        (data.conversations ?? []).forEach((conversation) => {
            const item = entry(conversation.user_id);
            item.lastBody = conversation.last_body;
            item.lastAt = conversation.last_at;
            item.lastSenderId = conversation.last_sender_id;
            item.unread = conversation.unread;
        });
    }

    /** Update the contact set to fetch presence for (e.g. on search filter). */
    function setIds(contactIds) {
        ids = contactIds;
        refresh().catch(() => {});
    }

    function start() {
        stop();
        refresh().catch(() => {});
        timer = window.setInterval(() => refresh().catch(() => {}), pollMs);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    /** Optimistic list update for a message in the open conversation. */
    function applyLocalMessage(userId, body, senderId, fromMe) {
        const item = entry(userId);
        item.lastBody = body;
        item.lastAt = new Date().toISOString();
        item.lastSenderId = senderId;
        if (!fromMe) {
            item.unread = (item.unread ?? 0) + 1;
        }
    }

    function clearUnread(userId) {
        entry(userId).unread = 0;
    }

    return { meta, start, stop, setIds, refresh, applyLocalMessage, clearUnread };
}

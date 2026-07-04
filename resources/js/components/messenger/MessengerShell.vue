<script setup>
/**
 * Messenger-style two-pane shell (Meta Messenger pattern).
 *
 * The parent owns all data + filtering and passes the already-filtered,
 * normalised `contacts`; this component owns the layout, the selection state
 * and the live chat thread. Selecting a contact resolves (or creates) the 1:1
 * conversation and streams its messages via {@link useConversation} — realtime
 * with a polling fallback, DB as the source of truth.
 *
 * Normalised contact shape:
 *   { id, name, avatar, subtitle, tag?, tagVariant?, status?, meta?: [{label, value}] }
 *   `id` is the target user's id (the person to message).
 */
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useConversation } from '@/composables/useConversation';
import { useMessengerOverview } from '@/composables/useMessengerOverview';
import Badge from '@/components/ui/Badge.vue';
import {
    ChatBubbleLeftRightIcon,
    ArrowLeftIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    contacts: { type: Array, default: () => [] },
    // Empty-state prompt shown in the chat pane before a contact is picked.
    selectPrompt: { type: String, default: 'Select a conversation to start chatting' },
    emptyListText: { type: String, default: 'No matches found.' },
    // Optional contact id to open as the active conversation on load (used when
    // deep-linking into the chat from a directory "Message" button).
    initialSelectedId: { type: [Number, String, null], default: null },
});

const selectedId = ref(null);

// Open the deep-linked conversation once, if that contact exists in the list.
onMounted(() => {
    if (
        props.initialSelectedId !== null &&
        props.contacts.some((contact) => contact.id === props.initialSelectedId)
    ) {
        selectedId.value = props.initialSelectedId;
    }
});

const selected = computed(
    () => props.contacts.find((contact) => contact.id === selectedId.value) ?? null,
);

// If active filters remove the currently-selected contact, drop the selection
// so the chat pane never points at someone no longer in the list.
watch(
    () => props.contacts,
    (list) => {
        if (selectedId.value !== null && !list.some((contact) => contact.id === selectedId.value)) {
            selectedId.value = null;
        }
    },
);

const select = (contact) => {
    selectedId.value = contact.id;
};

const clearSelection = () => {
    selectedId.value = null;
};

// --- Live conversation ------------------------------------------------------
const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const {
    conversationId, messages, loading, sending, otherTyping, notifyTyping, open, send, close,
} = useConversation();

const overview = useMessengerOverview();

const draft = ref('');
const threadEl = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (threadEl.value) {
            threadEl.value.scrollTop = threadEl.value.scrollHeight;
        }
    });
};

const markRead = (id) => {
    if (id) {
        axios.post(route('messenger.messages.read', id)).catch(() => {});
    }
};

// Contacts decorated with live list state (presence, last-message preview,
// unread), then ordered Messenger-style: active conversations newest-first, the
// rest keep their incoming (name-sorted) order.
const displayContacts = computed(() =>
    props.contacts
        .map((contact) => {
            const live = overview.meta[contact.id];
            return {
                ...contact,
                status: live?.online ? 'online' : undefined,
                lastActive: live?.lastActive ?? null,
                preview: live?.lastBody ?? null,
                previewMine: live?.lastSenderId != null && Number(live.lastSenderId) === Number(currentUserId.value),
                lastAt: live?.lastAt ?? null,
                unread: live?.unread ?? 0,
            };
        })
        .sort((a, b) => {
            if (a.lastAt && b.lastAt) {
                return new Date(b.lastAt) - new Date(a.lastAt);
            }
            return a.lastAt ? -1 : b.lastAt ? 1 : 0;
        }),
);

const contactIds = computed(() => props.contacts.map((contact) => contact.id));

// Live presence for the open contact (header dot + "Active now").
const selectedOnline = computed(
    () => selectedId.value !== null && overview.meta[selectedId.value]?.online === true,
);

// Messenger-style "last active" label from a last-seen timestamp.
const formatLastActive = (iso) => {
    if (!iso) {
        return 'Offline';
    }
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 60) {
        return 'Active now';
    }
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `Active ${minutes}m ago`;
    }
    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `Active ${hours}h ago`;
    }
    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `Active ${days}d ago`;
    }
    return `Active ${new Date(iso).toLocaleDateString()}`;
};

// Presence label for the open conversation header. Empty until we actually have
// presence data, so the header can fall back to the contact subtitle.
const selectedPresenceLabel = computed(() => {
    if (selectedId.value === null) {
        return '';
    }
    const live = overview.meta[selectedId.value];
    if (live?.online) {
        return 'Active now';
    }
    return live?.lastActive ? formatLastActive(live.lastActive) : '';
});

// Open/close the conversation as the selection changes. Covers every selection
// path: clicking a contact, the deep-linked onMounted selection, and the
// filter-driven auto-deselect above.
watch(selectedId, async (id) => {
    if (id === null) {
        close();
        return;
    }
    const contact = props.contacts.find((item) => item.id === id);
    if (!contact) {
        return;
    }
    await open(contact.id);
    scrollToBottom();
    overview.clearUnread(contact.id); // viewing it → no unread
    markRead(conversationId.value);
});

// On every new message in the OPEN conversation: pin to bottom, bump the contact
// to the top of the list with the new preview, and keep it marked read.
watch(() => messages.value.length, () => {
    scrollToBottom();
    const last = messages.value[messages.value.length - 1];
    if (!last || selectedId.value === null) {
        return;
    }
    const fromMe = Number(last.sender_id) === Number(currentUserId.value);
    overview.applyLocalMessage(selectedId.value, last.body, last.sender_id, fromMe);
    overview.clearUnread(selectedId.value);
    if (!fromMe) {
        markRead(conversationId.value);
    }
});

// Keep the polled presence/unread set in sync with the (filtered) contact list.
watch(contactIds, (ids) => overview.setIds(ids));

const composerInput = ref(null);

// Grow the composer with its content (like modern chat apps) up to a max height.
const autoResize = () => {
    notifyTyping();
    const el = composerInput.value;
    if (!el) {
        return;
    }
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, 120)}px`;
};

const resetComposerHeight = () => {
    if (composerInput.value) {
        composerInput.value.style.height = 'auto';
    }
};

const submit = async () => {
    // Ignore blank/whitespace-only drafts (Enter on an empty composer).
    if (!draft.value.trim()) {
        return;
    }
    if (await send(draft.value)) {
        draft.value = '';
        resetComposerHeight();
        scrollToBottom();
    }
};

// Coerce both sides: some hosts return the foreign key as a string ("2"), which
// would fail a strict === against the numeric auth id and push every message to
// one side. Number() makes the check driver-independent.
const isMine = (message) => Number(message.sender_id) === Number(currentUserId.value);

const formatTime = (iso) => {
    if (!iso) {
        return '';
    }
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    overview.setIds(contactIds.value);
    overview.start();
});

onUnmounted(() => {
    close();
    overview.stop();
});
</script>

<template>
    <div class="ui-card flex h-[calc(100dvh-15rem)] min-h-[540px] overflow-hidden">
        <!-- Contact list pane -->
        <div
            class="flex w-full flex-col border-line lg:w-[340px] lg:flex-shrink-0 lg:border-r"
            :class="selected ? 'hidden lg:flex' : 'flex'"
        >
            <div class="flex-shrink-0 space-y-3 border-b border-line p-4">
                <slot name="toolbar" />
            </div>

            <div class="flex-1 overflow-y-auto">
                <button
                    v-for="contact in displayContacts"
                    :key="contact.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-primary-soft/60"
                    :class="selectedId === contact.id ? 'bg-primary-soft' : ''"
                    @click="select(contact)"
                >
                    <div class="relative flex-shrink-0">
                        <img :src="contact.avatar" :alt="contact.name" class="h-11 w-11 rounded-full object-cover" />
                        <span
                            v-if="contact.status === 'online'"
                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-surface bg-success-fg"
                            title="Active now"
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-content">{{ contact.name }}</p>
                        <!-- Secondary line: last-message preview if a conversation
                             exists, else a Messenger-style presence/last-active
                             label, else the directory subtitle. -->
                        <p
                            class="truncate text-xs"
                            :class="contact.unread ? 'font-semibold text-content' : 'text-content-muted'"
                        >
                            <template v-if="contact.preview">
                                <span v-if="contact.previewMine" class="text-content-faint">You: </span>{{ contact.preview }}
                            </template>
                            <template v-else-if="contact.status === 'online'">
                                <span class="text-success-fg">Active now</span>
                            </template>
                            <template v-else-if="contact.lastActive">{{ formatLastActive(contact.lastActive) }}</template>
                            <template v-else>{{ contact.subtitle }}</template>
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <span
                            v-if="contact.unread"
                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-bold text-white"
                        >{{ contact.unread > 99 ? '99+' : contact.unread }}</span>
                        <Badge v-else-if="contact.tag" :variant="contact.tagVariant || 'primary'">{{ contact.tag }}</Badge>
                        <ChatBubbleLeftRightIcon v-else class="h-5 w-5 text-content-faint" />
                    </div>
                </button>

                <div v-if="!displayContacts.length" class="px-6 py-12 text-center text-sm text-content-muted">
                    {{ emptyListText }}
                </div>
            </div>
        </div>

        <!-- Chat pane -->
        <div class="flex flex-1 flex-col" :class="selected ? 'flex' : 'hidden lg:flex'">
            <template v-if="selected">
                <!-- Header -->
                <div class="flex flex-shrink-0 items-center gap-3 border-b border-line px-4 py-3">
                    <button
                        type="button"
                        class="rounded-control p-1.5 text-content-muted hover:bg-primary-soft lg:hidden"
                        aria-label="Back to list"
                        @click="clearSelection"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </button>
                    <div class="relative flex-shrink-0">
                        <img :src="selected.avatar" :alt="selected.name" class="h-10 w-10 rounded-full object-cover" />
                        <span
                            v-if="selectedOnline"
                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-surface bg-success-fg"
                            title="Active now"
                        ></span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-content">{{ selected.name }}</p>
                        <p
                            class="truncate text-xs"
                            :class="otherTyping ? 'text-primary' : 'text-content-muted'"
                        >
                            <template v-if="otherTyping">typing…</template>
                            <template v-else-if="selectedPresenceLabel">{{ selectedPresenceLabel }}</template>
                            <template v-else>{{ selected.subtitle }}</template>
                        </p>
                    </div>
                </div>

                <!-- Message thread -->
                <div ref="threadEl" class="flex flex-1 flex-col gap-2 overflow-y-auto bg-bg/40 p-4">
                    <div v-if="loading" class="flex flex-1 items-center justify-center text-sm text-content-muted">
                        Loading conversation…
                    </div>

                    <div
                        v-else-if="!messages.length"
                        class="flex flex-1 flex-col items-center justify-center gap-2 text-center text-sm text-content-muted"
                    >
                        <ChatBubbleLeftRightIcon class="h-8 w-8 text-content-faint" />
                        <p>No messages yet. Say hello to {{ selected.name }}.</p>
                    </div>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="isMine(message) ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[78%] rounded-2xl px-3.5 py-2 text-sm shadow-card"
                            :class="isMine(message)
                                ? 'rounded-br-sm bg-primary text-white'
                                : 'rounded-bl-sm bg-surface text-content'"
                        >
                            <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
                            <p
                                class="mt-1 text-right text-[10px]"
                                :class="isMine(message) ? 'text-white/70' : 'text-content-faint'"
                            >
                                {{ formatTime(message.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Composer -->
                <form class="flex-shrink-0 border-t border-line p-3 sm:p-4" @submit.prevent="submit">
                    <div class="flex items-end gap-2 rounded-2xl border border-line bg-surface px-3 py-2 focus-within:border-primary">
                        <textarea
                            ref="composerInput"
                            v-model="draft"
                            rows="1"
                            :placeholder="`Message ${selected.name}…`"
                            class="flex-1 resize-none border-0 bg-transparent py-1 text-sm text-content placeholder:text-content-faint focus:outline-none focus:ring-0"
                            @input="autoResize"
                            @keydown.enter.exact.prevent="submit"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="!draft.trim() || sending"
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-primary text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                            aria-label="Send message"
                        >
                            <PaperAirplaneIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <p class="mt-1.5 px-1 text-[10px] text-content-faint">
                        <kbd class="rounded bg-neutral-bg px-1 py-0.5">Enter</kbd> to send ·
                        <kbd class="rounded bg-neutral-bg px-1 py-0.5">Shift+Enter</kbd> for a new line
                    </p>
                </form>
            </template>

            <!-- Empty state (no contact selected) -->
            <div v-else class="hidden flex-1 flex-col items-center justify-center gap-3 p-8 text-center lg:flex">
                <span class="ui-icon-tile h-14 w-14 bg-primary-soft text-primary">
                    <ChatBubbleLeftRightIcon class="h-7 w-7" />
                </span>
                <p class="max-w-xs text-sm text-content-muted">{{ selectPrompt }}</p>
            </div>
        </div>
    </div>
</template>

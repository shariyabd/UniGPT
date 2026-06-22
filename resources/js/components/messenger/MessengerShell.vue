<script setup>
/**
 * Messenger-style two-pane shell (Meta Messenger pattern).
 *
 * UI/UX skeleton only — the chat thread is intentionally a non-functional
 * "Upcoming Feature" placeholder. The parent owns all data + filtering and
 * passes the already-filtered, normalised `contacts`; this component owns only
 * the layout, the selection state and the placeholder chat pane.
 *
 * Normalised contact shape:
 *   { id, name, avatar, subtitle, tag?, tagVariant?, status?, meta?: [{label, value}] }
 */
import { ref, computed, watch, onMounted } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import {
    ChatBubbleLeftRightIcon,
    ArrowLeftIcon,
    SparklesIcon,
    PaperAirplaneIcon,
    PaperClipIcon,
    FaceSmileIcon,
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

const statusColor = {
    online: 'bg-success-fg',
    away: 'bg-warning-fg',
    offline: 'bg-neutral-fg',
};

const select = (contact) => {
    selectedId.value = contact.id;
};

const clearSelection = () => {
    selectedId.value = null;
};
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
                    v-for="contact in contacts"
                    :key="contact.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-primary-soft/60"
                    :class="selectedId === contact.id ? 'bg-primary-soft' : ''"
                    @click="select(contact)"
                >
                    <div class="relative flex-shrink-0">
                        <img :src="contact.avatar" :alt="contact.name" class="h-11 w-11 rounded-full object-cover" />
                        <span
                            v-if="contact.status"
                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-surface"
                            :class="statusColor[contact.status] ?? statusColor.offline"
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-content">{{ contact.name }}</p>
                        <p class="truncate text-xs text-content-muted">{{ contact.subtitle }}</p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <Badge v-if="contact.tag" :variant="contact.tagVariant || 'primary'">{{ contact.tag }}</Badge>
                        <ChatBubbleLeftRightIcon class="h-5 w-5 text-content-faint" />
                    </div>
                </button>

                <div v-if="!contacts.length" class="px-6 py-12 text-center text-sm text-content-muted">
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
                            v-if="selected.status"
                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-surface"
                            :class="statusColor[selected.status] ?? statusColor.offline"
                        ></span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-content">{{ selected.name }}</p>
                        <p class="truncate text-xs text-content-muted">{{ selected.subtitle }}</p>
                    </div>
                </div>

                <!-- Body: "Upcoming Feature" placeholder -->
                <div class="flex flex-1 flex-col items-center justify-center gap-4 overflow-y-auto bg-bg/40 p-8 text-center">
                    <span class="ui-icon-tile h-14 w-14 bg-primary text-white">
                        <SparklesIcon class="h-7 w-7" />
                    </span>
                    <Badge variant="warning" :dot="true">Upcoming Feature</Badge>
                    <div class="max-w-sm space-y-1.5">
                        <h3 class="text-lg font-semibold text-content">Direct messaging is coming soon</h3>
                        <p class="text-sm text-content-muted">
                            You'll soon be able to chat with <span class="font-medium text-content">{{ selected.name }}</span>
                            in real time. We're still building this — hang tight!
                        </p>
                    </div>

                    <!-- Optional contact details for context -->
                    <dl
                        v-if="selected.meta && selected.meta.length"
                        class="mt-2 grid w-full max-w-sm grid-cols-2 gap-3 text-left"
                    >
                        <div v-for="item in selected.meta" :key="item.label" class="rounded-control bg-surface p-3 shadow-card">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-content-faint">{{ item.label }}</dt>
                            <dd class="mt-0.5 truncate text-sm font-medium text-content">{{ item.value }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Disabled composer -->
                <div class="flex-shrink-0 border-t border-line p-3 sm:p-4">
                    <div class="flex items-center gap-2 rounded-pill border border-line bg-neutral-bg px-3 py-2 opacity-70">
                        <PaperClipIcon class="h-5 w-5 flex-shrink-0 text-content-faint" />
                        <input
                            type="text"
                            disabled
                            placeholder="Messaging will be available soon…"
                            class="flex-1 cursor-not-allowed border-0 bg-transparent text-sm text-content placeholder:text-content-faint focus:outline-none focus:ring-0"
                        />
                        <FaceSmileIcon class="h-5 w-5 flex-shrink-0 text-content-faint" />
                        <button
                            type="button"
                            disabled
                            class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-full bg-primary/40 text-white"
                            aria-label="Send message (coming soon)"
                        >
                            <PaperAirplaneIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
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

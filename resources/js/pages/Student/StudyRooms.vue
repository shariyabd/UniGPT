<script setup>
import { ref, computed, nextTick, onBeforeUnmount, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConversation } from '@/composables/useConversation';
import { useConfirm } from '@/composables/useConfirm';
import {
    UserGroupIcon,
    PlusIcon,
    PaperAirplaneIcon,
    ArrowRightOnRectangleIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
});

const toast = useToast();
const { confirm } = useConfirm();
const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

// --- Room list / create ------------------------------------------------------
const createForm = useForm({ title: '', section_id: null });

const createRoom = () => {
    createForm.post(route('study-rooms.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const joinRoom = (room) => {
    router.post(route('study-rooms.join', room.id), {}, { preserveScroll: true });
};

const leaveRoom = async (room) => {
    const ok = await confirm({
        title: 'Leave study room',
        message: `Leave "${room.title}"? If you are the last member, the room and its messages are deleted.`,
        confirmLabel: 'Leave',
        variant: 'danger',
    });
    if (!ok) return;
    if (activeRoom.value?.id === room.id) {
        closeRoom();
    }
    router.post(route('study-rooms.leave', room.id), {}, { preserveScroll: true });
};

// --- Open room chat (shared messenger plumbing) ------------------------------
const conversation = useConversation();
const activeRoom = ref(null);
const memberNames = ref({});
const draft = ref('');
const thread = ref(null);

const scrollThread = () => {
    nextTick(() => {
        if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
    });
};

const openRoom = async (room) => {
    if (!room.isMember) return;
    activeRoom.value = room;
    memberNames.value = {};
    try {
        const [, membersResponse] = await Promise.all([
            conversation.openById(room.id),
            axios.get(route('study-rooms.members', room.id)),
        ]);
        memberNames.value = Object.fromEntries(
            (membersResponse.data.members ?? []).map((m) => [m.id, m.name]),
        );
    } catch {
        toast.error('Could not open the study room.');
        activeRoom.value = null;
        return;
    }
    scrollThread();
};

const closeRoom = () => {
    conversation.close();
    activeRoom.value = null;
    draft.value = '';
};

const sendDraft = async () => {
    const text = draft.value.trim();
    if (!text) return;
    draft.value = '';
    const ok = await conversation.send(text);
    if (!ok) toast.error('Message could not be sent.');
    scrollThread();
};

const senderName = (message) =>
    Number(message.sender_id) === Number(currentUserId.value)
        ? 'You'
        : (memberNames.value[message.sender_id] ?? 'Classmate');

const formatTime = (iso) => new Date(iso).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

// Keep the thread pinned to the newest message as realtime/poll messages land.
watch(() => conversation.messages.value.length, scrollThread);

onBeforeUnmount(() => conversation.close());
</script>

<template>
    <div>
        <Head title="Study Rooms" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Study Rooms"
                    subtitle="Group chats with your classmates, one room per topic, scoped to your section."
                    :icon="UserGroupIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: create + room list -->
                    <div class="space-y-6">
                        <Card>
                            <template #header>
                                <div class="flex items-center gap-2 font-semibold text-content">
                                    <PlusIcon class="w-5 h-5 text-primary" />
                                    New room
                                </div>
                            </template>
                            <form @submit.prevent="createRoom" class="space-y-3">
                                <input
                                    v-model="createForm.title"
                                    type="text"
                                    required
                                    maxlength="80"
                                    placeholder="e.g. Midterm prep squad"
                                    class="ui-input w-full"
                                    aria-label="Room name"
                                />
                                <select v-model="createForm.section_id" required class="ui-input w-full" aria-label="Section">
                                    <option :value="null" disabled>Pick a section…</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">
                                        {{ section.label }}
                                    </option>
                                </select>
                                <button type="submit" class="ui-btn-primary w-full" :disabled="createForm.processing || !createForm.title.trim() || !createForm.section_id">
                                    Create room
                                </button>
                            </form>
                        </Card>

                        <EmptyState
                            v-if="rooms.length === 0"
                            :icon="UserGroupIcon"
                            title="No study rooms yet"
                            description="Create the first room for one of your sections and invite classmates by telling them to join."
                        />

                        <Card v-for="room in rooms" :key="room.id" :class="activeRoom?.id === room.id ? 'ring-2 ring-primary' : ''">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-content truncate">{{ room.title }}</p>
                                    <p class="text-xs text-content-muted mt-0.5">{{ room.section }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <Badge variant="neutral">{{ room.members }} member{{ room.members === 1 ? '' : 's' }}</Badge>
                                        <span v-if="room.lastMessageAt" class="text-xs text-content-faint">Active {{ room.lastMessageAt }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                    <button v-if="room.isMember" @click="openRoom(room)" class="ui-btn-primary">
                                        <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        Open
                                    </button>
                                    <button v-else @click="joinRoom(room)" class="ui-btn-secondary">Join</button>
                                    <button
                                        v-if="room.isMember"
                                        @click="leaveRoom(room)"
                                        class="text-xs text-content-faint hover:text-danger-fg inline-flex items-center gap-1"
                                    >
                                        <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                                        Leave
                                    </button>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Right: open room chat -->
                    <div class="lg:col-span-2">
                        <Card v-if="!activeRoom" class="h-full">
                            <EmptyState
                                :icon="ChatBubbleLeftRightIcon"
                                title="Open a room to start chatting"
                                description="Messages are live — classmates in the room see them instantly."
                            />
                        </Card>

                        <Card v-else class="flex h-[70vh] flex-col p-0 overflow-hidden">
                            <div class="flex items-center justify-between gap-3 border-b border-line p-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-content truncate">{{ activeRoom.title }}</p>
                                    <p class="text-xs text-content-muted">{{ activeRoom.section }} · {{ Object.keys(memberNames).length }} members</p>
                                </div>
                                <button @click="closeRoom" class="ui-btn-secondary">Close</button>
                            </div>

                            <div ref="thread" class="flex-1 space-y-3 overflow-y-auto p-4">
                                <p v-if="conversation.loading.value" class="text-sm text-content-muted">Loading messages…</p>
                                <p v-else-if="conversation.messages.value.length === 0" class="text-sm text-content-muted">
                                    No messages yet — say hi to your classmates.
                                </p>
                                <div
                                    v-for="message in conversation.messages.value"
                                    :key="message.id"
                                    class="flex"
                                    :class="Number(message.sender_id) === Number(currentUserId) ? 'justify-end' : 'justify-start'"
                                >
                                    <div
                                        class="max-w-[80%] rounded-2xl px-4 py-2.5 shadow-card"
                                        :class="Number(message.sender_id) === Number(currentUserId)
                                            ? 'bg-primary text-white rounded-br-md'
                                            : 'bg-neutral-bg text-content rounded-bl-md'"
                                    >
                                        <p class="text-xs font-semibold" :class="Number(message.sender_id) === Number(currentUserId) ? 'text-white/80' : 'text-primary'">
                                            {{ senderName(message) }}
                                        </p>
                                        <p class="mt-0.5 whitespace-pre-wrap text-sm leading-relaxed">{{ message.body }}</p>
                                        <p class="mt-1 text-right text-[11px]" :class="Number(message.sender_id) === Number(currentUserId) ? 'text-white/60' : 'text-content-faint'">
                                            {{ formatTime(message.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="sendDraft" class="flex items-center gap-2 border-t border-line p-3">
                                <input
                                    v-model="draft"
                                    type="text"
                                    placeholder="Message the room…"
                                    class="ui-input flex-1"
                                    aria-label="Message"
                                    @input="conversation.notifyTyping"
                                />
                                <button
                                    type="submit"
                                    class="ui-btn-primary"
                                    :disabled="!draft.trim() || conversation.sending.value"
                                    aria-label="Send message"
                                >
                                    <PaperAirplaneIcon class="w-4 h-4" />
                                </button>
                            </form>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

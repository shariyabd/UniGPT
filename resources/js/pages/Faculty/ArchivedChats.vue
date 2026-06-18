<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ArchiveBoxIcon,
    ArrowLeftIcon,
    ArrowUturnLeftIcon,
    TrashIcon,
    ChatBubbleLeftRightIcon,
    InboxIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

const toast = useToast();
const { confirm } = useConfirm();

const sessions = ref([...props.sessions]);

const formatRelativeTime = (timestamp) => {
    if (!timestamp) return '';
    const now = new Date();
    const date = new Date(timestamp);
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;
    return `${Math.floor(diffInHours / 24)}d ago`;
};

const restore = async (session) => {
    try {
        await axios.patch(route('faculty.ai-assistant.session.unarchive', session.id));
        sessions.value = sessions.value.filter((s) => s.id !== session.id);
        toast.success('Conversation restored.');
    } catch (e) {
        toast.error('Could not restore the conversation.');
    }
};

const remove = async (session) => {
    const ok = await confirm({
        title: 'Delete conversation',
        message: `“${session.title}” will be permanently deleted. This cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;

    try {
        await axios.delete(route('faculty.ai-assistant.session.destroy', session.id));
        sessions.value = sessions.value.filter((s) => s.id !== session.id);
        toast.success('Conversation deleted.');
    } catch (e) {
        toast.error('Could not delete the conversation.');
    }
};
</script>

<template>
    <div>
        <Head title="Archived Chats" />

        <AppLayout>
            <div class="page-container space-y-6 py-8">
                <PageHeader
                    title="Archived Chats"
                    subtitle="Conversations you've archived. Restore them to your chat list or delete them for good."
                    :icon="ArchiveBoxIcon"
                >
                    <template #actions>
                        <Link :href="route('faculty.ai-assistant')" class="ui-btn-secondary">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back to Chat
                        </Link>
                    </template>
                </PageHeader>

                <Card v-if="sessions.length" padding="p-0">
                    <ul class="divide-y divide-line">
                        <li
                            v-for="session in sessions"
                            :key="session.id"
                            class="flex items-center gap-3 p-4 transition-colors hover:bg-primary-soft/40"
                        >
                            <span class="ui-icon-tile bg-neutral-bg text-content-muted flex-shrink-0">
                                <ChatBubbleLeftRightIcon class="h-5 w-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-content">{{ session.title }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-content-faint">
                                    <span>{{ session.messageCount }} msgs</span>
                                    <span v-if="session.timestamp">•</span>
                                    <span v-if="session.timestamp">{{ formatRelativeTime(session.timestamp) }}</span>
                                </p>
                            </div>

                            <div class="flex flex-shrink-0 items-center gap-1">
                                <button
                                    @click="restore(session)"
                                    class="inline-flex items-center gap-1.5 rounded-control px-3 py-1.5 text-sm font-medium text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                                    title="Restore to chat list"
                                >
                                    <ArrowUturnLeftIcon class="h-4 w-4" />
                                    <span class="hidden sm:inline">Restore</span>
                                </button>
                                <button
                                    @click="remove(session)"
                                    class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                    aria-label="Delete conversation"
                                    title="Delete permanently"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </Card>

                <Card v-else padding="p-0">
                    <EmptyState
                        title="No archived chats"
                        description="When you archive a conversation from the chat sidebar, it'll appear here."
                        :icon="InboxIcon"
                    >
                        <Link :href="route('faculty.ai-assistant')" class="ui-btn-primary">
                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                            Go to Chat
                        </Link>
                    </EmptyState>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

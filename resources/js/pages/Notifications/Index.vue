<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import {
    BellIcon,
    AcademicCapIcon,
    BookOpenIcon,
    CalendarDaysIcon,
    MegaphoneIcon,
    DocumentTextIcon,
    InboxArrowDownIcon,
    UserPlusIcon,
    TrashIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    notifications: { type: Object, default: () => ({ data: [] }) },
    unreadCount: { type: Number, default: 0 },
});

const notificationItems = computed(() => props.notifications.data ?? []);

const iconMap = {
    AcademicCapIcon,
    BookOpenIcon,
    CalendarDaysIcon,
    MegaphoneIcon,
    DocumentTextIcon,
    InboxArrowDownIcon,
    UserPlusIcon,
    BellIcon,
};

const iconFor = (name) => iconMap[name] ?? BellIcon;

const open = (item) => {
    const go = () => {
        if (item.link) {
            router.visit(item.link);
        }
    };

    if (item.read) {
        go();

        return;
    }

    router.post(route('notifications.read', item.id), {}, {
        preserveScroll: true,
        onFinish: go,
    });
};

const markRead = (item) => {
    router.post(route('notifications.read', item.id), {}, { preserveScroll: true });
};

const markAllRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};

const remove = (item) => {
    router.delete(route('notifications.destroy', item.id), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Notifications" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Notifications"
                    :subtitle="unreadCount > 0 ? `${unreadCount} unread` : 'All caught up'"
                    :icon="BellIcon"
                >
                    <template #actions>
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            @click="markAllRead"
                            class="ui-btn-secondary"
                        >
                            <CheckIcon class="w-4 h-4" />
                            Mark all read
                        </button>
                    </template>
                </PageHeader>

                <Card v-if="notificationItems.length === 0" padding="p-0">
                    <EmptyState
                        title="You're all caught up"
                        description="You have no notifications right now. New updates will show up here."
                        :icon="BellIcon"
                    />
                </Card>

                <Card v-else padding="p-0">
                    <ul class="divide-y divide-line">
                        <li
                            v-for="item in notificationItems"
                            :key="item.id"
                            class="group relative flex items-start gap-4 px-4 py-4 transition-colors sm:px-5"
                            :class="!item.read
                                ? 'border-l-2 border-primary bg-bg hover:bg-neutral-bg'
                                : 'border-l-2 border-transparent hover:bg-bg'"
                        >
                            <div
                                class="ui-icon-tile flex-shrink-0"
                                :class="!item.read
                                    ? 'bg-primary-soft text-primary'
                                    : 'bg-neutral-bg text-content-muted'"
                            >
                                <component :is="iconFor(item.icon)" class="h-5 w-5" />
                            </div>

                            <button
                                type="button"
                                @click="open(item)"
                                class="min-w-0 flex-1 text-left focus:outline-none"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="!item.read"
                                        class="h-2 w-2 flex-shrink-0 rounded-full bg-primary"
                                        aria-hidden="true"
                                    />
                                    <p class="truncate text-sm font-semibold text-content">
                                        {{ item.title }}
                                    </p>
                                </div>
                                <p
                                    v-if="item.message"
                                    class="mt-0.5 text-sm text-content-muted"
                                >
                                    {{ item.message }}
                                </p>
                                <p class="mt-1 text-xs text-content-faint">{{ item.time }}</p>
                            </button>

                            <div class="flex flex-shrink-0 items-center gap-1">
                                <button
                                    v-if="!item.read"
                                    type="button"
                                    @click="markRead(item)"
                                    title="Mark as read"
                                    aria-label="Mark as read"
                                    class="rounded-control p-2 text-content-faint transition-colors hover:bg-neutral-bg hover:text-content"
                                >
                                    <CheckIcon class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="remove(item)"
                                    title="Delete"
                                    aria-label="Delete notification"
                                    class="rounded-control p-2 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </Card>

                <Pagination :paginator="notifications" label="notifications" />
            </div>
        </AppLayout>
    </div>
</template>

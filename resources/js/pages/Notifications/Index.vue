<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BellIcon,
    AcademicCapIcon,
    BookOpenIcon,
    CalendarDaysIcon,
    MegaphoneIcon,
    TrashIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    notifications: { type: Array, default: () => [] },
    unreadCount: { type: Number, default: 0 },
});

const iconMap = {
    AcademicCapIcon,
    BookOpenIcon,
    CalendarDaysIcon,
    MegaphoneIcon,
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
    <Head title="Notifications" />

    <AppLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ unreadCount }} unread
                    </p>
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    @click="markAllRead"
                    class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700"
                >
                    <CheckIcon class="w-4 h-4" /> Mark all read
                </button>
            </div>

            <div v-if="notifications.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                <BellIcon class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                You have no notifications yet.
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                <div
                    v-for="item in notifications"
                    :key="item.id"
                    class="flex items-start gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                    :class="{ 'bg-indigo-50/40 dark:bg-indigo-900/10': !item.read }"
                >
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <component :is="iconFor(item.icon)" class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                    </div>
                    <button type="button" @click="open(item)" class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.title }}</p>
                        <p v-if="item.message" class="text-sm text-gray-600 dark:text-gray-300">{{ item.message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ item.time }}</p>
                    </button>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button
                            v-if="!item.read"
                            type="button"
                            @click="markRead(item)"
                            title="Mark as read"
                            class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                        >
                            <CheckIcon class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="remove(item)"
                            title="Delete"
                            class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

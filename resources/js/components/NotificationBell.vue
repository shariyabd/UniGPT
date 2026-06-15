<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { BellIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const open = ref(false);

const unread = computed(() => page.props.notifications?.unread ?? 0);
const items = computed(() => page.props.notifications?.items ?? []);

const toggle = () => {
    open.value = !open.value;
};

const openItem = (item) => {
    open.value = false;

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
        preserveState: true,
        onFinish: go,
    });
};

const markAllRead = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const viewAll = () => {
    open.value = false;
    router.visit(route('notifications.index'));
};

if (typeof window !== 'undefined') {
    window.addEventListener('click', (e) => {
        if (!e.target.closest('[data-notification-bell]')) {
            open.value = false;
        }
    });
}
</script>

<template>
    <div class="relative" data-notification-bell>
        <button
            type="button"
            @click="toggle"
            class="relative p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            aria-label="Notifications"
        >
            <BellIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
            <span
                v-if="unread > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
            >
                {{ unread > 99 ? '99+' : unread }}
            </span>
        </button>

        <div
            v-show="open"
            class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden"
        >
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</span>
                <button
                    v-if="unread > 0"
                    type="button"
                    @click="markAllRead"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Mark all read
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                <div v-if="items.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                    You're all caught up.
                </div>
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    @click="openItem(item)"
                    class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex gap-3"
                    :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': !item.read }"
                >
                    <span
                        class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                        :class="item.read ? 'bg-transparent' : 'bg-indigo-500'"
                    ></span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.title }}</span>
                        <span v-if="item.message" class="block text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ item.message }}</span>
                        <span class="block text-[11px] text-gray-400 mt-0.5">{{ item.time }}</span>
                    </span>
                </button>
            </div>

            <button
                type="button"
                @click="viewAll"
                class="block w-full text-center px-4 py-2.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 border-t border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50"
            >
                View all
            </button>
        </div>
    </div>
</template>

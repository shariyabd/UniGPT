<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { BellIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const open = ref(false);

// Local state, seeded from the shared Inertia prop and kept fresh by a poll.
const unread = ref(page.props.notifications?.unread ?? 0);
const items = ref(page.props.notifications?.items ?? []);

// Re-sync whenever an Inertia navigation refreshes the shared notification prop.
watch(() => page.props.notifications, (value) => {
    if (value) {
        unread.value = value.unread ?? 0;
        items.value = value.items ?? [];
    }
});

// Lightweight periodic refresh (no full page reload).
let pollTimer = null;
const poll = async () => {
    try {
        const { data } = await axios.get(route('notifications.poll'));
        unread.value = data.unread ?? 0;
        items.value = data.items ?? [];
    } catch (e) {
        // Ignore transient network errors; the next tick retries.
    }
};

onMounted(() => { pollTimer = window.setInterval(poll, 75000); });
onUnmounted(() => { if (pollTimer) window.clearInterval(pollTimer); });

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
            class="relative p-2 rounded-control text-content-muted hover:bg-primary-soft hover:text-primary transition-colors"
            aria-label="Notifications"
        >
            <BellIcon class="w-6 h-6" />
            <span
                v-if="unread > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-white text-[10px] font-bold flex items-center justify-center"
            >
                {{ unread > 99 ? '99+' : unread }}
            </span>
        </button>

        <div
            v-show="open"
            class="absolute right-0 mt-2 w-80 bg-surface rounded-card shadow-card-hover border border-line z-50 overflow-hidden"
        >
            <div class="flex items-center justify-between px-4 py-3 border-b border-line">
                <span class="text-sm font-semibold text-content">Notifications</span>
                <button
                    v-if="unread > 0"
                    type="button"
                    @click="markAllRead"
                    class="text-xs font-medium text-primary hover:underline"
                >
                    Mark all read
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-line">
                <div v-if="items.length === 0" class="px-4 py-8 text-center text-sm text-content-faint">
                    You're all caught up.
                </div>
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    @click="openItem(item)"
                    class="w-full text-left px-4 py-3 hover:bg-primary-soft transition-colors flex gap-3"
                    :class="{ 'bg-primary-soft/50': !item.read }"
                >
                    <span
                        class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                        :class="item.read ? 'bg-transparent' : 'bg-primary'"
                    ></span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-content truncate">{{ item.title }}</span>
                        <span v-if="item.message" class="block text-xs text-content-muted line-clamp-2">{{ item.message }}</span>
                        <span class="block text-[11px] text-content-faint mt-0.5">{{ item.time }}</span>
                    </span>
                </button>
            </div>

            <button
                type="button"
                @click="viewAll"
                class="block w-full text-center px-4 py-2.5 text-sm font-medium text-primary border-t border-line hover:bg-primary-soft transition-colors"
            >
                View all
            </button>
        </div>
    </div>
</template>

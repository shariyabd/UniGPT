<script setup>
/**
 * A single directory entry (faculty or student) shown in the list view.
 * The "Message" button deep-links into the dedicated chat page with this
 * person opened as the active conversation.
 */
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/ui/Badge.vue';
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';

defineProps({
    name: { type: String, required: true },
    avatar: { type: String, required: true },
    subtitle: { type: String, default: '' },
    // Optional presence ('online' | 'away' | 'offline'); the dot is hidden when
    // empty, so we don't imply a presence system that doesn't exist yet.
    status: { type: String, default: '' },
    // Small contextual pill (e.g. course code).
    tag: { type: String, default: '' },
    // Detail rows: [{ label, value }].
    meta: { type: Array, default: () => [] },
    // Inertia URL the "Message" button navigates to.
    chatHref: { type: String, required: true },
});

const statusColor = {
    online: 'bg-success-fg',
    away: 'bg-warning-fg',
    offline: 'bg-neutral-fg',
};
</script>

<template>
    <article class="ui-card ui-card-hover flex flex-col p-5">
        <div class="flex items-start gap-3.5">
            <div class="relative flex-shrink-0">
                <img :src="avatar" :alt="name" class="h-14 w-14 rounded-full object-cover" />
                <span
                    v-if="status"
                    class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-surface"
                    :class="statusColor[status] ?? statusColor.offline"
                ></span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <p class="truncate text-base font-semibold text-content">{{ name }}</p>
                    <Badge v-if="tag" variant="primary">{{ tag }}</Badge>
                </div>
                <p v-if="subtitle" class="mt-0.5 truncate text-sm text-content-muted">{{ subtitle }}</p>
            </div>
        </div>

        <dl v-if="meta.length" class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-line pt-4">
            <div v-for="item in meta" :key="item.label" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wider text-content-faint">{{ item.label }}</dt>
                <dd class="mt-0.5 truncate text-sm font-medium text-content">{{ item.value }}</dd>
            </div>
        </dl>

        <Link :href="chatHref" class="ui-btn-primary mt-5 w-full">
            <ChatBubbleLeftRightIcon class="h-4 w-4" />
            Message
        </Link>
    </article>
</template>

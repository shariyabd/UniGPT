<template>
    <div class="flex items-start space-x-4 p-4 hover:bg-neutral-bg rounded-control transition-colors">
        <div :class="['ui-icon-tile flex-shrink-0', iconToneClass]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon"></path>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-content truncate">{{ title }}</p>
            <p class="text-xs text-content-muted mt-1">{{ time }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'chat',
    },
    title: String,
    time: String,
    icon: String,
});

const iconToneClass = computed(() => {
    const classes = {
        chat: 'bg-primary-soft text-primary',
        document: 'bg-success-bg text-success-fg',
        save: 'bg-primary-soft text-primary',
    };
    return classes[props.type] || classes.chat;
});
</script>

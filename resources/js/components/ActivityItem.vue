<template>
    <div class="flex items-start space-x-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg transition-colors">
        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', iconBgClass]">
            <svg :class="['w-5 h-5', iconColorClass]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon"></path>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ title }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ time }}</p>
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

const iconBgClass = computed(() => {
    const classes = {
        chat: 'bg-blue-100 dark:bg-blue-900/30',
        document: 'bg-green-100 dark:bg-green-900/30',
        save: 'bg-purple-100 dark:bg-purple-900/30',
    };
    return classes[props.type] || classes.chat;
});

const iconColorClass = computed(() => {
    const classes = {
        chat: 'text-blue-600 dark:text-blue-400',
        document: 'text-green-600 dark:text-green-400',
        save: 'text-purple-600 dark:text-purple-400',
    };
    return classes[props.type] || classes.chat;
});
</script>

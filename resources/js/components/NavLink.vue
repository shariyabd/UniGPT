<template>
    <div>
        <Link
            :href="href"
            :class="[
                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                active
                    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                    : 'text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
        >
            <slot />
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    active: {
        type: Boolean,
        default: false
    }
});
</script>
<script setup>
import { router } from '@inertiajs/vue3';
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    class: {
        type: String,
        default: ''
    }
});

const logout = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => { }
    });
};
</script>

<template>
    <button
        @click="logout"
        :class="`flex items-center gap-2 px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors ${props.class}`"
    >
        <ArrowRightOnRectangleIcon class="w-4 h-4" />
        <span>Logout</span>
    </button>
</template>
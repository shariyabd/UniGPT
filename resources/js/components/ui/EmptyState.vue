<script setup>
defineProps({
    title: { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
        <div
            v-if="icon"
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-card bg-primary-soft text-primary"
        >
            <component :is="icon" class="h-7 w-7" />
        </div>
        <h3 class="text-base font-semibold text-content">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-content-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>

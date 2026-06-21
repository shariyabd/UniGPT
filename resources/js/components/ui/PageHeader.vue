<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    eyebrow: { type: String, default: '' },
    // Optional total-item count rendered as a pill beside the title.
    count: { type: [Number, null], default: null },
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3.5">
            <div
                v-if="icon"
                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-control bg-primary-soft text-primary"
            >
                <component :is="icon" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wider text-content-faint">{{ eyebrow }}</p>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-content">{{ title }}</h1>
                    <span
                        v-if="count !== null"
                        class="inline-flex items-center rounded-pill bg-primary-soft px-2.5 py-0.5 text-sm font-semibold text-primary"
                    >
                        {{ count }}
                    </span>
                </div>
                <p v-if="subtitle" class="mt-0.5 text-sm text-content-muted">{{ subtitle }}</p>
            </div>
        </div>
        <div v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

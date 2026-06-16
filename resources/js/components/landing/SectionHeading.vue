<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    align: { type: String, default: 'center' }, // 'center' | 'left'
});
</script>

<template>
    <div
        v-reveal
        class="reveal"
        :class="align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl text-left'"
    >
        <span
            v-if="eyebrow"
            class="inline-flex items-center gap-2 rounded-pill border border-line bg-primary-soft px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-primary"
        >
            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
            {{ eyebrow }}
        </span>

        <h2 class="mt-5 text-3xl font-bold tracking-tight text-content sm:text-4xl lg:text-[2.75rem] lg:leading-[1.1]">
            <slot name="title">{{ title }}</slot>
        </h2>

        <p v-if="subtitle" class="mt-4 text-base text-content-muted sm:text-lg">
            {{ subtitle }}
        </p>
    </div>
</template>

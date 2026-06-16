<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
});

// Map legacy + semantic variant names onto the status token palette.
const variants = {
    neutral: 'bg-neutral-bg text-neutral-fg',
    slate: 'bg-neutral-bg text-neutral-fg',
    primary: 'bg-primary-soft text-primary',
    brand: 'bg-primary-soft text-primary',
    violet: 'bg-primary-soft text-primary',
    success: 'bg-success-bg text-success-fg',
    warning: 'bg-warning-bg text-warning-fg',
    danger: 'bg-danger-bg text-danger-fg',
    info: 'bg-primary-soft text-primary',
};

const dotColor = {
    neutral: 'bg-neutral-fg',
    slate: 'bg-neutral-fg',
    primary: 'bg-primary',
    brand: 'bg-primary',
    violet: 'bg-primary',
    success: 'bg-success-fg',
    warning: 'bg-warning-fg',
    danger: 'bg-danger-fg',
    info: 'bg-primary',
};

const cls = computed(() => variants[props.variant] ?? variants.neutral);
const dotCls = computed(() => dotColor[props.variant] ?? dotColor.neutral);
</script>

<template>
    <span class="ui-badge" :class="cls">
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotCls"></span>
        <slot />
    </span>
</template>

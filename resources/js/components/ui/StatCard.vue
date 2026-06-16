<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Object, Function], default: null },
    color: { type: String, default: 'primary' },
    change: { type: String, default: '' },
    trend: { type: String, default: '' }, // 'up' | 'down'
    hint: { type: String, default: '' },
    variant: { type: String, default: 'soft' }, // retained for API compat; cards are always white
});

// Map any legacy/accent color name onto the semantic tone set used for the
// icon tile. Cards stay white (brief: never fill cards with saturated color).
const toneMap = {
    primary: 'primary', brand: 'primary', violet: 'primary', purple: 'primary',
    indigo: 'primary', lilac: 'primary', sky: 'primary', blue: 'primary', cyan: 'primary',
    success: 'success', emerald: 'success', green: 'success', teal: 'success', mint: 'success',
    warning: 'warning', amber: 'warning', yellow: 'warning', orange: 'warning', peach: 'warning',
    danger: 'danger', rose: 'danger', red: 'danger', pink: 'danger',
    neutral: 'neutral', slate: 'neutral', gray: 'neutral',
};
const tone = computed(() => toneMap[props.color] ?? 'primary');

const tileClass = {
    primary: 'bg-primary-soft text-primary',
    success: 'bg-success-bg text-success-fg',
    warning: 'bg-warning-bg text-warning-fg',
    danger: 'bg-danger-bg text-danger-fg',
    neutral: 'bg-neutral-bg text-neutral-fg',
};

const trendUp = computed(() => props.trend !== 'down');
</script>

<template>
    <div class="ui-card ui-card-hover p-5">
        <div class="flex items-center gap-3">
            <span v-if="icon" class="ui-icon-tile" :class="tileClass[tone]">
                <component :is="icon" class="h-5 w-5" />
            </span>
            <p class="text-sm font-medium text-content-muted">{{ label }}</p>
        </div>

        <div class="mt-4 flex items-end justify-between gap-3">
            <p class="text-stat text-content">{{ value }}</p>
            <span v-if="change" :class="trendUp ? 'ui-status-success' : 'ui-status-danger'">
                <span>{{ trendUp ? '▲' : '▼' }}</span>{{ change }}
            </span>
        </div>

        <div v-if="hint || $slots.action" class="mt-2 flex items-center justify-between gap-2">
            <p v-if="hint" class="text-xs text-content-faint">{{ hint }}</p>
            <slot name="action" />
        </div>
    </div>
</template>

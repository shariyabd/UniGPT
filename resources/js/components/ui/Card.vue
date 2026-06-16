<script setup>
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    padding: { type: String, default: 'p-5 sm:p-6' },
    hover: { type: Boolean, default: false },
});
</script>

<template>
    <section class="ui-card" :class="hover ? 'ui-card-hover' : ''">
        <header
            v-if="title || $slots.header || $slots.actions"
            class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6"
        >
            <slot name="header">
                <div class="flex items-center gap-2.5">
                    <component :is="icon" v-if="icon" class="h-5 w-5 text-content-faint" />
                    <div>
                        <h2 class="ui-card-title">{{ title }}</h2>
                        <p v-if="subtitle" class="text-xs text-content-muted">{{ subtitle }}</p>
                    </div>
                </div>
            </slot>
            <div v-if="$slots.actions" class="flex flex-shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </header>
        <div :class="padding">
            <slot />
        </div>
    </section>
</template>

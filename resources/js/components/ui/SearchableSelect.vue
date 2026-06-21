<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import {
    ChevronUpDownIcon,
    MagnifyingGlassIcon,
    CheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

/**
 * Accessible, searchable single-select dropdown — a drop-in replacement for a
 * plain <select> where scrolling a long option list is poor UX.
 *
 * Options are plain objects: { value, label, hint?, disabled? }. Each call site
 * maps its data to that shape, keeping this component presentation-only.
 */
const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select…' },
    searchPlaceholder: { type: String, default: 'Search…' },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    // Min options before the search box appears (small lists don't need it).
    searchThreshold: { type: Number, default: 6 },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const root = ref(null);
const searchInput = ref(null);

const selectedOption = computed(
    () => props.options.find((o) => o.value === props.modelValue) ?? null,
);

const showSearch = computed(() => props.options.length >= props.searchThreshold);

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((o) =>
        `${o.label} ${o.hint ?? ''}`.toLowerCase().includes(q),
    );
});

const toggle = () => {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        query.value = '';
        nextTick(() => searchInput.value?.focus());
    }
};

const close = () => {
    open.value = false;
};

const choose = (option) => {
    if (option.disabled) return;
    emit('update:modelValue', option.value);
    close();
};

const clear = () => {
    emit('update:modelValue', null);
    close();
};

const onDocumentClick = (event) => {
    if (root.value && !root.value.contains(event.target)) {
        close();
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        document.addEventListener('mousedown', onDocumentClick);
    } else {
        document.removeEventListener('mousedown', onDocumentClick);
    }
});

onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentClick));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            :disabled="disabled"
            class="ui-input flex w-full items-center justify-between gap-2 text-left disabled:cursor-not-allowed disabled:opacity-60"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @click="toggle"
        >
            <span :class="selectedOption ? 'text-content truncate' : 'text-content-faint truncate'">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <span class="flex flex-shrink-0 items-center gap-1">
                <XMarkIcon
                    v-if="clearable && selectedOption && !disabled"
                    class="h-4 w-4 text-content-faint hover:text-content-muted"
                    @click.stop="clear"
                />
                <ChevronUpDownIcon class="h-4 w-4 text-content-faint" />
            </span>
        </button>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-card border border-line bg-surface shadow-card"
        >
            <div v-if="showSearch" class="border-b border-line p-2">
                <div class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-content-faint" />
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="ui-input !py-1.5 pl-8 text-sm"
                        @keydown.esc.prevent="close"
                    />
                </div>
            </div>

            <ul class="max-h-60 overflow-y-auto py-1" role="listbox">
                <li
                    v-for="option in filteredOptions"
                    :key="option.value"
                    role="option"
                    :aria-selected="option.value === modelValue"
                    :class="[
                        'flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm',
                        option.disabled
                            ? 'cursor-not-allowed text-content-faint'
                            : 'text-content hover:bg-bg',
                        option.value === modelValue ? 'bg-primary-soft' : '',
                    ]"
                    @click="choose(option)"
                >
                    <span class="min-w-0 truncate">
                        {{ option.label }}
                        <span v-if="option.hint" class="text-content-faint">· {{ option.hint }}</span>
                    </span>
                    <CheckIcon
                        v-if="option.value === modelValue"
                        class="h-4 w-4 flex-shrink-0 text-primary"
                    />
                </li>
                <li
                    v-if="filteredOptions.length === 0"
                    class="px-3 py-3 text-center text-sm text-content-muted"
                >
                    No matches
                </li>
            </ul>
        </div>
    </div>
</template>

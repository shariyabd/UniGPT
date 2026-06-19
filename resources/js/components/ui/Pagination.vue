<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * Renders server-side pagination controls for a Laravel paginator passed as an
 * Inertia prop. Tolerant of both shapes used in this app:
 *   - the raw paginator (flat: current_page / last_page / from / to / total /
 *     prev_page_url / next_page_url), and
 *   - a JsonResource collection ({ data, meta: {...}, links: { prev, next } }).
 *
 * Hidden automatically when there is only a single page.
 */
const props = defineProps({
    // The paginator (raw object) or resource collection.
    paginator: {
        type: Object,
        default: null,
    },
    // Item noun shown in the "Showing X–Y of Z {label}" summary.
    label: {
        type: String,
        default: 'results',
    },
});

const meta = computed(() => props.paginator?.meta ?? props.paginator ?? {});
const links = computed(() => props.paginator?.links ?? {});
const prevUrl = computed(() => meta.value.prev_page_url ?? links.value.prev ?? null);
const nextUrl = computed(() => meta.value.next_page_url ?? links.value.next ?? null);
const hasPages = computed(() => (meta.value.last_page ?? 1) > 1);
</script>

<template>
    <div
        v-if="hasPages"
        class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row"
    >
        <p class="text-sm text-content-muted">
            Showing {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} of {{ meta.total ?? 0 }} {{ label }}
        </p>
        <nav class="flex items-center gap-2">
            <Link
                v-if="prevUrl"
                :href="prevUrl"
                preserve-scroll
                preserve-state
                class="ui-btn-secondary"
            >
                Previous
            </Link>
            <span v-else class="ui-btn-secondary cursor-not-allowed opacity-50">Previous</span>

            <span class="px-2 text-sm text-content-muted">
                Page {{ meta.current_page ?? 1 }} of {{ meta.last_page ?? 1 }}
            </span>

            <Link
                v-if="nextUrl"
                :href="nextUrl"
                preserve-scroll
                preserve-state
                class="ui-btn-secondary"
            >
                Next
            </Link>
            <span v-else class="ui-btn-secondary cursor-not-allowed opacity-50">Next</span>
        </nav>
    </div>
</template>

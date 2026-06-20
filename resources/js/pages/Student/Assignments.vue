<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import {
    DocumentTextIcon,
    ClipboardDocumentListIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    assignments: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ filter: 'all' }) },
    pendingCount: { type: Number, default: 0 },
});

const assignmentItems = computed(() => props.assignments.data ?? []);

// Map the student's per-assignment state to a label + badge variant.
const statusMeta = (item) => {
    if (item.status === 'graded') return { label: 'Graded', variant: 'success' };
    if (item.status === 'submitted') return { label: 'Submitted', variant: 'primary' };
    if (item.status === 'late') return { label: 'Submitted late', variant: 'warning' };
    if (item.isOverdue) return { label: 'Overdue', variant: 'danger' };
    return { label: 'Not submitted', variant: 'slate' };
};

const filters = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'To do' },
    { value: 'submitted', label: 'Submitted' },
    { value: 'graded', label: 'Graded' },
];
const activeFilter = ref(props.filters?.filter ?? 'all');

// Filtering happens SERVER-SIDE so the tabs work across every page; the current
// page is already the filtered set.
const filtered = computed(() => assignmentItems.value);

const applyFilter = (value) => {
    activeFilter.value = value;
    router.get(
        route('assignments'),
        { filter: value !== 'all' ? value : undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// Global pending count from the server (not just the current page).
const pendingCount = computed(() => props.pendingCount);

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'No due date';
</script>

<template>
    <div>
        <Head title="Assignments" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Assignments"
                    subtitle="Submit your work and track grades across your courses."
                    :icon="ClipboardDocumentListIcon"
                >
                    <template v-if="pendingCount > 0" #actions>
                        <Badge variant="warning">{{ pendingCount }} to submit</Badge>
                    </template>
                </PageHeader>

                <!-- Status filter -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="filter in filters"
                        :key="filter.value"
                        type="button"
                        class="rounded-pill border px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="activeFilter === filter.value
                            ? 'border-primary bg-primary text-white'
                            : 'border-line bg-surface text-content-muted hover:bg-bg'"
                        @click="applyFilter(filter.value)"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <EmptyState
                    v-if="filtered.length === 0"
                    :icon="DocumentTextIcon"
                    title="No assignments"
                    :description="activeFilter === 'all'
                        ? 'Your courses have no published assignments yet.'
                        : 'Nothing matches this filter.'"
                />

                <Card v-else padding="p-0">
                    <ul class="divide-y divide-line">
                        <li v-for="item in filtered" :key="item.id">
                            <Link
                                :href="route('assignments.show', item.id)"
                                class="group flex items-center gap-4 p-4 transition-colors hover:bg-bg sm:p-5"
                            >
                                <span class="ui-icon-tile flex-shrink-0 bg-primary-soft text-primary">
                                    <DocumentTextIcon class="h-5 w-5" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-semibold text-content-muted">{{ item.course.code }}</span>
                                        <Badge :variant="statusMeta(item).variant">{{ statusMeta(item).label }}</Badge>
                                    </div>
                                    <h3 class="mt-0.5 truncate font-bold text-content">{{ item.title }}</h3>
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-content-muted">
                                        <span class="flex items-center gap-1.5">
                                            <CalendarIcon class="h-4 w-4 text-content-faint" /> Due {{ formatDate(item.dueAt) }}
                                        </span>
                                        <span class="flex items-center gap-1.5 capitalize">{{ item.type }} · {{ item.totalPoints }} pts</span>
                                    </div>
                                </div>

                                <div class="flex flex-shrink-0 items-center gap-3">
                                    <span v-if="item.status === 'graded' && item.grade !== null" class="text-right">
                                        <span class="block text-lg font-bold text-success-fg">{{ item.grade }}<span class="text-sm text-content-faint">/{{ item.totalPoints }}</span></span>
                                    </span>
                                    <CheckCircleIcon v-else-if="item.status === 'submitted' || item.status === 'late'" class="h-6 w-6 text-primary" />
                                    <ExclamationTriangleIcon v-else-if="item.isOverdue" class="h-6 w-6 text-danger-fg" />
                                    <ArrowRightIcon class="h-4 w-4 text-content-faint transition-transform group-hover:translate-x-0.5" />
                                </div>
                            </Link>
                        </li>
                    </ul>
                </Card>

                <Pagination :paginator="assignments" label="assignments" />
            </div>
        </AppLayout>
    </div>
</template>

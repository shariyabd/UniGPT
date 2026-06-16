<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ClipboardDocumentCheckIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarDaysIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
    overall: {
        type: Object,
        default: () => ({ total: 0, attended: 0, absent: 0, rate: null }),
    },
});

const overallRate = computed(() => props.overall.rate ?? null);

const rateColor = (rate) => {
    if (rate === null) return 'text-content-faint';
    if (rate >= 85) return 'text-success-fg';
    if (rate >= 75) return 'text-warning-fg';
    return 'text-danger-fg';
};

const barColor = (rate) => {
    if (rate === null) return 'bg-neutral-bg';
    if (rate >= 85) return 'bg-success-fg';
    if (rate >= 75) return 'bg-warning-fg';
    return 'bg-danger-fg';
};

const statusVariant = (status) => ({
    present: 'success',
    late: 'warning',
    excused: 'info',
    absent: 'danger',
}[status] ?? 'slate');
</script>

<template>
    <div>
        <Head title="Attendance" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Attendance"
                    subtitle="Monitor your attendance across enrolled courses."
                    :icon="ClipboardDocumentCheckIcon"
                />

                <!-- Overall stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Overall Rate"
                        :value="overallRate !== null ? overallRate + '%' : '—'"
                        :icon="ClipboardDocumentCheckIcon"
                        color="brand"
                    />
                    <StatCard
                        label="Sessions"
                        :value="overall.total"
                        :icon="CalendarDaysIcon"
                        color="slate"
                    />
                    <StatCard
                        label="Attended"
                        :value="overall.attended"
                        :icon="CheckCircleIcon"
                        color="emerald"
                    />
                    <StatCard
                        label="Absent"
                        :value="overall.absent"
                        :icon="XCircleIcon"
                        color="rose"
                    />
                </div>

                <!-- Per-course breakdown -->
                <div v-if="courses.length" class="space-y-5 sm:space-y-6">
                    <Card v-for="course in courses" :key="course.id" hover>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-content">
                                    {{ course.code }} — {{ course.name }}
                                </h3>
                                <p class="mt-0.5 text-sm text-content-muted">
                                    {{ course.attended }} / {{ course.total }} sessions attended
                                </p>
                            </div>
                            <span :class="['text-xl font-bold', rateColor(course.rate)]">
                                {{ course.rate !== null ? course.rate + '%' : 'No data' }}
                            </span>
                        </div>

                        <div class="mt-4 h-2 w-full overflow-hidden rounded-pill bg-neutral-bg">
                            <div
                                :class="['h-2 rounded-pill transition-all duration-300', barColor(course.rate)]"
                                :style="{ width: (course.rate ?? 0) + '%' }"
                            ></div>
                        </div>

                        <div v-if="course.recent.length" class="mt-5 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="record in course.recent"
                                        :key="record.date"
                                        class="border-b border-line hover:bg-bg transition-colors"
                                    >
                                        <td class="px-4 py-3 text-content-muted">{{ record.date }}</td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="statusVariant(record.status)" dot>
                                                {{ record.status }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-4 text-sm text-content-faint">
                            No attendance recorded yet.
                        </p>
                    </Card>
                </div>

                <EmptyState
                    v-else
                    title="No courses yet"
                    description="You are not enrolled in any courses yet."
                    :icon="CalendarDaysIcon"
                />
            </div>
        </AppLayout>
    </div>
</template>

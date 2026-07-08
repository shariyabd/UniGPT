<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ChartBarIcon,
    ArrowLeftIcon,
    UsersIcon,
    NoSymbolIcon,
    AcademicCapIcon,
    FlagIcon,
    VideoCameraIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    test: { type: Object, required: true },
    attempts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const statusVariant = (status) => ({
    submitted: 'success',
    expired: 'warning',
    disqualified: 'danger',
    in_progress: 'slate',
}[status] ?? 'slate');

const statusLabel = (status) => ({
    submitted: 'Submitted',
    expired: 'Time expired',
    disqualified: 'Disqualified',
    in_progress: 'In progress',
}[status] ?? status);

// Risk score → traffic-light badge. Null means the test didn't run risk analysis.
const riskVariant = (score) => {
    if (score == null) return 'slate';
    if (score >= 70) return 'danger';
    if (score >= 40) return 'warning';
    return 'success';
};
</script>

<template>
    <div>
        <Head :title="`Results — ${props.test.title}`" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <Link :href="route('faculty.class-tests')" class="ui-btn-ghost w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to class tests
                </Link>

                <PageHeader
                    :title="props.test.title"
                    :subtitle="`${props.test.course.code} · Section ${props.test.section} · ${props.test.totalMarks} marks`"
                    :icon="ChartBarIcon"
                />

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard variant="filled" color="primary" label="Attempts" :value="stats.attempts ?? 0" :icon="UsersIcon" />
                    <StatCard variant="filled" color="warning" label="Flagged" :value="stats.flagged ?? 0" :icon="FlagIcon" />
                    <StatCard variant="filled" color="danger" label="Disqualified" :value="stats.disqualified ?? 0" :icon="NoSymbolIcon" />
                    <StatCard variant="filled" color="success" label="Average score" :value="stats.averageScore ?? '—'" :icon="AcademicCapIcon" />
                </div>

                <Card title="Attempts" padding="p-0">
                    <EmptyState
                        v-if="attempts.length === 0"
                        title="No attempts yet"
                        description="Results appear here as students complete the test."
                        :icon="UsersIcon"
                    />
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line text-left text-content-muted">
                                    <th class="px-4 py-3 font-medium">Student</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Score</th>
                                    <th class="px-4 py-3 font-medium">Warnings</th>
                                    <th class="px-4 py-3 font-medium">Risk</th>
                                    <th class="px-4 py-3 font-medium">Submitted</th>
                                    <th class="px-4 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="attempt in attempts" :key="attempt.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-content">{{ attempt.student.name }}</div>
                                        <div class="text-xs text-content-faint">{{ attempt.student.studentId }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="statusVariant(attempt.status)">{{ statusLabel(attempt.status) }}</Badge>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-content">
                                        {{ attempt.score }} / {{ attempt.totalMarks }}
                                    </td>
                                    <td class="px-4 py-3 text-content-muted">{{ attempt.violationCount }}</td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="riskVariant(attempt.riskScore)">
                                            {{ attempt.riskScore == null ? '—' : attempt.riskScore }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-content-muted">
                                        <div class="flex items-center gap-2">
                                            <span>{{ attempt.submittedAt ?? '—' }}</span>
                                            <VideoCameraIcon v-if="attempt.recordingCount > 0" class="h-4 w-4 text-content-faint" title="Has recordings" />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link :href="attempt.reviewUrl" class="ui-btn-ghost">Review</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

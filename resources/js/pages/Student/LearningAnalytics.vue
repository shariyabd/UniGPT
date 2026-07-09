<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatChart from '@/components/charts/StatChart.vue';
import {
    ChartBarIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentListIcon,
    FireIcon,
    CalendarDaysIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    analytics: { type: Object, required: true },
});

const s = computed(() => props.analytics.summary);

const dash = (value, suffix = '') => (value === null || value === undefined ? '—' : `${value}${suffix}`);

const cards = computed(() => [
    { label: 'CGPA', value: dash(s.value.cgpa), icon: AcademicCapIcon, color: 'primary' },
    { label: 'Attendance', value: dash(s.value.attendanceRate, '%'), icon: CalendarDaysIcon, color: 'success' },
    { label: 'Avg Test Score', value: dash(s.value.avgTestScore, '%'), icon: ClipboardDocumentCheckIcon, color: 'warning' },
    { label: 'Avg Assignment', value: dash(s.value.avgAssignmentScore, '%'), icon: ClipboardDocumentListIcon, color: 'primary' },
    { label: 'Study Streak', value: `${s.value.studyStreak} day${s.value.studyStreak === 1 ? '' : 's'}`, icon: FireIcon, color: 'danger' },
    { label: 'AI Sessions', value: String(s.value.aiSessions), icon: ChatBubbleLeftRightIcon, color: 'primary' },
]);

const has = (series) => Array.isArray(series) && series.length > 0;
</script>

<template>
    <div>
        <Head title="My Progress" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="My Progress"
                    subtitle="Track your academic performance and learning engagement over time."
                    :icon="ChartBarIcon"
                />

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <StatCard
                        v-for="card in cards"
                        :key="card.label"
                        :label="card.label"
                        :value="card.value"
                        :icon="card.icon"
                        :color="card.color"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <template #header><h2 class="ui-card-title">GPA trend</h2></template>
                        <StatChart
                            v-if="has(analytics.gpaTrend)"
                            type="line"
                            :series="analytics.gpaTrend"
                            label="GPA"
                            color="#6366f1"
                            :max="4"
                        />
                        <EmptyState
                            v-else
                            title="No graded semesters yet"
                            description="Your GPA trend appears once you have graded courses."
                            :icon="AcademicCapIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Attendance by course</h2></template>
                        <StatChart
                            v-if="has(analytics.attendanceByCourse)"
                            type="bar"
                            :series="analytics.attendanceByCourse"
                            label="Attendance %"
                            color="#10b981"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No attendance recorded"
                            description="Attendance appears once your instructors mark sessions."
                            :icon="CalendarDaysIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Class-test scores</h2></template>
                        <StatChart
                            v-if="has(analytics.testTrend)"
                            type="line"
                            :series="analytics.testTrend"
                            label="Score %"
                            color="#f59e0b"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No class tests taken"
                            description="Your class-test performance appears here after you submit a test."
                            :icon="ClipboardDocumentCheckIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Assignment scores by course</h2></template>
                        <StatChart
                            v-if="has(analytics.assignmentByCourse)"
                            type="bar"
                            :series="analytics.assignmentByCourse"
                            label="Score %"
                            color="#6366f1"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No graded assignments"
                            description="Assignment scores appear once your submissions are graded."
                            :icon="ClipboardDocumentListIcon"
                        />
                    </Card>

                    <Card class="lg:col-span-2">
                        <template #header><h2 class="ui-card-title">Weekly activity (last 8 weeks)</h2></template>
                        <StatChart
                            v-if="has(analytics.activityTrend)"
                            type="bar"
                            :series="analytics.activityTrend"
                            label="Actions"
                            color="#8b5cf6"
                        />
                        <EmptyState
                            v-else
                            title="No recent activity"
                            description="Your engagement trend builds as you use the platform."
                            :icon="FireIcon"
                        />
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ChartBarIcon,
    UserGroupIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    PresentationChartLineIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    overview: { type: Object, default: () => ({}) },
    selectedCourseId: { type: [Number, null], default: null },
    report: { type: Object, default: null },
});

const onCourseChange = (event) => {
    const id = event.target.value;
    if (id) {
        router.get(route('faculty.courses.analytics', id), {}, { preserveState: false });
    } else {
        router.get(route('faculty.analytics'));
    }
};

const maxGradeCount = computed(() => {
    if (!props.report?.gradeDistribution?.length) return 1;
    return Math.max(...props.report.gradeDistribution.map((g) => g.count), 1);
});

const formatPercent = (value) => (value != null ? value + '%' : '—');

const overviewCourses = computed(() => props.overview.courses ?? 0);
const overviewStudents = computed(() => props.overview.totalStudents ?? 0);
const overviewAttendance = computed(() => formatPercent(props.overview.averageAttendance));
const overviewPendingGrading = computed(() => props.overview.pendingGrading ?? 0);

const attendanceRate = computed(() => formatPercent(props.report?.attendance?.rate));
const averageScore = computed(() => formatPercent(props.report?.averageScore));
const completionRate = computed(() => formatPercent(props.report?.submissions?.completionRate));
</script>

<template>
    <div>
        <Head title="Learning Analytics" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Analytics"
                    subtitle="Performance and attendance reporting across your courses."
                    :icon="ChartBarIcon"
                    eyebrow="Faculty"
                >
                    <template #actions>
                        <div v-if="courses.length > 0">
                            <label for="course-select" class="sr-only">Select course</label>
                            <select
                                id="course-select"
                                :value="selectedCourseId ?? ''"
                                @change="onCourseChange"
                                class="ui-input min-w-[16rem]"
                            >
                                <option value="">All Courses</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                            </select>
                        </div>
                    </template>
                </PageHeader>

                <!-- Overview KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Courses"
                        :value="overviewCourses"
                        :icon="AcademicCapIcon"
                        variant="filled"
                        color="mint"
                    />
                    <StatCard
                        label="Students"
                        :value="overviewStudents"
                        :icon="UserGroupIcon"
                        variant="filled"
                        color="sky"
                    />
                    <StatCard
                        label="Avg Attendance"
                        :value="overviewAttendance"
                        :icon="PresentationChartLineIcon"
                        variant="filled"
                        color="lilac"
                    />
                    <StatCard
                        label="Pending Grading"
                        :value="overviewPendingGrading"
                        :icon="ClipboardDocumentCheckIcon"
                        variant="filled"
                        color="yellow"
                    />
                </div>

                <EmptyState
                    v-if="!report"
                    title="No courses to report on"
                    description="You are not teaching any courses yet."
                    :icon="ChartBarIcon"
                />

                <div v-else class="space-y-6 sm:space-y-8">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-content">
                            {{ report.course.code }} — {{ report.course.name }}
                        </h2>
                    </div>

                    <!-- Course KPIs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                        <StatCard
                            label="Enrolled"
                            :value="report.enrolled"
                            :icon="UserGroupIcon"
                            variant="filled"
                            color="mint"
                        />
                        <StatCard
                            label="Attendance"
                            :value="attendanceRate"
                            :icon="ClockIcon"
                            variant="filled"
                            color="sky"
                            :hint="`${report.attendance.sessions} sessions`"
                        />
                        <StatCard
                            label="Avg Score"
                            :value="averageScore"
                            :icon="PresentationChartLineIcon"
                            variant="filled"
                            color="lilac"
                        />
                        <StatCard
                            label="Graded"
                            :value="completionRate"
                            :icon="CheckBadgeIcon"
                            variant="filled"
                            color="yellow"
                            :hint="`${report.submissions.graded}/${report.submissions.total} submissions`"
                        />
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Grade distribution -->
                        <Card title="Grade Distribution" :icon="ChartBarIcon">
                            <div v-if="report.gradeDistribution.length === 0" class="text-sm text-content-muted py-4">
                                No grades recorded yet.
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="g in report.gradeDistribution" :key="g.grade" class="flex items-center gap-3">
                                    <span class="w-8 text-sm font-bold text-content">{{ g.grade }}</span>
                                    <div class="flex-1 bg-neutral-bg rounded-full h-3 overflow-hidden">
                                        <div
                                            class="bg-primary h-3 rounded-full transition-all duration-300"
                                            :style="{ width: (g.count / maxGradeCount * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="w-6 text-sm font-medium text-content-muted text-right">{{ g.count }}</span>
                                </div>
                            </div>
                        </Card>

                        <!-- At-risk students -->
                        <Card title="At-Risk Students" :icon="ExclamationTriangleIcon">
                            <div v-if="report.atRisk.length === 0" class="flex items-center gap-2 text-sm text-content-muted py-4">
                                <span class="h-2 w-2 rounded-full bg-success-fg" />
                                No students currently flagged.
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="s in report.atRisk"
                                    :key="s.id"
                                    class="flex items-center justify-between gap-3 p-3 rounded-card bg-warning-bg"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-content">{{ s.name }}</p>
                                        <p class="text-xs text-content-muted">{{ s.studentId }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-1 justify-end">
                                        <Badge v-for="reason in s.reasons" :key="reason" variant="warning">{{ reason }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

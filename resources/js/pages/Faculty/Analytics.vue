<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChartBarIcon,
    UserGroupIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
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
</script>

<template>
    <Head title="Learning Analytics" />

    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                        <ChartBarIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Learning Analytics</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Performance and attendance reporting across your courses.</p>
                    </div>
                </div>
                <select
                    v-if="courses.length > 0"
                    :value="selectedCourseId ?? ''"
                    @change="onCourseChange"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                </select>
            </div>

            <!-- Overview -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.courses ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Courses</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.totalStudents ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Students</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ overview.averageAttendance ?? '—' }}<span v-if="overview.averageAttendance">%</span></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Avg Attendance</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ overview.pendingGrading ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pending Grading</div>
                </div>
            </div>

            <div v-if="!report" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                You are not teaching any courses yet.
            </div>

            <div v-else class="space-y-8">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ report.course.code }} — {{ report.course.name }}
                </h2>

                <!-- Course KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 flex items-center gap-3">
                        <UserGroupIcon class="w-8 h-8 text-blue-500" />
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ report.enrolled }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Enrolled</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                        <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ report.attendance.rate ?? '—' }}<span v-if="report.attendance.rate">%</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Attendance ({{ report.attendance.sessions }} sessions)</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ report.averageScore ?? '—' }}<span v-if="report.averageScore">%</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Avg Score</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 flex items-center gap-3">
                        <CheckBadgeIcon class="w-8 h-8 text-green-500" />
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ report.submissions.completionRate ?? '—' }}<span v-if="report.submissions.completionRate">%</span></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Graded ({{ report.submissions.graded }}/{{ report.submissions.total }})</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Grade distribution -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Grade Distribution</h3>
                        <div v-if="report.gradeDistribution.length === 0" class="text-sm text-gray-400">No grades recorded yet.</div>
                        <div v-else class="space-y-3">
                            <div v-for="g in report.gradeDistribution" :key="g.grade" class="flex items-center gap-3">
                                <span class="w-8 text-sm font-bold text-gray-700 dark:text-gray-300">{{ g.grade }}</span>
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                    <div class="bg-gradient-to-r from-violet-500 to-purple-600 h-4 rounded-full" :style="{ width: (g.count / maxGradeCount * 100) + '%' }"></div>
                                </div>
                                <span class="w-6 text-sm text-gray-500 dark:text-gray-400 text-right">{{ g.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- At-risk students -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" /> At-Risk Students
                        </h3>
                        <div v-if="report.atRisk.length === 0" class="text-sm text-gray-400">No students currently flagged. 🎉</div>
                        <div v-else class="space-y-2">
                            <div v-for="s in report.atRisk" :key="s.id" class="flex items-center justify-between p-3 rounded-lg bg-amber-50 dark:bg-amber-900/10">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ s.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ s.studentId }}</p>
                                </div>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    <span v-for="reason in s.reasons" :key="reason" class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ reason }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

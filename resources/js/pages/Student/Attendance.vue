<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
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
    if (rate === null) return 'text-gray-400';
    if (rate >= 85) return 'text-green-600 dark:text-green-400';
    if (rate >= 75) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const barColor = (rate) => {
    if (rate === null) return 'bg-gray-300';
    if (rate >= 85) return 'bg-green-500';
    if (rate >= 75) return 'bg-yellow-500';
    return 'bg-red-500';
};

const statusBadge = (status) => ({
    present: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    late: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    excused: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    absent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}[status] ?? 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="Attendance" />

    <AppLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Attendance</h1>
                <p class="text-gray-600 dark:text-gray-400">Monitor your attendance across enrolled courses.</p>
            </div>

            <!-- Overall stats -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl mr-4 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                            <CalendarDaysIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Overall Rate</h3>
                            <p :class="['text-2xl font-bold', rateColor(overallRate)]">
                                {{ overallRate !== null ? overallRate + '%' : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl mr-4 bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400">
                            <CalendarDaysIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Sessions</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overall.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl mr-4 bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                            <CheckCircleIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Attended</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overall.attended }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl mr-4 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <XCircleIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Absent</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overall.absent }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Per-course breakdown -->
            <div v-if="courses.length" class="space-y-4">
                <div
                    v-for="course in courses"
                    :key="course.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ course.code }} — {{ course.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ course.attended }} / {{ course.total }} sessions attended
                            </p>
                        </div>
                        <span :class="['text-xl font-bold', rateColor(course.rate)]">
                            {{ course.rate !== null ? course.rate + '%' : 'No data' }}
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
                        <div
                            :class="['h-2 rounded-full transition-all', barColor(course.rate)]"
                            :style="{ width: (course.rate ?? 0) + '%' }"
                        ></div>
                    </div>

                    <div v-if="course.recent.length" class="flex flex-wrap gap-2">
                        <span
                            v-for="record in course.recent"
                            :key="record.date"
                            :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium', statusBadge(record.status)]"
                        >
                            <ClockIcon class="w-3 h-3" />
                            {{ record.date }} · {{ record.status }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-400">No attendance recorded yet.</p>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <CalendarDaysIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                <p class="text-gray-500 dark:text-gray-400">You are not enrolled in any courses yet.</p>
            </div>
        </div>
    </AppLayout>
</template>

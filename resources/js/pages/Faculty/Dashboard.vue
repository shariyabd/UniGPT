<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    AcademicCapIcon,
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChartBarIcon,
    ClockIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const { can } = usePermissions();

// Real data from the backend (FacultyDashboardController@index).
const props = defineProps({
    faculty: { type: Object, default: () => ({}) },
    courseStats: { type: Array, default: () => [] },
    activeCourses: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
});

const statIconMap = {
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChartBarIcon,
};

const courseStats = computed(() =>
    props.courseStats.map((stat) => ({
        ...stat,
        icon: statIconMap[stat.icon] ?? ChartBarIcon,
    }))
);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

// Static navigation shortcuts (UI config, not data) pointing at real routes.
const quickActions = [
    {
        title: 'AI Teaching Assistant',
        description: 'Generate quizzes and assignments',
        icon: SparklesIcon,
        route: 'faculty.ai-assistant',
        gradient: 'from-purple-500 to-indigo-600',
    },
    {
        title: 'Grade Submissions',
        description: 'Review and grade student work',
        icon: DocumentTextIcon,
        route: 'faculty.grading',
        gradient: 'from-orange-500 to-red-600',
    },
    {
        title: 'My Courses',
        description: 'Manage courses and materials',
        icon: BookOpenIcon,
        route: 'faculty.courses',
        gradient: 'from-green-500 to-emerald-600',
    },
];
</script>

<template>
    <div>
        <Head title="Faculty Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                            <img
                                :src="faculty.avatar"
                                :alt="faculty.name"
                                class="w-20 h-20 rounded-2xl border-4 border-white/30"
                            />
                            <div>
                                <h1 class="text-3xl font-bold text-white">
                                    {{ greeting }}, {{ faculty.name }}
                                </h1>
                                <p class="text-white/80 mt-1">
                                    <span v-if="faculty.department">{{ faculty.department }}</span>
                                    <span v-if="faculty.employeeId"> • {{ faculty.employeeId }}</span>
                                </p>
                                <p class="text-white/70 text-sm">{{ faculty.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6 space-y-8">
                    <!-- Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div
                            v-for="stat in courseStats"
                            :key="stat.label"
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6"
                        >
                            <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${stat.gradient} flex items-center justify-center mb-4`">
                                <component :is="stat.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Active Courses -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Active Courses</h2>
                                <Link
                                    v-if="can('create_course')"
                                    :href="route('faculty.courses.create')"
                                    class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                                >
                                    + New Course
                                </Link>
                            </div>

                            <div v-if="activeCourses.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center text-gray-500 dark:text-gray-400">
                                You are not teaching any courses yet.
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Link
                                    v-for="course in activeCourses"
                                    :key="course.id"
                                    :href="route('faculty.courses.show', course.id)"
                                    class="block bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <div class="text-sm font-semibold text-teal-600 dark:text-teal-400">{{ course.code }}</div>
                                            <h3 class="font-bold text-gray-900 dark:text-white">{{ course.name }}</h3>
                                        </div>
                                        <AcademicCapIcon class="w-6 h-6 text-gray-300 dark:text-gray-600" />
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {{ course.semester }} • {{ course.credits }} credits
                                    </p>
                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="flex items-center gap-1"><UserGroupIcon class="w-4 h-4" /> {{ course.students }}</span>
                                        <span class="flex items-center gap-1"><BookOpenIcon class="w-4 h-4" /> {{ course.materials }}</span>
                                        <span class="flex items-center gap-1"><DocumentTextIcon class="w-4 h-4" /> {{ course.assignments }}</span>
                                    </div>
                                    <div class="mt-4">
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            <span>Class progress</span>
                                            <span>{{ course.progress }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-teal-500 h-2 rounded-full" :style="{ width: course.progress + '%' }"></div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Sidebar: quick actions + recent activity -->
                        <div class="space-y-8">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                                <div class="space-y-3">
                                    <Link
                                        v-for="action in quickActions"
                                        :key="action.route"
                                        :href="route(action.route)"
                                        class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-xl shadow p-4 hover:shadow-lg transition-shadow"
                                    >
                                        <div :class="`w-10 h-10 rounded-lg bg-gradient-to-br ${action.gradient} flex items-center justify-center`">
                                            <component :is="action.icon" class="w-5 h-5 text-white" />
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ action.title }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ action.description }}</div>
                                        </div>
                                        <ArrowRightIcon class="w-4 h-4 text-gray-400" />
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg divide-y divide-gray-100 dark:divide-gray-700">
                                    <div v-if="recentActivities.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">
                                        No recent activity.
                                    </div>
                                    <div
                                        v-for="activity in recentActivities"
                                        :key="activity.id"
                                        class="flex items-start gap-3 p-4"
                                    >
                                        <ClockIcon class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200">{{ activity.description }}</p>
                                            <p class="text-xs text-gray-400">{{ activity.time }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

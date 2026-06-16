<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    AcademicCapIcon,
    BookOpenIcon,
    UserGroupIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChartBarIcon,
    ClockIcon,
    ArrowRightIcon,
    PlusIcon,
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

const statColors = ['success', 'primary', 'primary', 'warning'];

const courseStats = computed(() =>
    props.courseStats.map((stat, index) => ({
        ...stat,
        icon: statIconMap[stat.icon] ?? ChartBarIcon,
        color: statColors[index % statColors.length],
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
    },
    {
        title: 'Grade Submissions',
        description: 'Review and grade student work',
        icon: DocumentTextIcon,
        route: 'faculty.grading',
    },
    {
        title: 'My Courses',
        description: 'Manage courses and materials',
        icon: BookOpenIcon,
        route: 'faculty.courses',
    },
];

const facultySubtitle = computed(() => {
    const parts = [];
    if (props.faculty.department) parts.push(props.faculty.department);
    if (props.faculty.employeeId) parts.push(props.faculty.employeeId);
    return parts.join(' • ');
});
</script>

<template>
    <div>
        <Head title="Faculty Dashboard" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <!-- Greeting + page intro -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="faculty.avatar"
                            :src="faculty.avatar"
                            alt="Profile"
                            class="h-14 w-14 flex-shrink-0 rounded-card object-cover ring-1 ring-line"
                        />
                        <span
                            v-else
                            class="ui-icon-tile h-14 w-14 rounded-card bg-primary-soft text-primary"
                        >
                            <AcademicCapIcon class="h-7 w-7" />
                        </span>
                        <div class="min-w-0">
                            <h1 class="text-2xl font-bold tracking-tight text-content">
                                {{ greeting }}, {{ faculty.name }}
                            </h1>
                            <p class="mt-0.5 text-sm text-content-muted">
                                {{ facultySubtitle || faculty.email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="can('create_course')"
                            :href="route('faculty.courses.create')"
                            class="ui-btn-primary"
                        >
                            <PlusIcon class="w-4 h-4" />
                            New Course
                        </Link>
                    </div>
                </div>

                <!-- KPI cards (pastel, filled) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        v-for="(stat, i) in courseStats"
                        :key="stat.label"
                        variant="filled"
                        :label="stat.label"
                        :value="stat.value"
                        :icon="stat.icon"
                        :color="['warning', 'danger', 'primary', 'success'][i % 4]"
                        :change="stat.change"
                        :trend="stat.trend"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Active Courses -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card title="Active Courses" :icon="BookOpenIcon">
                            <template #actions>
                                <Link
                                    v-if="can('create_course')"
                                    :href="route('faculty.courses.create')"
                                    class="ui-btn-secondary"
                                >
                                    <PlusIcon class="w-4 h-4" />
                                    New Course
                                </Link>
                            </template>

                            <EmptyState
                                v-if="activeCourses.length === 0"
                                title="No active courses"
                                description="You are not teaching any courses yet."
                                :icon="BookOpenIcon"
                            />

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                                <Link
                                    v-for="course in activeCourses"
                                    :key="course.id"
                                    :href="route('faculty.courses.show', course.id)"
                                    class="group block rounded-card border border-line bg-surface p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <div class="text-sm font-semibold text-content-muted">{{ course.code }}</div>
                                            <h3 class="font-bold text-content">{{ course.name }}</h3>
                                        </div>
                                        <span class="ui-icon-tile bg-primary-soft text-primary">
                                            <AcademicCapIcon class="w-5 h-5" />
                                        </span>
                                    </div>
                                    <p class="text-xs text-content-muted mb-4">
                                        {{ course.semester }} • {{ course.credits }} credits
                                    </p>
                                    <div class="flex items-center gap-4 text-sm text-content-muted">
                                        <span class="flex items-center gap-1"><UserGroupIcon class="w-4 h-4" /> {{ course.students }}</span>
                                        <span class="flex items-center gap-1"><BookOpenIcon class="w-4 h-4" /> {{ course.materials }}</span>
                                        <span class="flex items-center gap-1"><DocumentTextIcon class="w-4 h-4" /> {{ course.assignments }}</span>
                                    </div>
                                    <div class="mt-4">
                                        <div class="flex items-center justify-between text-xs text-content-muted mb-1">
                                            <span>Class progress</span>
                                            <span>{{ course.progress }}%</span>
                                        </div>
                                        <div class="w-full bg-neutral-bg rounded-full h-2">
                                            <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: course.progress + '%' }"></div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar: quick actions + recent activity -->
                    <div class="space-y-6">
                        <Card title="Quick Actions" :icon="SparklesIcon">
                            <div class="space-y-3">
                                <Link
                                    v-for="action in quickActions"
                                    :key="action.route"
                                    :href="route(action.route)"
                                    class="group flex items-center gap-4 rounded-card border border-line bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card"
                                >
                                    <div class="ui-icon-tile bg-primary-soft text-primary">
                                        <component :is="action.icon" class="w-5 h-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-content text-sm">{{ action.title }}</div>
                                        <div class="text-xs text-content-muted">{{ action.description }}</div>
                                    </div>
                                    <ArrowRightIcon class="w-4 h-4 text-content-faint transition-transform group-hover:translate-x-0.5" />
                                </Link>
                            </div>
                        </Card>

                        <Card title="Recent Activity" :icon="ClockIcon" padding="p-0">
                            <EmptyState
                                v-if="recentActivities.length === 0"
                                title="No recent activity"
                                description="Activity will appear here as you work."
                                :icon="ClockIcon"
                            />
                            <div v-else class="divide-y divide-line">
                                <div
                                    v-for="activity in recentActivities"
                                    :key="activity.id"
                                    class="flex items-start gap-3 p-4 rounded-control hover:bg-bg"
                                >
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <ClockIcon class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-content">{{ activity.description }}</p>
                                        <p class="text-xs text-content-faint">{{ activity.time }}</p>
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

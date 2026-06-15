<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChartBarIcon,
    UsersIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    AcademicCapIcon,
    BookmarkIcon,
    RectangleStackIcon,
    UserGroupIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            totalUsers: 0,
            activeUsers: 0,
            totalQueries: 0,
            totalDocuments: 0,
            totalSessions: 0,
            savedAnswers: 0,
        }),
    },
    departmentStats: {
        type: Array,
        default: () => [],
    },
    topQueries: {
        type: Array,
        default: () => [],
    },
    usersByRole: {
        type: Array,
        default: () => [],
    },
});

const overviewStats = computed(() => [
    { label: 'Total Users', value: props.overview.totalUsers, icon: UsersIcon, gradient: 'from-blue-500 to-cyan-600' },
    { label: 'Active Users', value: props.overview.activeUsers, icon: UserGroupIcon, gradient: 'from-teal-500 to-emerald-600' },
    { label: 'Total Queries', value: props.overview.totalQueries, icon: ChatBubbleLeftRightIcon, gradient: 'from-green-500 to-emerald-600' },
    { label: 'Documents', value: props.overview.totalDocuments, icon: DocumentTextIcon, gradient: 'from-purple-500 to-pink-600' },
    { label: 'Chat Sessions', value: props.overview.totalSessions, icon: RectangleStackIcon, gradient: 'from-orange-500 to-red-600' },
    { label: 'Saved Answers', value: props.overview.savedAnswers, icon: BookmarkIcon, gradient: 'from-indigo-500 to-violet-600' },
]);

const maxDepartmentQueries = computed(
    () => Math.max(1, ...props.departmentStats.map((d) => d.queries)),
);

const maxRoleCount = computed(
    () => Math.max(1, ...props.usersByRole.map((r) => r.count)),
);

const totalQueries = computed(() => props.overview.totalQueries || 0);

const formatNumber = (value) => Number(value ?? 0).toLocaleString();

const refresh = () => {
    router.reload({ only: ['overview', 'departmentStats', 'topQueries', 'usersByRole'] });
};
</script>

<template>
    <div>
        <Head title="Analytics Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">📊 Analytics Dashboard</h1>
                                <p class="text-xl text-white/90">Live platform metrics for your AI academic assistant</p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    @click="refresh"
                                    class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white hover:bg-white/30 transition-all flex items-center gap-2"
                                >
                                    <ArrowPathIcon class="w-4 h-4" />
                                    Refresh
                                </button>
                                <Link
                                    href="/admin/dashboard"
                                    class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Overview Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                        <div
                            v-for="stat in overviewStats"
                            :key="stat.label"
                            class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1"
                        >
                            <div :class="`absolute inset-0 bg-gradient-to-br ${stat.gradient} opacity-5 group-hover:opacity-10 transition-opacity`"></div>
                            <div class="relative p-5">
                                <div :class="`inline-flex p-2.5 rounded-xl bg-gradient-to-br ${stat.gradient} shadow-lg mb-3`">
                                    <component :is="stat.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stat.value) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ stat.label }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Main -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Department Breakdown -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <AcademicCapIcon class="w-6 h-6 text-purple-600" />
                                    Queries by Department
                                </h2>

                                <div v-if="departmentStats.length" class="space-y-4">
                                    <div
                                        v-for="dept in departmentStats"
                                        :key="dept.name"
                                        class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ dept.name }}</h3>
                                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ dept.percentage }}%</span>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ formatNumber(dept.queries) }} queries</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div
                                                class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full transition-all duration-500"
                                                :style="{ width: (dept.queries / maxDepartmentQueries) * 100 + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-400 py-6 text-center">No query activity recorded yet.</p>
                            </div>

                            <!-- Top Queries -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <ChatBubbleLeftRightIcon class="w-6 h-6 text-green-600" />
                                    Most Asked Questions
                                </h2>

                                <div v-if="topQueries.length" class="space-y-3">
                                    <div
                                        v-for="(query, index) in topQueries"
                                        :key="index"
                                        class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            {{ index + 1 }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ query.query }}</p>
                                            <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ formatNumber(query.count) }} asks</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-400 py-6 text-center">No questions asked yet.</p>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-8">
                            <!-- Users by Role -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <UsersIcon class="w-6 h-6 text-blue-600" />
                                    Users by Role
                                </h2>

                                <div v-if="usersByRole.length" class="space-y-4">
                                    <div v-for="role in usersByRole" :key="role.role">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ role.role }}</span>
                                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(role.count) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div
                                                class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                                                :style="{ width: (role.count / maxRoleCount) * 100 + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-400 py-4 text-center">No roles defined.</p>
                            </div>

                            <!-- Totals summary -->
                            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg p-6 text-white">
                                <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                                    <ChartBarIcon class="w-5 h-5" />
                                    At a Glance
                                </h2>
                                <div class="space-y-3">
                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="text-sm">Total Queries</div>
                                        <div class="text-2xl font-bold">{{ formatNumber(totalQueries) }}</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="text-sm">Active / Total Users</div>
                                        <div class="text-2xl font-bold">{{ formatNumber(overview.activeUsers) }} / {{ formatNumber(overview.totalUsers) }}</div>
                                    </div>
                                </div>
                                <Link
                                    href="/admin/documents"
                                    class="block mt-4 text-center text-sm text-white/90 hover:text-white font-medium"
                                >
                                    Manage Documents →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

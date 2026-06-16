<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
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
    ArrowLeftIcon,
    ArrowRightIcon,
    QuestionMarkCircleIcon,
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
    { label: 'Total Users', value: props.overview.totalUsers, icon: UsersIcon, color: 'sky' },
    { label: 'Active Users', value: props.overview.activeUsers, icon: UserGroupIcon, color: 'mint' },
    { label: 'Total Queries', value: props.overview.totalQueries, icon: ChatBubbleLeftRightIcon, color: 'lilac' },
    { label: 'Documents', value: props.overview.totalDocuments, icon: DocumentTextIcon, color: 'peach' },
    { label: 'Chat Sessions', value: props.overview.totalSessions, icon: RectangleStackIcon, color: 'yellow' },
    { label: 'Saved Answers', value: props.overview.savedAnswers, icon: BookmarkIcon, color: 'rose' },
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
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Analytics"
                    subtitle="Live platform metrics for your AI academic assistant"
                    :icon="ChartBarIcon"
                    eyebrow="System Overview"
                >
                    <template #actions>
                        <button
                            type="button"
                            @click="refresh"
                            class="ui-btn-secondary"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                            Refresh
                        </button>
                        <Link href="/admin/dashboard" class="ui-btn-ghost">
                            <ArrowLeftIcon class="w-4 h-4" />
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Overview Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 sm:gap-5">
                    <StatCard
                        v-for="stat in overviewStats"
                        :key="stat.label"
                        variant="filled"
                        :label="stat.label"
                        :value="formatNumber(stat.value)"
                        :icon="stat.icon"
                        :color="stat.color"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Department Breakdown -->
                        <Card title="Queries by Department" :icon="AcademicCapIcon">
                            <div v-if="departmentStats.length" class="space-y-4">
                                <div
                                    v-for="dept in departmentStats"
                                    :key="dept.name"
                                    class="rounded-card bg-bg p-4 transition-colors hover:bg-surface hover:shadow-card"
                                >
                                    <div class="flex items-center justify-between mb-1.5">
                                        <h3 class="font-semibold text-content">{{ dept.name }}</h3>
                                        <span class="text-sm font-bold text-content">{{ dept.percentage }}%</span>
                                    </div>
                                    <div class="text-sm text-content-muted mb-2.5">{{ formatNumber(dept.queries) }} queries</div>
                                    <div class="w-full bg-neutral-bg rounded-full h-2 overflow-hidden">
                                        <div
                                            class="bg-primary h-2 rounded-full transition-all duration-500"
                                            :style="{ width: (dept.queries / maxDepartmentQueries) * 100 + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                title="No query activity recorded yet"
                                description="Department breakdown will appear once users start asking questions."
                                :icon="AcademicCapIcon"
                            />
                        </Card>

                        <!-- Top Queries -->
                        <Card title="Most Asked Questions" :icon="ChatBubbleLeftRightIcon">
                            <div v-if="topQueries.length" class="space-y-3">
                                <div
                                    v-for="(query, index) in topQueries"
                                    :key="index"
                                    class="flex items-start gap-4 rounded-card bg-bg p-4 transition-colors hover:bg-surface hover:shadow-card"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-soft text-primary flex items-center justify-center font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-content line-clamp-2">{{ query.query }}</p>
                                        <span class="text-sm text-content-muted font-semibold">{{ formatNumber(query.count) }} asks</span>
                                    </div>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                title="No questions asked yet"
                                description="Popular questions across the platform will be listed here."
                                :icon="QuestionMarkCircleIcon"
                            />
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Users by Role -->
                        <Card title="Users by Role" :icon="UsersIcon">
                            <div v-if="usersByRole.length" class="space-y-4">
                                <div v-for="role in usersByRole" :key="role.role">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-content">{{ role.role }}</span>
                                        <span class="text-sm font-bold text-content">{{ formatNumber(role.count) }}</span>
                                    </div>
                                    <div class="w-full bg-neutral-bg rounded-full h-2 overflow-hidden">
                                        <div
                                            class="bg-primary h-2 rounded-full transition-all duration-500"
                                            :style="{ width: (role.count / maxRoleCount) * 100 + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                title="No roles defined"
                                description="User role distribution will show once roles are assigned."
                                :icon="UserGroupIcon"
                            />
                        </Card>

                        <!-- Totals summary -->
                        <div class="ui-card p-6">
                            <h2 class="ui-card-title mb-4 flex items-center gap-2">
                                <ChartBarIcon class="w-5 h-5 text-primary" />
                                At a Glance
                            </h2>
                            <div class="space-y-3">
                                <div class="bg-bg rounded-card p-4">
                                    <div class="text-sm text-content-muted">Total Queries</div>
                                    <div class="text-2xl font-bold text-content">{{ formatNumber(totalQueries) }}</div>
                                </div>
                                <div class="bg-bg rounded-card p-4">
                                    <div class="text-sm text-content-muted">Active / Total Users</div>
                                    <div class="text-2xl font-bold text-content">{{ formatNumber(overview.activeUsers) }} / {{ formatNumber(overview.totalUsers) }}</div>
                                </div>
                            </div>
                            <Link
                                href="/admin/documents"
                                class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:opacity-80 transition-opacity"
                            >
                                Manage Documents
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
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

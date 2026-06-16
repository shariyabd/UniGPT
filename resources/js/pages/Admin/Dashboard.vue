<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ShieldCheckIcon,
    UsersIcon,
    DocumentIcon,
    ChartBarSquareIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowRightIcon,
    ServerIcon,
    CircleStackIcon,
    CloudIcon,
    CpuChipIcon,
    BeakerIcon,
    ChartPieIcon,
    HomeIcon,
    InboxStackIcon,
    SignalIcon
} from '@heroicons/vue/24/outline';

// Props from the backend
const props = defineProps({
    systemStats: { type: Array, default: () => [] },
    statistics: { type: Object, default: () => ({}) },
    pendingDocuments: { type: Object, default: () => ({ data: [] }) },
    recentActivities: { type: Array, default: () => [] },
    systemHealth: { type: Object, default: () => ({}) }
});

const { can } = usePermissions();

// Map string icon identifiers from the server to Heroicon components
const iconMap = {
    UsersIcon,
    ServerIcon,
    DocumentIcon,
    ShieldCheckIcon,
    ChartBarSquareIcon,
    BeakerIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CircleStackIcon,
    CloudIcon,
    CpuChipIcon,
    ChartPieIcon
};
const resolveIcon = (icon) => iconMap[icon] || UsersIcon;

// Rotating semantic tones for KPI / quick-action tiles
const tileTones = ['primary', 'primary', 'success', 'warning'];

// System statistics (mapped from server props)
const systemStats = computed(() =>
    (props.systemStats || []).map(stat => ({
        ...stat,
        icon: resolveIcon(stat.icon)
    }))
);

// Pending documents for approval (mapped from paginator into template field names)
const pendingDocuments = computed(() =>
    (props.pendingDocuments?.data || []).map(doc => ({
        id: doc.id,
        title: doc.title,
        author: doc.uploadedBy,
        department: doc.category,
        uploadDate: doc.uploadedAt,
        size: doc.fileSize,
        type: doc.type,
        status: doc.status,
        urgent: false,
        preview: ''
    }))
);

// Recent activities (mapped from server props into template field names)
const recentActivities = computed(() =>
    (props.recentActivities || []).map(activity => ({
        id: activity.id,
        type: activity.type,
        title: activity.description,
        user: activity.user,
        time: activity.timestamp,
        status: activity.type === 'rejection' ? 'error'
            : activity.type === 'upload' ? 'info'
            : 'success'
    }))
);

// Quick actions for admin
const quickActions = ref([
    {
        title: 'User Management',
        description: 'Manage users, roles, and permissions',
        icon: UsersIcon,
        action: 'admin.users',
        badge: '1,247 Users'
    },
    {
        title: 'Document Library',
        description: 'Manage document uploads and approvals',
        icon: DocumentIcon,
        action: 'admin.documents',
        badge: '3,456 Docs'
    },
    {
        title: 'System Analytics',
        description: 'View usage statistics and reports',
        icon: ChartBarSquareIcon,
        action: 'admin.analytics',
        badge: 'Real-time'
    },
    {
        title: 'AI Configuration',
        description: 'Configure AI models and settings',
        icon: BeakerIcon,
        action: 'admin.settings',
        badge: 'GPT-4'
    }
]);

// System health metrics - YOUR ORIGINAL DATA STRUCTURE
const systemHealth = ref({
    cpu: 45,
    memory: 62,
    storage: 38,
    network: 91,
    database: 88
});

// Student insights - YOUR ORIGINAL DATA
const studentInsights = ref({
    activeStudents: 1247,
    averagePerformance: 85.2,
    improvementRate: 12.5,
    satisfactionScore: 4.6
});

// Admin info
const adminInfo = ref({
    name: 'System Administrator',
    email: 'admin@university.edu',
    department: 'IT Administration',
    employeeId: 'ADM001',
    avatar: 'https://ui-avatars.com/api/?name=System+Administrator&background=8b5cf6&color=fff'
});

// Computed properties
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const pendingCount = computed(() => {
    return pendingDocuments.value.filter(doc => doc.status === 'pending').length;
});

const urgentCount = computed(() => {
    return pendingDocuments.value.filter(doc => doc.urgent && doc.status === 'pending').length;
});

const overallStudentPerformance = computed(() => {
    return studentInsights.value.averagePerformance;
});

// Methods
const handleQuickAction = (action) => {
    if (action.startsWith('admin.')) {
        router.visit(`/${action.replace('.', '/')}`);
    } else {
        router.visit(`/admin/${action}`);
    }
};

const approveDocument = (docId) => {
    router.post(route('admin.documents.approve', docId), {}, {
        preserveScroll: true
    });
};

const rejectDocument = (docId) => {
    router.post(route('admin.documents.reject', docId), {}, {
        preserveScroll: true
    });
};

const getHealthColor = (percentage) => {
    if (percentage >= 80) return 'bg-success-fg';
    if (percentage >= 60) return 'bg-warning-fg';
    return 'bg-danger-fg';
};

const getActivityIcon = (type) => {
    const iconMap = {
        approval: CheckCircleIcon,
        upload: DocumentIcon,
        rejection: ExclamationTriangleIcon,
        system: ServerIcon
    };
    return iconMap[type] || ClockIcon;
};

const getActivityColor = (status) => {
    const colorMap = {
        success: 'bg-success-bg text-success-fg',
        error: 'bg-danger-bg text-danger-fg',
        info: 'bg-primary-soft text-primary'
    };
    return colorMap[status] || 'bg-neutral-bg text-neutral-fg';
};
</script>

<template>
    <div>
        <Head title="Admin Dashboard" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">

                <!-- Page Header -->
                <PageHeader
                    title="Admin Dashboard"
                    :subtitle="`${greeting}, Administrator • ${adminInfo.employeeId} • ${adminInfo.department}`"
                    eyebrow="Full System Access"
                    :icon="HomeIcon"
                >
                    <template #actions>
                        <Link :href="route('admin.analytics')" class="ui-btn-secondary">
                            <ChartBarSquareIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Analytics</span>
                        </Link>
                        <Link :href="route('admin.settings')" class="ui-btn-primary">
                            <BeakerIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Settings</span>
                        </Link>
                    </template>
                </PageHeader>

                <!-- Light greeting row + at-a-glance stats -->
                <div class="ui-card p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="ui-icon-tile h-14 w-14 rounded-card bg-primary-soft text-primary">
                                <ShieldCheckIcon class="h-8 w-8" />
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-content">{{ greeting }}, Administrator</h2>
                                <p class="text-sm text-content-muted mt-1">Full System Access • {{ new Date().toLocaleDateString() }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <div class="rounded-control border border-line bg-bg px-3 py-2 text-center">
                                <div class="text-lg font-bold text-content">1,247</div>
                                <div class="text-[11px] text-content-muted">Total Users</div>
                            </div>
                            <div class="rounded-control border border-line bg-bg px-3 py-2 text-center">
                                <div class="text-lg font-bold text-content">89</div>
                                <div class="text-[11px] text-content-muted">Active Now</div>
                            </div>
                            <div class="rounded-control border border-line bg-bg px-3 py-2 text-center">
                                <div class="text-lg font-bold text-content">{{ pendingCount }}</div>
                                <div class="text-[11px] text-content-muted">Pending</div>
                            </div>
                            <div class="rounded-control border border-line bg-bg px-3 py-2 text-center">
                                <div class="text-lg font-bold text-content">99.8%</div>
                                <div class="text-[11px] text-content-muted">Uptime</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Control Panel / Quick Actions -->
                <Card title="Admin Control Panel" :icon="ShieldCheckIcon">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                        <button
                            v-for="action in quickActions"
                            :key="action.title"
                            @click="handleQuickAction(action.action)"
                            class="group relative flex flex-col rounded-card border border-line bg-surface p-5 text-left transition-all duration-200 hover:shadow-card hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <div class="absolute top-4 right-4">
                                <Badge variant="primary">{{ action.badge }}</Badge>
                            </div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="ui-icon-tile bg-primary-soft text-primary transition-transform group-hover:scale-105">
                                    <component :is="action.icon" class="w-6 h-6" />
                                </div>
                            </div>
                            <h3 class="font-semibold text-content">
                                {{ action.title }}
                            </h3>
                            <p class="mt-1 text-sm text-content-muted">
                                {{ action.description }}
                            </p>
                            <ArrowRightIcon class="mt-3 w-4 h-4 text-content-faint group-hover:text-primary group-hover:translate-x-1 transition-all" />
                        </button>
                    </div>
                </Card>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column: Statistics and System Health -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- System Statistics -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <StatCard
                                v-for="(stat, index) in systemStats"
                                :key="stat.label"
                                :label="stat.label"
                                :value="stat.value"
                                :icon="stat.icon"
                                variant="filled"
                                :color="tileTones[index % tileTones.length]"
                                :change="stat.change"
                                :trend="stat.trend"
                                :hint="stat.description"
                            />
                        </div>

                        <!-- System Health Monitor -->
                        <Card title="System Health" :icon="ServerIcon">
                            <template #actions>
                                <Badge variant="success" dot>All Systems Operational</Badge>
                            </template>
                            <div class="space-y-4">
                                <div v-for="(value, metric) in systemHealth" :key="metric">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-content capitalize">{{ metric }}</span>
                                        <span class="text-sm text-content-muted">{{ value }}%</span>
                                    </div>
                                    <div class="w-full bg-neutral-bg rounded-full h-2">
                                        <div
                                            :class="`h-2 rounded-full transition-all duration-500 ${getHealthColor(value)}`"
                                            :style="`width: ${value}%`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Right Column: Pending Approvals, Insights, Activities -->
                    <div class="space-y-6">

                        <!-- Pending Document Approvals -->
                        <Card title="Pending Approvals" :icon="InboxStackIcon">
                            <template #actions>
                                <Badge variant="warning">{{ pendingCount }} pending</Badge>
                            </template>

                            <div class="space-y-4">
                                <div
                                    v-for="doc in pendingDocuments.filter(d => d.status === 'pending')"
                                    :key="doc.id"
                                    :class="`p-4 rounded-control border transition-colors ${doc.urgent ? 'border-danger-fg/20 bg-danger-bg' : 'border-line bg-bg'}`"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-content text-sm">
                                                {{ doc.title }}
                                            </h4>
                                            <p class="text-xs text-content-muted mt-1">
                                                {{ doc.author }} • {{ doc.department }}
                                            </p>
                                            <p class="text-xs text-content-muted mt-1">
                                                {{ doc.size }} • {{ doc.type }}
                                            </p>
                                        </div>
                                        <Badge v-if="doc.urgent" variant="danger">Urgent</Badge>
                                    </div>

                                    <p class="text-xs text-content-muted mb-3 line-clamp-2">
                                        {{ doc.preview }}
                                    </p>

                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-content-faint">
                                            {{ doc.uploadDate }}
                                        </span>
                                        <div v-if="can('approve_document')" class="flex gap-2">
                                            <button
                                                @click="rejectDocument(doc.id)"
                                                class="ui-status-danger"
                                            >
                                                Reject
                                            </button>
                                            <button
                                                @click="approveDocument(doc.id)"
                                                class="ui-status-success"
                                            >
                                                Approve
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <EmptyState
                                    v-if="pendingDocuments.filter(d => d.status === 'pending').length === 0"
                                    title="No pending approvals"
                                    description="All documents have been reviewed."
                                    :icon="CheckCircleIcon"
                                />
                            </div>

                            <Link
                                :href="route('admin.approvals')"
                                class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover"
                            >
                                View All Approvals
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </Card>

                        <!-- Student Insights (light card) -->
                        <div class="ui-card p-6">
                            <div>
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="ui-card-title flex items-center gap-2">
                                        <ChartPieIcon class="w-5 h-5 text-content-faint" />
                                        Student Insights
                                    </h3>
                                    <span class="ui-status-success">
                                        {{ overallStudentPerformance }}% avg
                                    </span>
                                </div>

                                <!-- Performance Metrics -->
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="rounded-control border border-line bg-bg p-3 text-center">
                                        <div class="text-lg font-bold text-content">{{ studentInsights.activeStudents }}</div>
                                        <div class="text-xs text-content-muted">Active Students</div>
                                    </div>
                                    <div class="rounded-control border border-line bg-bg p-3 text-center">
                                        <div class="text-lg font-bold text-content">{{ studentInsights.averagePerformance }}%</div>
                                        <div class="text-xs text-content-muted">Avg Performance</div>
                                    </div>
                                    <div class="rounded-control border border-line bg-bg p-3 text-center">
                                        <div class="text-lg font-bold text-content">+{{ studentInsights.improvementRate }}%</div>
                                        <div class="text-xs text-content-muted">Improvement</div>
                                    </div>
                                    <div class="rounded-control border border-line bg-bg p-3 text-center">
                                        <div class="text-lg font-bold text-content">{{ studentInsights.satisfactionScore }}/5</div>
                                        <div class="text-xs text-content-muted">Satisfaction</div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-content-muted mb-2">
                                        <span>Overall Progress</span>
                                        <span>{{ overallStudentPerformance }}%</span>
                                    </div>
                                    <div class="w-full bg-neutral-bg rounded-full h-2">
                                        <div
                                            class="bg-primary h-2 rounded-full transition-all duration-500"
                                            :style="`width: ${overallStudentPerformance}%`"
                                        ></div>
                                    </div>
                                </div>

                                <Link
                                    :href="route('admin.analytics')"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover"
                                >
                                    View Detailed Analytics
                                    <ArrowRightIcon class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <Card title="Recent Activities" :icon="ClockIcon">
                            <div class="space-y-2">
                                <div
                                    v-for="activity in recentActivities.slice(0, 5)"
                                    :key="activity.id"
                                    class="flex items-start gap-3 p-3 rounded-control hover:bg-bg transition-colors"
                                >
                                    <div :class="`w-8 h-8 flex-shrink-0 rounded-full flex items-center justify-center ${getActivityColor(activity.status)}`">
                                        <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-content">{{ activity.title }}</h4>
                                        <div class="flex items-center justify-between mt-1">
                                            <p class="text-xs text-content-muted">by {{ activity.user }}</p>
                                            <p class="text-xs text-content-faint">{{ activity.time }}</p>
                                        </div>
                                    </div>
                                </div>

                                <EmptyState
                                    v-if="recentActivities.length === 0"
                                    title="No recent activity"
                                    description="System activity will appear here."
                                    :icon="SignalIcon"
                                />
                            </div>

                            <Link
                                href="/admin/activities"
                                class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover"
                            >
                                View All Activities
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </Card>
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

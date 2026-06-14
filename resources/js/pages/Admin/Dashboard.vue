<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    ShieldCheckIcon,
    UsersIcon,
    DocumentIcon,
    ChartBarSquareIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    PlusIcon,
    ArrowRightIcon,
    BellIcon,
    UserIcon,
    Cog6ToothIcon,
    ServerIcon,
    CircleStackIcon,
    CloudIcon,
    CpuChipIcon,
    BeakerIcon,
    ChartPieIcon
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
    Cog6ToothIcon,
    CircleStackIcon,
    CloudIcon,
    CpuChipIcon,
    ChartPieIcon
};
const resolveIcon = (icon) => iconMap[icon] || UsersIcon;

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
        gradient: 'from-blue-500 to-cyan-600',
        badge: '1,247 Users'
    },
    {
        title: 'Document Library',
        description: 'Manage document uploads and approvals',
        icon: DocumentIcon,
        action: 'admin.documents',
        gradient: 'from-purple-500 to-pink-600',
        badge: '3,456 Docs'
    },
    {
        title: 'System Analytics',
        description: 'View usage statistics and reports',
        icon: ChartBarSquareIcon,
        action: 'admin.analytics',
        gradient: 'from-green-500 to-emerald-600',
        badge: 'Real-time'
    },
    {
        title: 'AI Configuration',
        description: 'Configure AI models and settings',
        icon: BeakerIcon,
        action: 'admin.settings',
        gradient: 'from-orange-500 to-red-600',
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
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
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
        success: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        error: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        info: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400'
    };
    return colorMap[status] || 'text-gray-600 bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400';
};
</script>

<template>
    <div>
        <Head title="Admin Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-indigo-100 dark:from-gray-900 dark:via-purple-950 dark:to-indigo-950">

                <!-- Enhanced Admin Header with Action Buttons -->
                <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-900 dark:via-indigo-900 dark:to-blue-900">
                    <!-- Decorative Background Pattern -->
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 0%, transparent 50%)"></div>

                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">

                            <!-- Left Section: Admin Profile Info -->
                            <div class="flex items-center gap-6 flex-1">
                                <div class="relative flex-shrink-0">
                                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-2xl flex items-center justify-center border-4 border-white/20">
                                        <ShieldCheckIcon class="w-10 h-10 text-white" />
                                    </div>
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-400 rounded-full ring-4 ring-white dark:ring-gray-800 flex items-center justify-center">
                                        <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                                    </div>
                                </div>

                                <div class="text-white min-w-0">
                                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                                        {{ greeting }}, Administrator! 🛡️
                                    </h1>
                                    <p class="text-white/90 text-lg">
                                        {{ adminInfo.employeeId }} • {{ adminInfo.department }}
                                    </p>
                                    <p class="text-white/80 text-sm">
                                        Full System Access • {{ new Date().toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right Section: Action Buttons - Perfect Two Rows Layout -->
                            <div class="flex flex-col gap-3 xl:items-end">

                                <!-- Top Row: System Management Buttons -->
                                <div class="flex items-center gap-2 flex-wrap justify-center xl:justify-end">
                                    <!-- System Alerts with Enhanced Badge -->
                                    <button class="action-button group flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 relative">
                                        <BellIcon class="w-4 h-4 group-hover:animate-pulse" />
                                        <span class="text-sm font-medium hidden sm:inline">System Alerts</span>
                                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center badge-pulse">
                                            <span class="text-xs text-white font-bold">{{ urgentCount }}</span>
                                        </div>
                                    </button>

                                    <!-- Quick Analytics -->
                                    <Link
                                        :href="route('admin.analytics')"
                                        class="action-button group flex items-center gap-2 px-3 py-2 bg-blue-500/30 backdrop-blur-sm text-white hover:bg-blue-500/40 border border-blue-400/40 hover:border-blue-400/60 rounded-lg transition-all duration-200 transform hover:scale-105"
                                    >
                                        <ChartBarSquareIcon class="w-4 h-4" />
                                        <span class="text-sm font-medium hidden sm:inline">Analytics</span>
                                    </Link>
                                </div>

                                <!-- Bottom Row: User Management Buttons -->
                                <div class="flex items-center gap-2 flex-wrap justify-center xl:justify-end">
                                    <!-- Profile -->
                                    <Link
                                        href="/admin/profile"
                                        class="action-button flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 hover:scale-105"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        <span class="text-sm font-medium hidden sm:inline">Profile</span>
                                    </Link>

                                    <!-- Settings -->
                                    <Link
                                        :href="route('admin.settings')"
                                        class="action-button flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border border-white/20 hover:border-white/40 rounded-lg transition-all duration-200 hover:scale-105"
                                    >
                                        <Cog6ToothIcon class="w-4 h-4" />
                                        <span class="text-sm font-medium hidden sm:inline">Settings</span>
                                    </Link>

                                    <!-- Logout with Enhanced Styling -->
                                    <div class="logout-btn-wrapper">
                                        <LogoutButton class="action-button flex items-center gap-2 px-3 py-2 bg-red-500/30 backdrop-blur-sm text-white hover:bg-red-500/40 border border-red-400/40 hover:border-red-400/60 rounded-lg transition-all duration-200 hover:scale-105" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced System Status Row -->
                        <div class="mt-8 pt-6 border-t border-white/20">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1">1,247</div>
                                    <div class="text-sm text-white/80">Total Users</div>
                                    <div class="text-xs text-green-300 mt-1">+12 this week</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1 text-green-200">89</div>
                                    <div class="text-sm text-white/80">Active Now</div>
                                    <div class="text-xs text-green-300 mt-1">Online users</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1 text-yellow-200">{{ pendingCount }}</div>
                                    <div class="text-sm text-white/80">Pending</div>
                                    <div class="text-xs text-yellow-300 mt-1">{{ urgentCount }} urgent</div>
                                </div>
                                <div class="stats-card bg-white/20 backdrop-blur-lg rounded-xl p-4 text-white text-center border border-white/10 hover:bg-white/25 transition-all">
                                    <div class="text-2xl font-bold mb-1 text-green-200">99.8%</div>
                                    <div class="text-sm text-white/80">Uptime</div>
                                    <div class="text-xs text-green-300 mt-1">Excellent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Content -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                    <!-- Quick Actions Admin Panel -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <ShieldCheckIcon class="w-6 h-6 text-purple-600" />
                            Admin Control Panel
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div
                                v-for="action in quickActions"
                                :key="action.title"
                                @click="handleQuickAction(action.action)"
                                class="admin-card group cursor-pointer bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden"
                            >
                                <!-- Badge -->
                                <div class="absolute top-4 right-4 text-xs px-2 py-1 rounded-full bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-medium">
                                    {{ action.badge }}
                                </div>

                                <div class="flex items-center justify-between mb-4">
                                    <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${action.gradient} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform`">
                                        <component :is="action.icon" class="w-6 h-6 text-white" />
                                    </div>
                                    <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:translate-x-1 transition-all" />
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                    {{ action.title }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ action.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- Left Column: Statistics and System Health -->
                        <div class="lg:col-span-2 space-y-8">

                            <!-- System Statistics -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    v-for="stat in systemStats"
                                    :key="stat.label"
                                    class="stat-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300"
                                >
                                    <div class="flex items-center justify-between mb-4">
                                        <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${stat.gradient} flex items-center justify-center shadow-lg`">
                                            <component :is="stat.icon" class="w-6 h-6 text-white" />
                                        </div>
                                        <div :class="`text-xs px-2 py-1 rounded-full ${stat.trend === 'up' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : stat.trend === 'down' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}`">
                                            {{ stat.change }}
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                            {{ stat.value }}
                                        </h3>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            {{ stat.label }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                            {{ stat.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- YOUR ORIGINAL System Health Monitor Design -->
                            <div class="system-health bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ServerIcon class="w-5 h-5 text-green-500" />
                                        System Health
                                    </h3>
                                    <div class="text-xs bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-1 rounded-full">
                                        All Systems Operational
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="(value, metric) in systemHealth" :key="metric" class="health-metric">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ metric }}</span>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ value }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div
                                                :class="`h-2 rounded-full transition-all duration-500 ${getHealthColor(value)}`"
                                                :style="`width: ${value}%`"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: YOUR ORIGINAL Pending Approvals and Activities Design -->
                        <div class="space-y-8">

                            <!-- YOUR ORIGINAL Pending Document Approvals Design -->
                            <div class="approvals-section bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ExclamationTriangleIcon class="w-5 h-5 text-orange-500" />
                                        Pending Approvals
                                    </h3>
                                    <div class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-1 rounded-full">
                                        {{ pendingCount }} pending
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="doc in pendingDocuments.filter(d => d.status === 'pending')"
                                        :key="doc.id"
                                        :class="`document-card p-4 rounded-lg border transition-colors ${doc.urgent ? 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700' : 'border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600'}`"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                                    {{ doc.title }}
                                                </h4>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ doc.author }} • {{ doc.department }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                    {{ doc.size }} • {{ doc.type }}
                                                </p>
                                            </div>
                                            <div v-if="doc.urgent" class="text-xs bg-red-500 text-white px-2 py-1 rounded-full">
                                                Urgent
                                            </div>
                                        </div>

                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                            {{ doc.preview }}
                                        </p>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-500">
                                                {{ doc.uploadDate }}
                                            </span>
                                            <div v-if="can('approve_document')" class="flex gap-2">
                                                <button
                                                    @click="rejectDocument(doc.id)"
                                                    class="text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded transition-colors"
                                                >
                                                    Reject
                                                </button>
                                                <button
                                                    @click="approveDocument(doc.id)"
                                                    class="text-xs px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded transition-colors"
                                                >
                                                    Approve
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    :href="route('admin.approvals')"
                                    class="block mt-4 text-center py-2 text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium"
                                >
                                    View All Approvals →
                                </Link>
                            </div>

                            <!-- Student Insights Section - YOUR ORIGINAL DESIGN -->
                            <div class="student-insights bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg p-6 text-white">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold flex items-center gap-2">
                                        <ChartPieIcon class="w-5 h-5 text-white/80" />
                                        Student Insights
                                    </h3>
                                    <div class="text-xs bg-white/20 px-2 py-1 rounded-full">
                                        {{ overallStudentPerformance }}% avg
                                    </div>
                                </div>

                                <!-- Performance Metrics -->
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">{{ studentInsights.activeStudents }}</div>
                                        <div class="text-xs text-white/80">Active Students</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">{{ studentInsights.averagePerformance }}%</div>
                                        <div class="text-xs text-white/80">Avg Performance</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">+{{ studentInsights.improvementRate }}%</div>
                                        <div class="text-xs text-white/80">Improvement</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold">{{ studentInsights.satisfactionScore }}/5</div>
                                        <div class="text-xs text-white/80">Satisfaction</div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-white/80 mb-2">
                                        <span>Overall Progress</span>
                                        <span>{{ overallStudentPerformance }}%</span>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2">
                                        <div
                                            class="bg-white h-2 rounded-full transition-all duration-500"
                                            :style="`width: ${overallStudentPerformance}%`"
                                        ></div>
                                    </div>
                                </div>

                                <Link
                                    :href="route('admin.analytics')"
                                    class="block text-center text-sm text-white/90 hover:text-white font-medium"
                                >
                                    View Detailed Analytics →
                                </Link>
                            </div>

                            <!-- Recent Activities -->
                            <div class="activities-section bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ClockIcon class="w-5 h-5 text-blue-500" />
                                        Recent Activities
                                    </h3>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="activity in recentActivities.slice(0, 5)"
                                        :key="activity.id"
                                        class="activity-item flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <div :class="`w-8 h-8 rounded-full flex items-center justify-center ${getActivityColor(activity.status)}`">
                                            <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.title }}</h4>
                                            <div class="flex items-center justify-between mt-1">
                                                <p class="text-xs text-gray-600 dark:text-gray-400">by {{ activity.user }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500">{{ activity.time }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/admin/activities"
                                    class="block mt-4 text-center py-2 text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium"
                                >
                                    View All Activities →
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
/* Line clamping utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Action Button Enhancements */
.action-button {
    position: relative;
    overflow: hidden;
}

.action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s;
}

.action-button:hover::before {
    left: 100%;
}

/* Perfect Header Styling */
.logout-btn-wrapper :deep(.flex) {
    @apply !bg-red-500/30 !border-red-400/40 !text-white;
}

.logout-btn-wrapper :deep(.flex:hover) {
    @apply !bg-red-500/40 !border-red-400/60 !text-white;
}

/* Backdrop blur effects */
.bg-white\/20,
.bg-blue-500\/30,
.bg-red-500\/30 {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Stats Card Enhancements */
.stats-card {
    position: relative;
    overflow: hidden;
}

.stats-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    transform: translateX(100%);
    transition: transform 0.6s;
}

.stats-card:hover::after {
    transform: translateX(-100%);
}

/* Admin Card Hover Effects */
.admin-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.admin-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.dark .admin-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.admin-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(147, 51, 234, 0.1), transparent);
    transition: left 0.5s ease;
}

.admin-card:hover::after {
    left: 100%;
}

/* System Health Styling - YOUR ORIGINAL DESIGN */
.system-health {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
}

.dark .system-health {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    border: 1px solid #374151;
}

.health-metric {
    transition: all 0.3s ease;
}

.health-metric:hover {
    transform: translateX(4px);
    background: rgba(0, 0, 0, 0.02);
    border-radius: 8px;
    padding: 8px;
}

.dark .health-metric:hover {
    background: rgba(255, 255, 255, 0.02);
}

/* Student Insights Section Styles - YOUR ORIGINAL DESIGN */
.student-insights {
    position: relative;
    overflow: hidden;
}

.student-insights::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.insight-metric {
    transition: all 0.3s ease;
}

.insight-metric:hover {
    transform: scale(1.05);
    background: rgba(255,255,255,0.2);
}

/* Document Card Effects - YOUR ORIGINAL DESIGN */
.document-card {
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dark .document-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Activity Item Animations */
.activity-item {
    transition: all 0.3s ease;
}

.activity-item:hover {
    transform: translateX(4px);
}

/* Badge animations */
.badge-pulse {
    animation: badgePulse 2s infinite;
}

@keyframes badgePulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

/* Responsive design */
@media (max-width: 640px) {
    .hidden.sm\:inline {
        display: none;
    }

    .action-button {
        justify-content: center;
        min-width: 44px;
        min-height: 44px;
    }

    .stats-card {
        text-align: center;
        padding: 12px;
    }

    .insight-metric {
        padding: 12px;
    }
}

/* Enhanced mobile responsiveness */
@media (max-width: 768px) {
    .admin-card {
        padding: 16px;
    }

    .document-card {
        padding: 12px;
    }

    .activity-item {
        padding: 12px;
    }

    .system-health {
        padding: 16px;
    }

    .student-insights {
        padding: 16px;
    }
}

/* Focus states for accessibility */
.action-button:focus,
.admin-card:focus,
.document-card:focus {
    outline: 2px solid rgba(147, 51, 234, 0.5);
    outline-offset: 2px;
}
</style>
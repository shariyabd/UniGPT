<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    DocumentTextIcon,
    CloudArrowUpIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    UsersIcon,
    ChatBubbleLeftRightIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    Cog6ToothIcon,
    FolderIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

// Mock admin data
const adminStats = ref([
    {
        label: 'Total Documents',
        value: '1,247',
        change: '+12 today',
        trend: 'up',
        icon: DocumentTextIcon,
        gradient: 'from-blue-500 to-cyan-600'
    },
    {
        label: 'Pending Approvals',
        value: '23',
        change: '8 urgent',
        trend: 'attention',
        icon: ClockIcon,
        gradient: 'from-yellow-500 to-orange-600'
    },
    {
        label: 'Active Users',
        value: '892',
        change: '+45 this week',
        trend: 'up',
        icon: UsersIcon,
        gradient: 'from-green-500 to-emerald-600'
    },
    {
        label: 'Total Queries',
        value: '15,429',
        change: '+234 today',
        trend: 'up',
        icon: ChatBubbleLeftRightIcon,
        gradient: 'from-purple-500 to-pink-600'
    }
]);

const pendingDocuments = ref([
    {
        id: 1,
        title: 'Computer Science Syllabus 2024',
        department: 'CSE',
        uploadedBy: 'Dr. Smith',
        uploadDate: '2024-01-15',
        size: '2.4 MB',
        type: 'PDF',
        status: 'pending',
        urgent: true,
        preview: 'Contains updated curriculum for AI/ML courses...'
    },
    {
        id: 2,
        title: 'Attendance Policy Update',
        department: 'Administration',
        uploadedBy: 'Admin Office',
        uploadDate: '2024-01-14',
        size: '856 KB',
        type: 'DOCX',
        status: 'pending',
        urgent: false,
        preview: 'Revised attendance requirements for all departments...'
    },
    {
        id: 3,
        title: 'Final Exam Schedule Spring 2024',
        department: 'Academic Office',
        uploadedBy: 'Prof. Johnson',
        uploadDate: '2024-01-13',
        size: '1.2 MB',
        type: 'PDF',
        status: 'pending',
        urgent: true,
        preview: 'Complete examination timetable for all courses...'
    }
]);

const recentActivities = ref([
    {
        id: 1,
        type: 'approval',
        title: 'Approved "Database Systems Handbook"',
        user: 'Admin',
        time: '2 hours ago',
        status: 'success'
    },
    {
        id: 2,
        type: 'upload',
        title: 'New document uploaded by Dr. Williams',
        user: 'Dr. Williams',
        time: '4 hours ago',
        status: 'info'
    },
    {
        id: 3,
        type: 'rejection',
        title: 'Rejected "Outdated Course Material"',
        user: 'Admin',
        time: '6 hours ago',
        status: 'error'
    },
    {
        id: 4,
        type: 'user',
        title: '25 new student registrations',
        user: 'System',
        time: '8 hours ago',
        status: 'info'
    }
]);

const systemMetrics = ref({
    uptime: '99.9%',
    responseTime: '245ms',
    storageUsed: '67%',
    activeConnections: 156,
    queriesPerHour: 1247,
    errorRate: '0.02%'
});

const approveDocument = (docId) => {
    const doc = pendingDocuments.value.find(d => d.id === docId);
    if (doc) {
        doc.status = 'approved';
        // Add to recent activities
        recentActivities.value.unshift({
            id: Date.now(),
            type: 'approval',
            title: `Approved "${doc.title}"`,
            user: 'Admin',
            time: 'Just now',
            status: 'success'
        });
    }
};

const rejectDocument = (docId) => {
    const doc = pendingDocuments.value.find(d => d.id === docId);
    if (doc) {
        doc.status = 'rejected';
        // Add to recent activities
        recentActivities.value.unshift({
            id: Date.now(),
            type: 'rejection',
            title: `Rejected "${doc.title}"`,
            user: 'Admin',
            time: 'Just now',
            status: 'error'
        });
    }
};

const getActivityIcon = (type) => {
    switch (type) {
        case 'approval': return CheckCircleIcon;
        case 'rejection': return XCircleIcon;
        case 'upload': return CloudArrowUpIcon;
        case 'user': return UsersIcon;
        default: return DocumentTextIcon;
    }
};

const getActivityColor = (status) => {
    switch (status) {
        case 'success': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'error': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        case 'info': return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'text-gray-600 bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400';
    }
};
</script>

<template>
    <div>
        <Head title="Admin Dashboard" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 dark:from-indigo-900 dark:via-blue-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    Admin Dashboard 🛡️
                                </h1>
                                <p class="text-xl text-white/90">
                                    Manage documents, users, and system configuration
                                </p>
                            </div>
                            <div class="hidden md:flex items-center space-x-4">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white">
                                    <div class="text-sm font-medium">System Status</div>
                                    <div class="text-2xl font-bold text-green-300">{{ systemMetrics.uptime }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div
                            v-for="stat in adminStats"
                            :key="stat.label"
                            class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2"
                        >
                            <!-- Gradient Background -->
                            <div :class="`absolute inset-0 bg-gradient-to-br ${stat.gradient} opacity-5 group-hover:opacity-10 transition-opacity`"></div>

                            <div class="relative p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div :class="`p-3 rounded-xl bg-gradient-to-br ${stat.gradient} shadow-lg`">
                                        <component :is="stat.icon" class="w-6 h-6 text-white" />
                                    </div>
                                    <span :class="`text-xs font-semibold px-2 py-1 rounded-full ${
                                        stat.trend === 'up' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                        stat.trend === 'attention' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    }`">
                                        {{ stat.change }}
                                    </span>
                                </div>

                                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                    {{ stat.value }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ stat.label }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column - Document Management -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Quick Actions -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <Cog6ToothIcon class="w-6 h-6 text-indigo-600" />
                                    Admin Actions
                                </h2>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <!-- Upload Documents -->
                                    <Link
                                        href="/admin/documents/upload"
                                        class="group relative bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-indigo-400"
                                    >
                                        <CloudArrowUpIcon class="w-8 h-8 text-indigo-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Upload Documents</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Add new course materials</p>
                                    </Link>

                                    <!-- Manage Users -->
                                    <a
                                        href="/admin/users"
                                        class="group relative bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-green-400"
                                    >
                                        <UsersIcon class="w-8 h-8 text-green-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Manage Users</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">User roles & permissions</p>
                                    </a>

                                    <!-- System Settings -->
                                    <a
                                        href="/admin/settings"
                                        class="group relative bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-purple-400"
                                    >
                                        <Cog6ToothIcon class="w-8 h-8 text-purple-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">AI Settings</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Configure AI parameters</p>
                                    </a>

                                    <!-- Analytics -->
                                    <Link
                                        href="/admin/analytics"
                                        class="group relative bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/30 dark:to-cyan-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-blue-400"
                                    >
                                        <ChartBarIcon class="w-8 h-8 text-blue-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Analytics</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Usage reports & metrics</p>
                                    </Link>

                                    <!-- Document Library -->
                                    <a
                                        href="/admin/documents"
                                        class="group relative bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/30 dark:to-orange-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-yellow-400"
                                    >
                                        <FolderIcon class="w-8 h-8 text-yellow-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Document Library</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Browse all documents</p>
                                    </a>

                                    <!-- System Monitor -->
                                    <a
                                        href="/admin/monitor"
                                        class="group relative bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/30 dark:to-pink-900/30 p-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200 border-2 border-transparent hover:border-red-400"
                                    >
                                        <ExclamationTriangleIcon class="w-8 h-8 text-red-600 mb-3 group-hover:scale-110 transition-transform" />
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">System Monitor</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Health & performance</p>
                                    </a>
                                </div>
                            </div>

                            <!-- Pending Approvals -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ClockIcon class="w-6 h-6 text-orange-600" />
                                        Pending Approvals
                                        <span class="bg-orange-100 text-orange-800 text-xs font-bold px-2 py-1 rounded-full dark:bg-orange-900/30 dark:text-orange-400">
                                            {{ pendingDocuments.filter(doc => doc.status === 'pending').length }}
                                        </span>
                                    </h2>
                                    <a href="/admin/approvals" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium">
                                        View All →
                                    </a>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="doc in pendingDocuments.filter(d => d.status === 'pending')"
                                        :key="doc.id"
                                        :class="`p-4 rounded-xl border-2 transition-all duration-300 hover:shadow-md ${
                                            doc.urgent
                                                ? 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800'
                                                : 'border-gray-200 bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700'
                                        }`"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ doc.title }}</h3>
                                                    <span v-if="doc.urgent" class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full animate-pulse dark:bg-red-900 dark:text-red-200">
                                                        URGENT
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                    <div>
                                                        <span class="font-medium">Department:</span>
                                                        <div class="text-gray-900 dark:text-white">{{ doc.department }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium">Uploaded by:</span>
                                                        <div class="text-gray-900 dark:text-white">{{ doc.uploadedBy }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium">Size:</span>
                                                        <div class="text-gray-900 dark:text-white">{{ doc.size }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium">Type:</span>
                                                        <div class="text-gray-900 dark:text-white">{{ doc.type }}</div>
                                                    </div>
                                                </div>

                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                                    {{ doc.preview }}
                                                </p>

                                                <div class="flex items-center gap-3">
                                                    <button
                                                        @click="approveDocument(doc.id)"
                                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                                    >
                                                        <CheckCircleIcon class="w-4 h-4 mr-1" />
                                                        Approve
                                                    </button>
                                                    <button
                                                        @click="rejectDocument(doc.id)"
                                                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                                    >
                                                        <XCircleIcon class="w-4 h-4 mr-1" />
                                                        Reject
                                                    </button>
                                                    <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transform hover:-translate-y-0.5 transition-all duration-200">
                                                        <EyeIcon class="w-4 h-4 mr-1" />
                                                        Preview
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-8">
                            <!-- System Metrics -->
                            <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <ChartBarIcon class="w-6 h-6" />
                                    System Health
                                </h2>

                                <div class="space-y-4">
                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Response Time</span>
                                            <span class="font-bold">{{ systemMetrics.responseTime }}</span>
                                        </div>
                                        <div class="w-full bg-white/20 rounded-full h-2">
                                            <div class="bg-green-400 h-2 rounded-full" style="width: 85%"></div>
                                        </div>
                                    </div>

                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Storage Used</span>
                                            <span class="font-bold">{{ systemMetrics.storageUsed }}</span>
                                        </div>
                                        <div class="w-full bg-white/20 rounded-full h-2">
                                            <div class="bg-yellow-400 h-2 rounded-full" style="width: 67%"></div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                            <div class="text-2xl font-bold">{{ systemMetrics.activeConnections }}</div>
                                            <div class="text-xs text-white/80">Active Users</div>
                                        </div>
                                        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                            <div class="text-2xl font-bold">{{ systemMetrics.queriesPerHour }}</div>
                                            <div class="text-xs text-white/80">Queries/Hour</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activities -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <ClockIcon class="w-6 h-6 text-gray-600" />
                                    Recent Activities
                                </h2>

                                <div class="space-y-4">
                                    <div
                                        v-for="activity in recentActivities.slice(0, 6)"
                                        :key="activity.id"
                                        class="flex items-start space-x-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                                    >
                                        <div :class="`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${getActivityColor(activity.status)}`">
                                            <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ activity.user }} • {{ activity.time }}</p>
                                        </div>
                                    </div>
                                </div>

                                <a
                                    href="/admin/activities"
                                    class="block mt-4 text-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium"
                                >
                                    View All Activities →
                                </a>
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
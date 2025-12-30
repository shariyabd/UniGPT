<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChartBarIcon,
    UsersIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ClockIcon,
    EyeIcon,
    AcademicCapIcon,
    CalendarIcon,
    FunnelIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

// Analytics data state
const selectedTimeRange = ref('7d');
const selectedDepartment = ref('all');
const selectedUserType = ref('all');
const isLoading = ref(false);

// Mock analytics data
const overviewStats = ref([
    {
        label: 'Total Users',
        value: '1,247',
        change: '+12.5%',
        trend: 'up',
        icon: UsersIcon,
        gradient: 'from-blue-500 to-cyan-600',
        description: 'Active users this period'
    },
    {
        label: 'Total Queries',
        value: '15,429',
        change: '+8.3%',
        trend: 'up',
        icon: ChatBubbleLeftRightIcon,
        gradient: 'from-green-500 to-emerald-600',
        description: 'AI queries processed'
    },
    {
        label: 'Documents',
        value: '892',
        change: '+23.1%',
        trend: 'up',
        icon: DocumentTextIcon,
        gradient: 'from-purple-500 to-pink-600',
        description: 'Documents in library'
    },
    {
        label: 'Avg Response Time',
        value: '245ms',
        change: '-5.2%',
        trend: 'down',
        icon: ClockIcon,
        gradient: 'from-orange-500 to-red-600',
        description: 'System response time'
    }
]);

// Usage trends data (mock)
const usageTrends = ref({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
        {
            label: 'Student Queries',
            data: [245, 312, 278, 389, 456, 234, 123],
            color: 'rgb(59, 130, 246)' // blue
        },
        {
            label: 'Faculty Queries',
            data: [89, 95, 87, 102, 134, 78, 45],
            color: 'rgb(16, 185, 129)' // emerald
        },
        {
            label: 'Admin Actions',
            data: [12, 15, 18, 23, 19, 8, 3],
            color: 'rgb(139, 92, 246)' // purple
        }
    ]
});

// Department breakdown
const departmentStats = ref([
    { name: 'Computer Science', queries: 4521, users: 234, percentage: 35.2 },
    { name: 'Electrical Engineering', queries: 3234, users: 189, percentage: 25.1 },
    { name: 'Mechanical Engineering', queries: 2876, users: 156, percentage: 18.7 },
    { name: 'Civil Engineering', queries: 1987, users: 123, percentage: 12.9 },
    { name: 'Administration', queries: 1043, users: 67, percentage: 8.1 }
]);

// Top queries data
const topQueries = ref([
    {
        id: 1,
        query: 'What is the attendance policy for CSE department?',
        count: 234,
        avgConfidence: 95.2,
        lastAsked: '2 hours ago',
        category: 'Policy'
    },
    {
        id: 2,
        query: 'Explain database normalization with examples',
        count: 189,
        avgConfidence: 92.8,
        lastAsked: '5 hours ago',
        category: 'Academic'
    },
    {
        id: 3,
        query: 'When is the final exam registration deadline?',
        count: 167,
        avgConfidence: 98.1,
        lastAsked: '1 hour ago',
        category: 'Examination'
    },
    {
        id: 4,
        query: 'How to calculate CGPA from semester grades?',
        count: 145,
        avgConfidence: 87.3,
        lastAsked: '3 hours ago',
        category: 'Academic'
    },
    {
        id: 5,
        query: 'What are the requirements for internship?',
        count: 132,
        avgConfidence: 91.7,
        lastAsked: '8 hours ago',
        category: 'Requirements'
    }
]);

// Popular documents
const popularDocuments = ref([
    {
        id: 1,
        title: 'Academic Handbook 2024',
        views: 1234,
        downloads: 456,
        department: 'Administration',
        lastUpdated: '2024-01-15'
    },
    {
        id: 2,
        title: 'CSE Syllabus - AI/ML Track',
        views: 987,
        downloads: 234,
        department: 'Computer Science',
        lastUpdated: '2024-01-12'
    },
    {
        id: 3,
        title: 'Examination Guidelines',
        views: 876,
        downloads: 345,
        department: 'Academic Office',
        lastUpdated: '2024-01-10'
    },
    {
        id: 4,
        title: 'Attendance Policy Update',
        views: 654,
        downloads: 123,
        department: 'Administration',
        lastUpdated: '2024-01-08'
    }
]);

// User engagement metrics
const engagementMetrics = ref({
    dailyActiveUsers: { current: 423, previous: 389, change: 8.7 },
    avgSessionDuration: { current: '12m 34s', previous: '11m 42s', change: 7.4 },
    querySuccessRate: { current: 94.2, previous: 92.8, change: 1.5 },
    userSatisfaction: { current: 4.6, previous: 4.4, change: 4.5 }
});

// Time range options
const timeRanges = [
    { value: '1d', label: 'Last 24 Hours' },
    { value: '7d', label: 'Last 7 Days' },
    { value: '30d', label: 'Last 30 Days' },
    { value: '90d', label: 'Last 3 Months' },
    { value: '1y', label: 'Last Year' }
];

// Department options
const departments = [
    { value: 'all', label: 'All Departments' },
    { value: 'cse', label: 'Computer Science' },
    { value: 'ee', label: 'Electrical Engineering' },
    { value: 'me', label: 'Mechanical Engineering' },
    { value: 'ce', label: 'Civil Engineering' },
    { value: 'admin', label: 'Administration' }
];

// User type options
const userTypes = [
    { value: 'all', label: 'All Users' },
    { value: 'student', label: 'Students' },
    { value: 'faculty', label: 'Faculty' },
    { value: 'admin', label: 'Administrators' }
];

// Computed properties
const maxDepartmentQueries = computed(() => {
    return Math.max(...departmentStats.value.map(d => d.queries));
});

// Methods
const refreshData = () => {
    isLoading.value = true;
    // Simulate API call
    setTimeout(() => {
        isLoading.value = false;
        // Update data based on filters
    }, 1000);
};

const exportData = (type) => {
    alert(`Exporting ${type} data...`);
};

const getConfidenceColor = (confidence) => {
    if (confidence >= 95) return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    if (confidence >= 90) return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400';
    if (confidence >= 85) return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
    return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
};

const getCategoryColor = (category) => {
    const colors = {
        'Policy': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'Academic': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'Examination': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'Requirements': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
    };
    return colors[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

// Mock chart component (simplified for demo)
const renderChart = (canvasId, data) => {
    // In a real app, you'd use Chart.js, D3.js, or similar library
    setTimeout(() => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Simple bar chart simulation
            const barWidth = width / data.labels.length;
            const maxValue = Math.max(...data.datasets[0].data);

            data.labels.forEach((label, index) => {
                const value = data.datasets[0].data[index];
                const barHeight = (value / maxValue) * (height - 40);

                // Draw bar
                ctx.fillStyle = data.datasets[0].color;
                ctx.fillRect(index * barWidth + 10, height - barHeight - 20, barWidth - 20, barHeight);

                // Draw label
                ctx.fillStyle = '#6B7280';
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(label, index * barWidth + barWidth/2, height - 5);
            });
        }
    }, 100);
};

onMounted(() => {
    renderChart('usage-chart', usageTrends.value);
});
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
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    📊 Analytics Dashboard
                                </h1>
                                <p class="text-xl text-white/90">
                                    Insights and metrics for your AI academic assistant
                                </p>
                            </div>

                            <!-- Filters & Actions -->
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex gap-3">
                                    <!-- Time Range Filter -->
                                    <select
                                        v-model="selectedTimeRange"
                                        @change="refreshData"
                                        class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-white/50"
                                    >
                                        <option v-for="range in timeRanges" :key="range.value" :value="range.value" class="text-gray-900">
                                            {{ range.label }}
                                        </option>
                                    </select>

                                    <!-- Department Filter -->
                                    <select
                                        v-model="selectedDepartment"
                                        @change="refreshData"
                                        class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-white/50"
                                    >
                                        <option v-for="dept in departments" :key="dept.value" :value="dept.value" class="text-gray-900">
                                            {{ dept.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        @click="refreshData"
                                        :disabled="isLoading"
                                        class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white hover:bg-white/30 transition-all flex items-center gap-2"
                                    >
                                        <ArrowPathIcon :class="`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`" />
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
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Overview Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div
                            v-for="stat in overviewStats"
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
                                    <div class="flex items-center gap-1">
                                        <component
                                            :is="stat.trend === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                            :class="`w-4 h-4 ${stat.trend === 'up' ? 'text-green-600' : 'text-red-600'}`"
                                        />
                                        <span :class="`text-sm font-semibold ${stat.trend === 'up' ? 'text-green-600' : 'text-red-600'}`">
                                            {{ stat.change }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                    {{ stat.value }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    {{ stat.label }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ stat.description }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Main Analytics -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Usage Trends Chart -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ChartBarIcon class="w-6 h-6 text-blue-600" />
                                        Usage Trends
                                    </h2>
                                    <button
                                        @click="exportData('usage')"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium"
                                    >
                                        Export Data
                                    </button>
                                </div>

                                <!-- Chart Area -->
                                <div class="relative h-64 mb-4">
                                    <canvas
                                        id="usage-chart"
                                        width="800"
                                        height="256"
                                        class="w-full h-full rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800"
                                    ></canvas>
                                </div>

                                <!-- Chart Legend -->
                                <div class="flex flex-wrap gap-4 text-sm">
                                    <div v-for="dataset in usageTrends.datasets" :key="dataset.label" class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: dataset.color }"></div>
                                        <span class="text-gray-600 dark:text-gray-400">{{ dataset.label }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Department Breakdown -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <AcademicCapIcon class="w-6 h-6 text-purple-600" />
                                    Department Breakdown
                                </h2>

                                <div class="space-y-4">
                                    <div
                                        v-for="dept in departmentStats"
                                        :key="dept.name"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900/70 transition-colors"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-2">
                                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ dept.name }}</h3>
                                                <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ dept.percentage }}%</span>
                                            </div>

                                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                <span>{{ dept.queries.toLocaleString() }} queries</span>
                                                <span>{{ dept.users }} users</span>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full transition-all duration-500"
                                                    :style="{ width: (dept.queries / maxDepartmentQueries) * 100 + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Queries -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-green-600" />
                                        Most Asked Questions
                                    </h2>
                                    <button
                                        @click="exportData('queries')"
                                        class="text-sm text-green-600 dark:text-green-400 hover:text-green-700 font-medium"
                                    >
                                        View All
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="(query, index) in topQueries"
                                        :key="query.id"
                                        class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900/70 transition-colors"
                                    >
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            {{ index + 1 }}
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-4 mb-2">
                                                <p class="font-medium text-gray-900 dark:text-white line-clamp-2">
                                                    {{ query.query }}
                                                </p>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <span :class="`text-xs px-2 py-1 rounded-full font-medium ${getCategoryColor(query.category)}`">
                                                        {{ query.category }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="font-semibold">{{ query.count }} asks</span>
                                                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getConfidenceColor(query.avgConfidence)}`">
                                                    {{ query.avgConfidence }}% confidence
                                                </span>
                                                <span>{{ query.lastAsked }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-8">
                            <!-- Engagement Metrics -->
                            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg p-6 text-white">
                                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <UsersIcon class="w-6 h-6" />
                                    User Engagement
                                </h2>

                                <div class="space-y-4">
                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Daily Active Users</span>
                                            <div class="flex items-center gap-1">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-300" />
                                                <span class="text-sm font-bold text-green-300">+{{ engagementMetrics.dailyActiveUsers.change }}%</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-bold">{{ engagementMetrics.dailyActiveUsers.current }}</div>
                                    </div>

                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Avg Session Duration</span>
                                            <div class="flex items-center gap-1">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-300" />
                                                <span class="text-sm font-bold text-green-300">+{{ engagementMetrics.avgSessionDuration.change }}%</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-bold">{{ engagementMetrics.avgSessionDuration.current }}</div>
                                    </div>

                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Query Success Rate</span>
                                            <div class="flex items-center gap-1">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-300" />
                                                <span class="text-sm font-bold text-green-300">+{{ engagementMetrics.querySuccessRate.change }}%</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-bold">{{ engagementMetrics.querySuccessRate.current }}%</div>
                                    </div>

                                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">User Satisfaction</span>
                                            <div class="flex items-center gap-1">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-300" />
                                                <span class="text-sm font-bold text-green-300">+{{ engagementMetrics.userSatisfaction.change }}%</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-bold">{{ engagementMetrics.userSatisfaction.current }}/5.0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Popular Documents -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <DocumentTextIcon class="w-6 h-6 text-orange-600" />
                                    Popular Documents
                                </h2>

                                <div class="space-y-4">
                                    <div
                                        v-for="doc in popularDocuments"
                                        :key="doc.id"
                                        class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900/70 transition-colors"
                                    >
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-2 line-clamp-2">
                                            {{ doc.title }}
                                        </h3>

                                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            <div class="flex items-center gap-1">
                                                <EyeIcon class="w-3 h-3" />
                                                {{ doc.views }} views
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <ArrowTrendingDownIcon class="w-3 h-3" />
                                                {{ doc.downloads }} downloads
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">{{ doc.department }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-500">{{ doc.lastUpdated }}</span>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/admin/documents"
                                    class="block mt-4 text-center text-sm text-orange-600 dark:text-orange-400 hover:text-orange-700 font-medium"
                                >
                                    View All Documents →
                                </Link>
                            </div>

                            <!-- Quick Export -->
                            <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                                <h3 class="text-lg font-bold mb-4">Export Reports</h3>
                                <div class="space-y-3">
                                    <button
                                        @click="exportData('full')"
                                        class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg py-2 text-sm font-medium transition-all"
                                    >
                                        📊 Full Analytics Report
                                    </button>
                                    <button
                                        @click="exportData('usage')"
                                        class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg py-2 text-sm font-medium transition-all"
                                    >
                                        📈 Usage Statistics
                                    </button>
                                    <button
                                        @click="exportData('performance')"
                                        class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg py-2 text-sm font-medium transition-all"
                                    >
                                        ⚡ Performance Metrics
                                    </button>
                                </div>
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

canvas {
    border-radius: 8px;
}
</style>
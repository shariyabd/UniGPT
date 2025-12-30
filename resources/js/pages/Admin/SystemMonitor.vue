<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    CpuChipIcon,
    CircleStackIcon,
    ServerIcon,
    SignalIcon,
    BoltIcon,
    ChartBarIcon,
    DocumentTextIcon,
    EyeIcon,
    ArrowPathIcon,
    XCircleIcon,
    InformationCircleIcon,
    ShieldCheckIcon,
    CloudIcon,
    DatabaseIcon
} from '@heroicons/vue/24/outline';

// Component state
const refreshInterval = ref(null);
const isLoading = ref(false);
const lastUpdated = ref(new Date());
const selectedTimeRange = ref('1h');
const autoRefresh = ref(true);

// System metrics state
const systemMetrics = ref({
    cpu: {
        usage: 45.2,
        cores: 8,
        temperature: 65,
        processes: 342,
        loadAverage: [2.1, 1.8, 1.5]
    },
    memory: {
        total: 16384, // MB
        used: 7234,
        free: 9150,
        cached: 2456,
        swapTotal: 4096,
        swapUsed: 512
    },
    storage: {
        total: 1000, // GB
        used: 670,
        free: 330,
        partitions: [
            { name: '/', used: 45.2, total: 100, type: 'ext4' },
            { name: '/var', used: 78.9, total: 200, type: 'ext4' },
            { name: '/tmp', used: 12.3, total: 50, type: 'tmpfs' }
        ]
    },
    network: {
        interfaces: [
            {
                name: 'eth0',
                status: 'up',
                rxBytes: 1234567890,
                txBytes: 987654321,
                rxPackets: 45678,
                txPackets: 34567,
                rxErrors: 0,
                txErrors: 0
            }
        ],
        connections: {
            established: 156,
            timeWait: 23,
            listening: 12
        }
    }
});

// Performance metrics
const performanceMetrics = ref({
    responseTime: {
        current: 245,
        average: 280,
        p95: 450,
        p99: 680
    },
    throughput: {
        requestsPerSecond: 127,
        queriesPerSecond: 89,
        uploadsPerMinute: 23
    },
    errors: {
        rate: 0.02,
        count: 12,
        lastHour: [0, 1, 0, 2, 1, 0, 3, 1, 0, 0, 2, 1]
    },
    availability: {
        uptime: 99.97,
        uptimeSeconds: 2592000, // 30 days
        lastDowntime: '2024-01-10T14:30:00'
    }
});

// Service status
const services = ref([
    {
        name: 'Web Server (Nginx)',
        status: 'healthy',
        port: 80,
        responseTime: 12,
        lastCheck: new Date(),
        uptime: 99.99
    },
    {
        name: 'Application Server (PHP-FPM)',
        status: 'healthy',
        port: 9000,
        responseTime: 45,
        lastCheck: new Date(),
        uptime: 99.95
    },
    {
        name: 'Database (MySQL)',
        status: 'healthy',
        port: 3306,
        responseTime: 8,
        lastCheck: new Date(),
        uptime: 99.98,
        connections: 45,
        queries: 1247
    },
    {
        name: 'Redis Cache',
        status: 'healthy',
        port: 6379,
        responseTime: 2,
        lastCheck: new Date(),
        uptime: 100.0,
        hitRate: 94.2
    },
    {
        name: 'Queue Worker',
        status: 'warning',
        responseTime: 156,
        lastCheck: new Date(),
        uptime: 98.5,
        jobsProcessed: 4567,
        failedJobs: 12
    },
    {
        name: 'File Storage',
        status: 'healthy',
        responseTime: 23,
        lastCheck: new Date(),
        uptime: 99.9,
        freeSpace: '330 GB'
    }
]);

// System logs
const systemLogs = ref([
    {
        id: 1,
        timestamp: '2024-01-15T14:30:15',
        level: 'error',
        service: 'Application',
        message: 'Failed to connect to external API endpoint',
        details: 'Connection timeout after 30 seconds'
    },
    {
        id: 2,
        timestamp: '2024-01-15T14:25:42',
        level: 'warning',
        service: 'Queue Worker',
        message: 'High memory usage detected',
        details: 'Memory usage at 85% of allocated limit'
    },
    {
        id: 3,
        timestamp: '2024-01-15T14:20:33',
        level: 'info',
        service: 'Database',
        message: 'Slow query detected',
        details: 'Query execution time: 2.3 seconds'
    },
    {
        id: 4,
        timestamp: '2024-01-15T14:15:28',
        level: 'success',
        service: 'Backup',
        message: 'Daily backup completed successfully',
        details: 'Backup size: 2.4 GB, Duration: 45 minutes'
    },
    {
        id: 5,
        timestamp: '2024-01-15T14:10:19',
        level: 'warning',
        service: 'Security',
        message: 'Multiple failed login attempts detected',
        details: 'IP: 192.168.1.100, Attempts: 5'
    }
]);

// Alerts
const alerts = ref([
    {
        id: 1,
        type: 'warning',
        title: 'High Memory Usage',
        message: 'System memory usage is at 85%. Consider scaling resources.',
        timestamp: '2024-01-15T14:30:00',
        acknowledged: false,
        severity: 'medium'
    },
    {
        id: 2,
        type: 'info',
        title: 'Scheduled Maintenance',
        message: 'System maintenance scheduled for tonight 2:00 AM - 4:00 AM',
        timestamp: '2024-01-15T10:00:00',
        acknowledged: true,
        severity: 'low'
    }
]);

// Time range options
const timeRanges = [
    { value: '15m', label: 'Last 15 minutes' },
    { value: '1h', label: 'Last hour' },
    { value: '6h', label: 'Last 6 hours' },
    { value: '24h', label: 'Last 24 hours' },
    { value: '7d', label: 'Last 7 days' }
];

// Computed properties
const systemHealth = computed(() => {
    const cpu = systemMetrics.value.cpu.usage;
    const memory = (systemMetrics.value.memory.used / systemMetrics.value.memory.total) * 100;
    const storage = (systemMetrics.value.storage.used / systemMetrics.value.storage.total) * 100;

    if (cpu > 90 || memory > 90 || storage > 95) return { status: 'critical', color: 'red' };
    if (cpu > 70 || memory > 75 || storage > 85) return { status: 'warning', color: 'yellow' };
    return { status: 'healthy', color: 'green' };
});

const memoryUsagePercent = computed(() => {
    return (systemMetrics.value.memory.used / systemMetrics.value.memory.total) * 100;
});

const storageUsagePercent = computed(() => {
    return (systemMetrics.value.storage.used / systemMetrics.value.storage.total) * 100;
});

const activeAlerts = computed(() => {
    return alerts.value.filter(alert => !alert.acknowledged);
});

// Utility functions
const formatBytes = (bytes) => {
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if (bytes === 0) return '0 B';
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
};

const formatUptime = (seconds) => {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
};

const getStatusColor = (status) => {
    const colors = {
        healthy: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        warning: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
        critical: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        error: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[status] || colors.healthy;
};

const getStatusIcon = (status) => {
    const icons = {
        healthy: CheckCircleIcon,
        warning: ExclamationTriangleIcon,
        critical: XCircleIcon,
        error: XCircleIcon
    };
    return icons[status] || CheckCircleIcon;
};

const getLogLevelColor = (level) => {
    const colors = {
        error: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        warning: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
        info: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
        success: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400'
    };
    return colors[level] || colors.info;
};

const formatTimestamp = (timestamp) => {
    return new Date(timestamp).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

// Actions
const refreshMetrics = async () => {
    isLoading.value = true;

    // Simulate API call with random variations
    setTimeout(() => {
        // Update CPU usage
        systemMetrics.value.cpu.usage = Math.max(20, Math.min(90, systemMetrics.value.cpu.usage + (Math.random() - 0.5) * 10));

        // Update memory usage
        const memoryVariation = (Math.random() - 0.5) * 500;
        systemMetrics.value.memory.used = Math.max(4000, Math.min(14000, systemMetrics.value.memory.used + memoryVariation));

        // Update response times
        performanceMetrics.value.responseTime.current = Math.max(100, Math.min(500, performanceMetrics.value.responseTime.current + (Math.random() - 0.5) * 50));

        // Update service response times
        services.value.forEach(service => {
            service.responseTime = Math.max(1, service.responseTime + (Math.random() - 0.5) * 10);
            service.lastCheck = new Date();
        });

        lastUpdated.value = new Date();
        isLoading.value = false;
    }, 1000);
};

const acknowledgeAlert = (alertId) => {
    const alert = alerts.value.find(a => a.id === alertId);
    if (alert) {
        alert.acknowledged = true;
    }
};

const restartService = (serviceName) => {
    if (confirm(`Are you sure you want to restart ${serviceName}?`)) {
        alert(`Restarting ${serviceName}...`);
    }
};

const downloadLogs = () => {
    alert('Downloading system logs...');
};

const clearLogs = () => {
    if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
        systemLogs.value = [];
    }
};

// Lifecycle
onMounted(() => {
    refreshMetrics();

    if (autoRefresh.value) {
        refreshInterval.value = setInterval(refreshMetrics, 30000); // 30 seconds
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <div>
        <Head title="System Monitor" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 via-orange-600 to-yellow-600 dark:from-red-900 dark:via-orange-900 dark:to-yellow-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    🖥️ System Monitor
                                </h1>
                                <p class="text-xl text-white/90">
                                    Real-time system health and performance monitoring
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- System Health Indicator -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white">
                                    <div class="flex items-center gap-2 mb-1">
                                        <component
                                            :is="getStatusIcon(systemHealth.status)"
                                            class="w-5 h-5"
                                            :class="systemHealth.color === 'green' ? 'text-green-300' :
                                                   systemHealth.color === 'yellow' ? 'text-yellow-300' : 'text-red-300'"
                                        />
                                        <span class="font-bold">{{ systemHealth.status.toUpperCase() }}</span>
                                    </div>
                                    <div class="text-xs text-white/80">Last updated: {{ formatTimestamp(lastUpdated) }}</div>
                                </div>

                                <!-- Auto Refresh Toggle -->
                                <label class="flex items-center gap-2 bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white cursor-pointer">
                                    <input
                                        v-model="autoRefresh"
                                        type="checkbox"
                                        class="rounded border-white/20 text-white bg-transparent focus:ring-white/50"
                                    />
                                    <span class="text-sm">Auto Refresh</span>
                                </label>

                                <Link
                                    href="/admin/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Active Alerts -->
                    <div v-if="activeAlerts.length > 0" class="mb-8">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Active Alerts</h2>
                        <div class="space-y-3">
                            <div
                                v-for="alert in activeAlerts"
                                :key="alert.id"
                                :class="`p-4 rounded-xl border-l-4 ${
                                    alert.type === 'critical' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' :
                                    alert.type === 'warning' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' :
                                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                }`"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ alert.title }}</h3>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ alert.message }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ formatTimestamp(alert.timestamp) }}</p>
                                    </div>
                                    <button
                                        @click="acknowledgeAlert(alert.id)"
                                        class="px-3 py-1 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors"
                                    >
                                        Acknowledge
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Metrics Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- CPU Usage -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                        <CpuChipIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">CPU Usage</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ systemMetrics.cpu.cores }} cores</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ systemMetrics.cpu.usage.toFixed(1) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: systemMetrics.cpu.usage + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span>Load: {{ systemMetrics.cpu.loadAverage.join(', ') }}</span>
                                <span>Temp: {{ systemMetrics.cpu.temperature }}°C</span>
                            </div>
                        </div>

                        <!-- Memory Usage -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                        <CircleStackIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Memory</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ formatBytes(systemMetrics.memory.used * 1024 * 1024) }} / {{ formatBytes(systemMetrics.memory.total * 1024 * 1024) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ memoryUsagePercent.toFixed(1) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="bg-green-600 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: memoryUsagePercent + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span>Free: {{ formatBytes(systemMetrics.memory.free * 1024 * 1024) }}</span>
                                <span>Cached: {{ formatBytes(systemMetrics.memory.cached * 1024 * 1024) }}</span>
                            </div>
                        </div>

                        <!-- Storage Usage -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                        <ServerIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Storage</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ systemMetrics.storage.used }}GB / {{ systemMetrics.storage.total }}GB
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ storageUsagePercent.toFixed(1) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    class="bg-purple-600 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: storageUsagePercent + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span>Free: {{ systemMetrics.storage.free }}GB</span>
                                <span>{{ systemMetrics.storage.partitions.length }} partitions</span>
                            </div>
                        </div>

                        <!-- Performance -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                                        <BoltIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Response Time</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Current avg</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ performanceMetrics.responseTime.current }}ms
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>P95:</span>
                                    <span>{{ performanceMetrics.responseTime.p95 }}ms</span>
                                </div>
                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>P99:</span>
                                    <span>{{ performanceMetrics.responseTime.p99 }}ms</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Services Status -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ShieldCheckIcon class="w-6 h-6 text-blue-600" />
                                        Service Status
                                    </h2>
                                    <button
                                        @click="refreshMetrics"
                                        :disabled="isLoading"
                                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                                    >
                                        <ArrowPathIcon :class="`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`" />
                                        Refresh
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        v-for="service in services"
                                        :key="service.name"
                                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                                    >
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ service.name }}</h3>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(service.status)}`">
                                                        <component :is="getStatusIcon(service.status)" class="w-3 h-3 inline mr-1" />
                                                        {{ service.status }}
                                                    </span>
                                                    <span v-if="service.port" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Port {{ service.port }}
                                                    </span>
                                                </div>
                                            </div>

                                            <button
                                                @click="restartService(service.name)"
                                                v-if="service.status !== 'healthy'"
                                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                                            >
                                                Restart
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Response:</span>
                                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ service.responseTime }}ms</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Uptime:</span>
                                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ service.uptime }}%</span>
                                            </div>
                                            <div v-if="service.connections">
                                                <span class="text-gray-600 dark:text-gray-400">Connections:</span>
                                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ service.connections }}</span>
                                            </div>
                                            <div v-if="service.hitRate">
                                                <span class="text-gray-600 dark:text-gray-400">Hit Rate:</span>
                                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ service.hitRate }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Metrics -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <ChartBarIcon class="w-6 h-6 text-green-600" />
                                    Performance Metrics
                                </h2>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Throughput -->
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                            {{ performanceMetrics.throughput.requestsPerSecond }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Requests/sec</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ performanceMetrics.throughput.queriesPerSecond }} queries/sec
                                        </div>
                                    </div>

                                    <!-- Error Rate -->
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                            {{ (performanceMetrics.errors.rate * 100).toFixed(2) }}%
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Error Rate</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ performanceMetrics.errors.count }} errors/hour
                                        </div>
                                    </div>

                                    <!-- Availability -->
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                                            {{ performanceMetrics.availability.uptime }}%
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Availability</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ formatUptime(performanceMetrics.availability.uptimeSeconds) }} uptime
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Logs -->
                        <div>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <DocumentTextIcon class="w-6 h-6 text-purple-600" />
                                        System Logs
                                    </h2>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="downloadLogs"
                                            class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700"
                                        >
                                            Download
                                        </button>
                                        <button
                                            @click="clearLogs"
                                            class="text-sm text-red-600 dark:text-red-400 hover:text-red-700"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    <div
                                        v-for="log in systemLogs"
                                        :key="log.id"
                                        class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                                    >
                                        <div class="flex items-start justify-between mb-2">
                                            <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getLogLevelColor(log.level)}`">
                                                {{ log.level.toUpperCase() }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatTimestamp(log.timestamp) }}
                                            </span>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            [{{ log.service }}] {{ log.message }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ log.details }}
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

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Pulse animation for status indicators */
@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}
</style>
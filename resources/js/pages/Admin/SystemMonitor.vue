<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CpuChipIcon,
    CircleStackIcon,
    ServerIcon,
    BoltIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({
            cpu: { load1: 0, load5: 0, load15: 0, cores: 1 },
            memory: { usage: 0, peak: 0 },
            storage: { usedPercent: 0, free: '0 B', total: '0 B' },
            services: { database: 'down', queue: '-', cache: '-', aiProvider: '-' },
            app: { php: '-', laravel: '-', environment: '-', uptimePercent: 0 },
        }),
    },
});

const isRefreshing = ref(false);
const autoRefresh = ref(false);
let timer = null;

// CPU load relative to core count — a load == cores is ~100% utilization.
const cpuLoadPercent = computed(() => {
    const cores = props.metrics.cpu.cores || 1;
    return Math.min(100, Math.round((props.metrics.cpu.load1 / cores) * 100));
});

const barColor = (pct) => {
    if (pct >= 85) return 'bg-red-500';
    if (pct >= 65) return 'bg-yellow-500';
    return 'bg-green-500';
};

const serviceUp = (value) => value === 'operational';

const refresh = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['metrics'],
        onFinish: () => { isRefreshing.value = false; },
    });
};

const toggleAuto = () => {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        timer = setInterval(refresh, 10000);
    } else if (timer) {
        clearInterval(timer);
        timer = null;
    }
};

onMounted(() => {});
onUnmounted(() => { if (timer) clearInterval(timer); });
</script>

<template>
    <div>
        <Head title="System Monitor" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-slate-700 via-gray-800 to-slate-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">🖥️ System Monitor</h1>
                                <p class="text-xl text-white/90">Live server and service health</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="toggleAuto"
                                    :class="`px-4 py-2 backdrop-blur-lg border border-white/20 rounded-xl text-white transition-all ${autoRefresh ? 'bg-green-500/40' : 'bg-white/20 hover:bg-white/30'}`"
                                >
                                    Auto-refresh: {{ autoRefresh ? 'On' : 'Off' }}
                                </button>
                                <button
                                    @click="refresh"
                                    class="px-4 py-2 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white hover:bg-white/30 transition-all flex items-center gap-2"
                                >
                                    <ArrowPathIcon :class="`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`" />
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

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6 space-y-8">
                    <!-- Resource gauges -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- CPU -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <CpuChipIcon class="w-6 h-6 text-blue-600" />
                                <h2 class="font-bold text-gray-900 dark:text-white">CPU Load</h2>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ cpuLoadPercent }}%</div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
                                <div :class="`h-2 rounded-full transition-all ${barColor(cpuLoadPercent)}`" :style="{ width: cpuLoadPercent + '%' }"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                                <div><div class="text-gray-500">1m</div><div class="font-semibold text-gray-900 dark:text-white">{{ metrics.cpu.load1 }}</div></div>
                                <div><div class="text-gray-500">5m</div><div class="font-semibold text-gray-900 dark:text-white">{{ metrics.cpu.load5 }}</div></div>
                                <div><div class="text-gray-500">15m</div><div class="font-semibold text-gray-900 dark:text-white">{{ metrics.cpu.load15 }}</div></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">{{ metrics.cpu.cores }} cores</p>
                        </div>

                        <!-- Memory -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <BoltIcon class="w-6 h-6 text-purple-600" />
                                <h2 class="font-bold text-gray-900 dark:text-white">Memory (PHP)</h2>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ metrics.memory.usage }} MB</div>
                            <p class="text-sm text-gray-500">Peak this request: {{ metrics.memory.peak }} MB</p>
                        </div>

                        <!-- Storage -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <CircleStackIcon class="w-6 h-6 text-orange-600" />
                                <h2 class="font-bold text-gray-900 dark:text-white">Storage</h2>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ metrics.storage.usedPercent }}%</div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
                                <div :class="`h-2 rounded-full transition-all ${barColor(metrics.storage.usedPercent)}`" :style="{ width: metrics.storage.usedPercent + '%' }"></div>
                            </div>
                            <p class="text-sm text-gray-500">{{ metrics.storage.free }} free of {{ metrics.storage.total }}</p>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <ServerIcon class="w-6 h-6 text-indigo-600" /> Services
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <span class="font-medium text-gray-900 dark:text-white">Database</span>
                                <span :class="`inline-flex items-center gap-1 text-sm font-medium ${serviceUp(metrics.services.database) ? 'text-green-600' : 'text-red-600'}`">
                                    <component :is="serviceUp(metrics.services.database) ? CheckCircleIcon : XCircleIcon" class="w-4 h-4" />
                                    {{ metrics.services.database }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <span class="font-medium text-gray-900 dark:text-white">Queue</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ metrics.services.queue }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <span class="font-medium text-gray-900 dark:text-white">Cache</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ metrics.services.cache }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <span class="font-medium text-gray-900 dark:text-white">AI Provider</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ metrics.services.aiProvider }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- App info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Environment</h2>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <div class="text-gray-500">PHP</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ metrics.app.php }}</div>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <div class="text-gray-500">Laravel</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ metrics.app.laravel }}</div>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <div class="text-gray-500">Environment</div>
                                <div class="font-semibold text-gray-900 dark:text-white capitalize">{{ metrics.app.environment }}</div>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                <div class="text-gray-500">Uptime</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ metrics.app.uptimePercent }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

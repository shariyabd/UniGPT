<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    CpuChipIcon,
    CircleStackIcon,
    ServerIcon,
    ServerStackIcon,
    BoltIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    ArrowLeftIcon,
    CommandLineIcon,
    CloudIcon,
    SparklesIcon,
    QueueListIcon,
    CircleStackIcon as DbIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({
            cpu: { load1: 0, load5: 0, load15: 0, cores: 1 },
            memory: { usage: 0, peak: 0 },
            storage: { usedPercent: 0, free: '0 B', total: '0 B' },
            services: {
                database: 'down',
                queue: { driver: '-', status: 'down' },
                cache: { driver: '-', status: 'down' },
                aiProvider: { name: '-', status: 'unconfigured' },
            },
            app: { php: '-', laravel: '-', environment: '-', uptime: 'n/a' },
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
    if (pct >= 85) return 'bg-danger-fg';
    if (pct >= 65) return 'bg-warning-fg';
    return 'bg-success-fg';
};

const serviceUp = (value) => value === 'operational';

// Overall health derived from key signals.
const dbHealthy = computed(() => serviceUp(props.metrics.services.database));
const healthState = computed(() => {
    if (!dbHealthy.value) return 'down';
    if (cpuLoadPercent.value >= 85 || props.metrics.storage.usedPercent >= 85) return 'warning';
    return 'healthy';
});
const healthMeta = computed(() => ({
    healthy: { color: 'emerald', label: 'Healthy', variant: 'success' },
    warning: { color: 'amber', label: 'Degraded', variant: 'warning' },
    down: { color: 'rose', label: 'Down', variant: 'danger' },
})[healthState.value]);

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
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="System Monitor"
                    subtitle="Live server and service health"
                    :icon="ServerStackIcon"
                >
                    <template #actions>
                        <button
                            type="button"
                            @click="toggleAuto"
                            class="ui-btn-secondary"
                            :class="autoRefresh ? 'ring-2 ring-primary/20' : ''"
                            :aria-pressed="autoRefresh"
                        >
                            <BoltIcon class="h-4 w-4" :class="autoRefresh ? 'text-primary' : ''" />
                            Auto-refresh: {{ autoRefresh ? 'On' : 'Off' }}
                        </button>
                        <button
                            type="button"
                            @click="refresh"
                            class="ui-btn-primary"
                        >
                            <ArrowPathIcon class="h-4 w-4" :class="isRefreshing ? 'animate-spin' : ''" />
                            Refresh
                        </button>
                        <Link href="/admin/dashboard" class="ui-btn-ghost">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Status overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="System Health"
                        :value="healthMeta.label"
                        :icon="ServerStackIcon"
                        :color="healthMeta.color"
                        :hint="dbHealthy ? 'Database operational' : 'Database unreachable'"
                    />
                    <StatCard
                        label="CPU Load"
                        :value="`${cpuLoadPercent}%`"
                        :icon="CpuChipIcon"
                        :color="cpuLoadPercent >= 85 ? 'rose' : cpuLoadPercent >= 65 ? 'amber' : 'violet'"
                        :hint="`${metrics.cpu.cores} cores`"
                    />
                    <StatCard
                        label="Storage Used"
                        :value="`${metrics.storage.usedPercent}%`"
                        :icon="CircleStackIcon"
                        :color="metrics.storage.usedPercent >= 85 ? 'rose' : metrics.storage.usedPercent >= 65 ? 'amber' : 'violet'"
                        :hint="`${metrics.storage.free} free of ${metrics.storage.total}`"
                    />
                    <StatCard
                        label="System Uptime"
                        :value="metrics.app.uptime"
                        :icon="BoltIcon"
                        color="violet"
                        :hint="`PHP memory: ${metrics.memory.usage} MB`"
                    />
                </div>

                <!-- Resource usage -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                    <!-- CPU -->
                    <Card title="CPU Load" :icon="CpuChipIcon">
                        <div class="text-stat text-content mb-2">{{ cpuLoadPercent }}%</div>
                        <div class="w-full bg-neutral-bg rounded-full h-2 mb-4 overflow-hidden">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="barColor(cpuLoadPercent)"
                                :style="{ width: cpuLoadPercent + '%' }"
                            ></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-sm">
                            <div>
                                <div class="text-content-muted">1m</div>
                                <div class="font-semibold text-content">{{ metrics.cpu.load1 }}</div>
                            </div>
                            <div>
                                <div class="text-content-muted">5m</div>
                                <div class="font-semibold text-content">{{ metrics.cpu.load5 }}</div>
                            </div>
                            <div>
                                <div class="text-content-muted">15m</div>
                                <div class="font-semibold text-content">{{ metrics.cpu.load15 }}</div>
                            </div>
                        </div>
                        <p class="text-xs text-content-muted mt-3">{{ metrics.cpu.cores }} cores</p>
                    </Card>

                    <!-- Memory -->
                    <Card title="Memory (PHP)" :icon="BoltIcon">
                        <div class="text-stat text-content mb-2">{{ metrics.memory.usage }} MB</div>
                        <p class="text-sm text-content-muted">Peak this request: {{ metrics.memory.peak }} MB</p>
                    </Card>

                    <!-- Storage -->
                    <Card title="Storage" :icon="CircleStackIcon">
                        <div class="text-stat text-content mb-2">{{ metrics.storage.usedPercent }}%</div>
                        <div class="w-full bg-neutral-bg rounded-full h-2 mb-4 overflow-hidden">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="barColor(metrics.storage.usedPercent)"
                                :style="{ width: metrics.storage.usedPercent + '%' }"
                            ></div>
                        </div>
                        <p class="text-sm text-content-muted">{{ metrics.storage.free }} free of {{ metrics.storage.total }}</p>
                    </Card>
                </div>

                <!-- Services -->
                <Card title="Services" :icon="ServerIcon">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex items-center justify-between p-4 rounded-card bg-bg border border-line">
                            <span class="flex items-center gap-2 font-medium text-content">
                                <DbIcon class="h-4 w-4 text-primary" />
                                Database
                            </span>
                            <Badge :variant="serviceUp(metrics.services.database) ? 'success' : 'danger'" dot>
                                <component :is="serviceUp(metrics.services.database) ? CheckCircleIcon : XCircleIcon" class="h-3.5 w-3.5" />
                                {{ metrics.services.database }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-card bg-bg border border-line">
                            <span class="flex items-center gap-2 font-medium text-content">
                                <QueueListIcon class="h-4 w-4 text-primary" />
                                Queue
                                <span class="text-xs text-content-muted">({{ metrics.services.queue.driver }})</span>
                            </span>
                            <Badge :variant="serviceUp(metrics.services.queue.status) ? 'success' : 'danger'" dot>
                                <component :is="serviceUp(metrics.services.queue.status) ? CheckCircleIcon : XCircleIcon" class="h-3.5 w-3.5" />
                                {{ metrics.services.queue.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-card bg-bg border border-line">
                            <span class="flex items-center gap-2 font-medium text-content">
                                <CloudIcon class="h-4 w-4 text-primary" />
                                Cache
                                <span class="text-xs text-content-muted">({{ metrics.services.cache.driver }})</span>
                            </span>
                            <Badge :variant="serviceUp(metrics.services.cache.status) ? 'success' : 'danger'" dot>
                                <component :is="serviceUp(metrics.services.cache.status) ? CheckCircleIcon : XCircleIcon" class="h-3.5 w-3.5" />
                                {{ metrics.services.cache.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-card bg-bg border border-line">
                            <span class="flex items-center gap-2 font-medium text-content">
                                <SparklesIcon class="h-4 w-4 text-primary" />
                                AI Provider
                                <span class="text-xs text-content-muted capitalize">({{ metrics.services.aiProvider.name }})</span>
                            </span>
                            <Badge :variant="serviceUp(metrics.services.aiProvider.status) ? 'success' : 'warning'" dot>
                                <component :is="serviceUp(metrics.services.aiProvider.status) ? CheckCircleIcon : XCircleIcon" class="h-3.5 w-3.5" />
                                {{ metrics.services.aiProvider.status }}
                            </Badge>
                        </div>
                    </div>
                </Card>

                <!-- App info -->
                <Card title="Environment" :icon="CommandLineIcon">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div class="p-4 rounded-card bg-bg border border-line">
                            <div class="text-content-muted">PHP</div>
                            <div class="font-semibold text-content">{{ metrics.app.php }}</div>
                        </div>
                        <div class="p-4 rounded-card bg-bg border border-line">
                            <div class="text-content-muted">Laravel</div>
                            <div class="font-semibold text-content">{{ metrics.app.laravel }}</div>
                        </div>
                        <div class="p-4 rounded-card bg-bg border border-line">
                            <div class="text-content-muted">Environment</div>
                            <div class="font-semibold capitalize text-content">{{ metrics.app.environment }}</div>
                        </div>
                        <div class="p-4 rounded-card bg-bg border border-line">
                            <div class="text-content-muted">System Uptime</div>
                            <div class="font-semibold text-content">{{ metrics.app.uptime }}</div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

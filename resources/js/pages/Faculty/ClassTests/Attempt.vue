<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ShieldCheckIcon,
    ArrowLeftIcon,
    FlagIcon,
    NoSymbolIcon,
    ClockIcon,
    VideoCameraIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    test: { type: Object, required: true },
    attempt: { type: Object, required: true },
    events: { type: Array, default: () => [] },
    eventCounts: { type: Object, default: () => ({}) },
    recordings: { type: Array, default: () => [] },
});

const statusVariant = (status) => ({
    submitted: 'success',
    expired: 'warning',
    disqualified: 'danger',
    in_progress: 'slate',
}[status] ?? 'slate');

const riskVariant = (score) => {
    if (score == null) return 'slate';
    if (score >= 70) return 'danger';
    if (score >= 40) return 'warning';
    return 'success';
};

const severityColor = (severity) => ({
    violation: 'text-danger-fg',
    warning: 'text-warning-fg',
    info: 'text-content-muted',
}[severity] ?? 'text-content-muted');

const fingerprintRows = computed(() => {
    const fp = props.attempt.fingerprint ?? {};
    return Object.entries(fp).filter(([, v]) => v != null && v !== '');
});

// --- recording playback -----------------------------------------------------
// MediaRecorder timeslice chunks are only valid webm when concatenated in
// order, so we fetch every chunk and stitch them into one Blob before playing.
const videoUrls = reactive({});
const loading = reactive({});

const loadRecording = async (recording) => {
    if (loading[recording.kind]) return;
    loading[recording.kind] = true;
    try {
        const blobs = [];
        for (const chunk of recording.chunks) {
            const { data } = await window.axios.get(chunk.url, { responseType: 'blob' });
            blobs.push(data);
        }
        videoUrls[recording.kind] = URL.createObjectURL(new Blob(blobs, { type: 'video/webm' }));
    } catch {
        toast.error('Could not load the recording.');
    } finally {
        loading[recording.kind] = false;
    }
};

const kindLabel = (kind) => (kind === 'screen' ? 'Screen' : 'Webcam');
const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`;
};
</script>

<template>
    <div>
        <Head :title="`Review — ${props.attempt.student.name}`" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <Link :href="route('faculty.class-tests.results', props.test.id)" class="ui-btn-ghost w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to results
                </Link>

                <PageHeader
                    :title="props.attempt.student.name"
                    :subtitle="`${props.attempt.student.studentId ?? ''} · ${props.test.title} · ${props.test.course.code}`"
                    :icon="ShieldCheckIcon"
                />

                <!-- Summary -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Card>
                        <div class="text-xs text-content-muted">Status</div>
                        <Badge :variant="statusVariant(props.attempt.status)" class="mt-1">{{ props.attempt.status }}</Badge>
                    </Card>
                    <Card>
                        <div class="text-xs text-content-muted">Score</div>
                        <div class="mt-1 text-lg font-bold text-content">{{ props.attempt.score }} / {{ props.attempt.totalMarks }}</div>
                    </Card>
                    <StatCard variant="filled" color="danger" label="Warnings" :value="props.attempt.violationCount" :icon="NoSymbolIcon" />
                    <Card>
                        <div class="text-xs text-content-muted">Risk score</div>
                        <Badge :variant="riskVariant(props.attempt.riskScore)" class="mt-1">
                            {{ props.attempt.riskScore == null ? 'not analysed' : `${props.attempt.riskScore} / 100` }}
                        </Badge>
                    </Card>
                </div>

                <!-- Risk factors -->
                <Card v-if="props.attempt.riskFactors && props.attempt.riskFactors.length" title="Risk factors" :icon="FlagIcon">
                    <ul class="space-y-2">
                        <li v-for="(factor, i) in props.attempt.riskFactors" :key="i" class="flex items-start justify-between gap-4 text-sm">
                            <div>
                                <span class="font-medium text-content">{{ factor.label }}</span>
                                <span class="ml-2 text-content-muted">{{ factor.detail }}</span>
                            </div>
                            <span class="text-content-faint">+{{ factor.weight }}</span>
                        </li>
                    </ul>
                </Card>

                <!-- Identity & device -->
                <Card title="Identity & device">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-4"><dt class="text-content-muted">Started</dt><dd class="text-content">{{ props.attempt.startedAt ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-content-muted">Submitted</dt><dd class="text-content">{{ props.attempt.submittedAt ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-content-muted">IP address</dt><dd class="font-mono text-content">{{ props.attempt.ip ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-content-muted">Session</dt><dd class="font-mono text-content">{{ props.attempt.sessionId ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-content-muted">Fingerprint</dt><dd class="font-mono text-content">{{ props.attempt.fingerprintHash ?? '—' }}</dd></div>
                        <div class="col-span-full flex justify-between gap-4"><dt class="shrink-0 text-content-muted">User agent</dt><dd class="truncate text-content" :title="props.attempt.userAgent">{{ props.attempt.userAgent ?? '—' }}</dd></div>
                    </dl>

                    <details v-if="fingerprintRows.length" class="mt-3">
                        <summary class="cursor-pointer text-xs text-content-muted">Fingerprint components</summary>
                        <dl class="mt-2 grid grid-cols-1 gap-x-8 gap-y-1 text-xs sm:grid-cols-2">
                            <div v-for="[key, value] in fingerprintRows" :key="key" class="flex justify-between gap-4">
                                <dt class="text-content-muted">{{ key }}</dt>
                                <dd class="truncate font-mono text-content" :title="String(value)">{{ value }}</dd>
                            </div>
                        </dl>
                    </details>
                </Card>

                <!-- Recordings -->
                <Card v-if="recordings.length" title="Recordings" :icon="VideoCameraIcon">
                    <div class="space-y-5">
                        <div v-for="rec in recordings" :key="rec.kind">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-content">{{ kindLabel(rec.kind) }}</span>
                                <span class="text-xs text-content-faint">{{ rec.chunkCount }} chunk(s) · {{ formatBytes(rec.sizeBytes) }}</span>
                            </div>
                            <video v-if="videoUrls[rec.kind]" :src="videoUrls[rec.kind]" controls class="w-full rounded-card border border-line" />
                            <button v-else type="button" class="ui-btn-ghost" :disabled="loading[rec.kind]" @click="loadRecording(rec)">
                                <VideoCameraIcon class="h-4 w-4" />
                                {{ loading[rec.kind] ? 'Loading…' : 'Load & play recording' }}
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Behaviour timeline -->
                <Card title="Activity timeline" :icon="ClockIcon" padding="p-0">
                    <div class="flex flex-wrap gap-2 border-b border-line px-4 py-3 text-xs">
                        <Badge variant="danger">{{ eventCounts.violation ?? 0 }} violations</Badge>
                        <Badge variant="warning">{{ eventCounts.warning ?? 0 }} warnings</Badge>
                        <Badge variant="slate">{{ eventCounts.info ?? 0 }} activity</Badge>
                    </div>

                    <EmptyState
                        v-if="events.length === 0"
                        title="No events recorded"
                        description="This attempt did not log any behaviour events."
                        :icon="ClockIcon"
                    />
                    <div v-else class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-line">
                                <tr v-for="(event, i) in events" :key="i">
                                    <td class="whitespace-nowrap px-4 py-2 text-content-faint">{{ event.occurredAt }}</td>
                                    <td class="px-4 py-2">
                                        <span class="font-medium" :class="severityColor(event.severity)">{{ event.type }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-content-muted">
                                        <span v-if="event.durationMs">{{ Math.round(event.durationMs / 1000) }}s</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

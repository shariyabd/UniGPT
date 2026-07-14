<script setup>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue';
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
    CameraIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    test: { type: Object, required: true },
    attempt: { type: Object, required: true },
    events: { type: Array, default: () => [] },
    eventCounts: { type: Object, default: () => ({}) },
    recordings: { type: Array, default: () => [] },
    snapshots: { type: Array, default: () => [] },
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

// Face-liveness events get friendly wording; other types read fine raw.
const TYPE_LABELS = {
    face_verified: 'face verified (liveness gate passed)',
    face_lost: 'face lost > grace period',
    face_restored: 'face back in frame',
    face_liveness_violation: 'face lost — violation',
    face_liveness_unavailable: 'liveness detector unavailable',
    face_verification_bypassed: 'skipped face verification (review evidence)',
    no_blink_suspicion: 'no blink detected — possible photo',
    phone_detected: 'phone seen in frame',
    multiple_faces: 'second face in frame',
    phone_detection_unavailable: 'phone detector unavailable',
};
const typeLabel = (type) => TYPE_LABELS[type] ?? type;

// --- snapshot evidence -------------------------------------------------------
const TRIGGER_META = {
    violation: { label: 'Violation', variant: 'danger' },
    face_lost: { label: 'Face lost', variant: 'warning' },
    phone_detected: { label: 'Phone', variant: 'danger' },
    multiple_faces: { label: 'Second face', variant: 'danger' },
    identity: { label: 'Identity', variant: 'success' },
    periodic: { label: 'Periodic', variant: 'slate' },
};
const triggerMeta = (trigger) => TRIGGER_META[trigger] ?? { label: trigger, variant: 'slate' };

// Full-size viewer is a slider over the whole strip: arrows/keyboard navigate,
// index wraps at the ends, Esc or backdrop click closes.
const openIndex = ref(null);
const openSnapshot = computed(() => (openIndex.value === null ? null : props.snapshots[openIndex.value] ?? null));
const flaggedSnapshotCount = computed(() => props.snapshots.filter((s) => s.trigger !== 'periodic').length);

const stepSnapshot = (delta) => {
    if (openIndex.value === null || !props.snapshots.length) return;
    openIndex.value = (openIndex.value + delta + props.snapshots.length) % props.snapshots.length;
};

const onViewerKeys = (e) => {
    if (e.key === 'Escape') openIndex.value = null;
    else if (e.key === 'ArrowLeft') stepSnapshot(-1);
    else if (e.key === 'ArrowRight') stepSnapshot(1);
};

watch(openIndex, (open, was) => {
    if (open !== null && was === null) window.addEventListener('keydown', onViewerKeys);
    else if (open === null) window.removeEventListener('keydown', onViewerKeys);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onViewerKeys));

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

                <!-- Snapshot evidence (photo strip) -->
                <Card v-if="snapshots.length" title="Snapshot evidence" :icon="CameraIcon">
                    <p class="mb-3 text-xs text-content-muted">
                        {{ snapshots.length }} photo(s) — {{ flaggedSnapshotCount }} at flagged moments, the rest random samples.
                        Captured on-device instead of continuous recording.
                    </p>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        <button
                            v-for="(shot, si) in snapshots"
                            :key="shot.id"
                            type="button"
                            class="group relative overflow-hidden rounded-card border border-line text-left"
                            @click="openIndex = si"
                        >
                            <img :src="shot.url" :alt="`Snapshot ${shot.sequence} — ${shot.trigger}`" loading="lazy" class="aspect-video w-full object-cover transition-transform group-hover:scale-105" />
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-black/60 px-1.5 py-1">
                                <Badge :variant="triggerMeta(shot.trigger).variant">{{ triggerMeta(shot.trigger).label }}</Badge>
                                <span class="text-[10px] text-white/80">{{ (shot.capturedAt || '').slice(11, 16) }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Full-size viewer. Teleported to <body>: the layout's <main>
                         keeps a transform from its fade-in animation, which turns it
                         into the containing block for position:fixed — the overlay
                         must escape it to actually cover the viewport. -->
                    <Teleport to="body">
                        <div
                            v-if="openSnapshot"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
                            role="dialog"
                            aria-modal="true"
                            @click="openIndex = null"
                        >
                            <button
                                type="button"
                                class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/25"
                                aria-label="Close viewer"
                                @click.stop="openIndex = null"
                            >
                                <XMarkIcon class="h-6 w-6" />
                            </button>

                            <button
                                v-if="snapshots.length > 1"
                                type="button"
                                class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2.5 text-white transition-colors hover:bg-white/25"
                                aria-label="Previous snapshot"
                                @click.stop="stepSnapshot(-1)"
                            >
                                <ChevronLeftIcon class="h-7 w-7" />
                            </button>

                            <div class="max-w-3xl" @click.stop>
                                <img :src="openSnapshot.url" :alt="`Snapshot ${openSnapshot.sequence}`" class="max-h-[80vh] w-auto rounded-card" />
                                <p class="mt-2 flex items-center justify-center gap-3 text-sm text-white/90">
                                    <Badge :variant="triggerMeta(openSnapshot.trigger).variant">{{ triggerMeta(openSnapshot.trigger).label }}</Badge>
                                    {{ openSnapshot.capturedAt }}
                                    <span class="tabular-nums text-white/60">{{ openIndex + 1 }} / {{ snapshots.length }}</span>
                                </p>
                            </div>

                            <button
                                v-if="snapshots.length > 1"
                                type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2.5 text-white transition-colors hover:bg-white/25"
                                aria-label="Next snapshot"
                                @click.stop="stepSnapshot(1)"
                            >
                                <ChevronRightIcon class="h-7 w-7" />
                            </button>
                        </div>
                    </Teleport>
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
                                        <span class="font-medium" :class="severityColor(event.severity)">{{ typeLabel(event.type) }}</span>
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

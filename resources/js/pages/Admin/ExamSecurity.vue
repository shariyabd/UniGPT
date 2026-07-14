<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { ShieldCheckIcon, BookOpenIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    layers: { type: Array, default: () => [] },
    maxWarningsDefault: { type: Number, default: 3 },
    guideConfig: { type: Object, default: () => ({}) },
});

const CATEGORY_META = {
    lockdown: 'Lockdown',
    integrity: 'Question integrity',
    monitoring: 'Monitoring & evidence',
    media: 'Media recording',
};
const CATEGORY_ORDER = ['lockdown', 'integrity', 'monitoring', 'media'];

// --- layer guide (offcanvas) -------------------------------------------------
const guideOpen = ref(false);
const g = props.guideConfig;

// Step-by-step flow for every layer, using the real config numbers so the
// guide never drifts from shipped behaviour. Keyed by registry layer key.
const LAYER_GUIDE = {
    fullscreen: {
        flow: [
            'When the student clicks "Begin test" the exam enters fullscreen.',
            'If the browser refuses fullscreen (or the student leaves it during face verification), the paper stays blurred behind a "Fullscreen required" gate until they enter — the timer keeps running, but nothing is counted.',
            'Exiting fullscreen mid-exam counts one warning and shows a blocking "return to fullscreen" dialog.',
            `Warnings from all layers share the test's limit (default ${props.maxWarningsDefault}); exceeding it auto-submits the attempt as disqualified with score 0.`,
        ],
    },
    tab_switch: {
        flow: [
            'Switching tabs, minimising, or clicking outside the exam window counts one warning (a single leave that fires several browser events is debounced to one).',
            'A blocking warning dialog shows the running count against the test\'s limit.',
            'Exceeding the limit auto-submits the attempt as disqualified (score 0).',
        ],
    },
    clipboard: {
        flow: [
            'Copy, cut, paste, drag and the right-click menu are disabled on the exam screen.',
            'Each attempt is logged as a review event in the attempt timeline — it does not consume a warning.',
        ],
    },
    sequential: {
        flow: [
            'The paper shows one question per screen with Next/Previous navigation instead of the full list.',
            'Per-question answer timing becomes more meaningful for risk scoring.',
        ],
    },
    lock_back: {
        flow: [
            'Requires "one question at a time".',
            'Once the student moves past a question they cannot navigate back to it.',
        ],
    },
    shuffle_questions: {
        flow: ['Each student receives the questions in a different random order, so answers can\'t be shared by position ("the answer to Q3 is B").'],
    },
    shuffle_options: {
        flow: ['Within each multiple-choice question, the options are shuffled per student.'],
    },
    watermark: {
        flow: [
            'The student\'s name, ID, session and a live clock are tiled faintly across the whole paper.',
            'Any screenshot or phone photo of the paper carries the student\'s identity — a strong deterrent to sharing it.',
        ],
    },
    integrity_notice: {
        flow: ['A notice asking AI tools not to supply answers is shown on the paper and repeated under every question, so it travels with any copied or photographed excerpt.'],
    },
    fingerprint: {
        flow: [
            'A browser/device fingerprint hash is captured when the attempt starts.',
            'Faculty sees it on the attempt review page — two "different students" with identical fingerprints is a strong signal.',
        ],
    },
    behavior_log: {
        flow: [
            'Focus loss, clicks, answer timing, idle periods and window resizes are logged in batches during the attempt.',
            'Faculty sees the full activity timeline on the attempt review page.',
        ],
    },
    risk_analysis: {
        flow: [
            'After submission, a 0–100 suspicion score is computed from the logged behaviour.',
            'Factors: implausibly fast answers, near-identical time per question, long idle stretches, frequent focus loss, no mouse movement, and repeated face loss (when face liveness is on).',
            'The score and its contributing factors appear on the attempt review page — a signal for human review, never an automatic verdict.',
        ],
    },
    webcam: {
        flow: [
            'The student must consent and grant camera access before the exam can begin (shown on the instructions page).',
            `Recording runs for the whole attempt, uploaded in ${g.recordingChunkSeconds ?? 15}-second chunks — a mid-exam disconnect still preserves earlier footage.`,
            `Bitrate is capped at ${g.recordingBitrateKbps ?? 250} kbps (~${Math.round((g.recordingBitrateKbps ?? 250) * 60 * 20 / 8 / 1024)} MB per 20-minute exam); recordings of finished attempts are auto-deleted after ${g.retentionDays ?? 30} days.`,
            'Stored privately; only faculty and admins can play it back from the attempt review page.',
            'Storage-heavy — for large cohorts consider "Snapshot evidence" instead, which captures flagged moments as photos.',
        ],
    },
    snapshot_evidence: {
        flow: [
            'Uses the camera without continuous recording — the student consents to camera access and photo capture.',
            `Photo bursts (${g.snapshotBurstCount ?? 3} frames) are captured on-device at flagged moments: proctoring violations, face loss, phone or second-face detections, and once at identity verification.`,
            `Random single photos are also taken every ${g.snapshotPeriodicMin ?? 60}–${g.snapshotPeriodicMax ?? 90}s (jittered, so capture moments can't be predicted).`,
            `Capped at ${g.snapshotMaxPerAttempt ?? 60} photos per attempt (~3–4 MB vs ~150+ MB of recording); auto-deleted after ${g.retentionDays ?? 30} days like recordings.`,
            'Faculty reviews the trigger-labelled photo strip on the attempt page next to the event timeline.',
        ],
    },
    phone_detection: {
        flow: [
            'An on-device AI model watches the webcam for a phone raised into view — the position a phone must be in to photograph the screen. No frames leave the browser.',
            `A detection only fires after ${g.phoneConsecutiveHits ?? 3} consecutive positive frames (~1–2s), then cools down for ${g.phoneCooldownSeconds ?? 30}s — filtering out phone-shaped false positives.`,
            'A confirmed detection logs a "phone seen in frame" event and captures a photo burst (when Snapshot evidence is on). It is a review signal, never an automatic violation — a human judges the photos.',
            'When face liveness is also on, a second face leaning into frame is flagged the same way ("second face in frame").',
            'Flagged attempts gain a "Phone / second person in frame" risk factor so they sort up for review.',
        ],
    },
    screen_record: {
        flow: [
            'The student must consent and share their screen/tab before the exam can begin.',
            `Same chunked recording and private playback as the webcam (${g.recordingChunkSeconds ?? 15}-second chunks).`,
        ],
    },
    face_liveness: {
        flow: [
            'Requires webcam recording (it runs on the same camera stream). Detection is fully in-browser — no video leaves the device for this check.',
            'Verification gate: the questions stay hidden until a live face AND one eye blink are detected. A printed photo can\'t blink. Works with face masks — blinks are read from eye movement, not the whole face.',
            `If verification keeps failing (heavily covered face, poor camera), after ${g.gateBypassSeconds ?? 30}s the student may continue without it — logged on the attempt for faculty review, and recording still runs.`,
            `During the exam the camera is checked continuously. No face for ${g.softWarningSeconds ?? 3}s → warning banner and the questions blur (unreadable/unphotographable). No penalty; everything restores the instant the face returns.`,
            `No face for ${g.graceSeconds ?? 8}s → the screen locks behind a warning overlay and the incident is counted. The first ${g.freeWarnings ?? 2} incidents are free warnings.`,
            `Every incident after that also counts as a violation toward the test's warning limit (default ${props.maxWarningsDefault}) — exceeding it auto-submits as disqualified (score 0).`,
            `A face that stays visible but never blinks for ${g.noBlinkSpoofSeconds ?? 90}s is flagged as a possible photo — a review signal in the timeline, no penalty.`,
        ],
    },
};

const guideGroups = computed(() =>
    CATEGORY_ORDER
        .map((category) => ({
            key: category,
            label: CATEGORY_META[category] ?? category,
            layers: props.layers
                .filter((layer) => layer.category === category && LAYER_GUIDE[layer.key])
                .map((layer) => ({ ...layer, flow: LAYER_GUIDE[layer.key].flow })),
        }))
        .filter((group) => group.layers.length)
);

const form = useForm({
    layers: Object.fromEntries(
        props.layers.map((layer) => [layer.key, { available: layer.available, default: layer.default }])
    ),
});

const grouped = computed(() => {
    const groups = {};
    for (const layer of props.layers) {
        if (!groups[layer.category]) groups[layer.category] = [];
        groups[layer.category].push(layer);
    }
    return CATEGORY_ORDER
        .filter((c) => groups[c]?.length)
        .map((c) => ({ key: c, label: CATEGORY_META[c] ?? c, layers: groups[c] }));
});

const submit = () => {
    form.patch(route('admin.exam-security.update'), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Exam Security" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <PageHeader
                        title="Exam security"
                        subtitle="Control which proctoring layers faculty can apply to class tests, and which are on by default."
                        :icon="ShieldCheckIcon"
                    />
                    <button type="button" class="ui-btn-ghost" @click="guideOpen = true">
                        <BookOpenIcon class="h-4 w-4" /> How each layer works
                    </button>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <Card v-for="group in grouped" :key="group.key" :title="group.label">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-line text-left text-content-muted">
                                        <th class="py-2 pr-4 font-medium">Layer</th>
                                        <th class="w-28 py-2 px-2 text-center font-medium">Available</th>
                                        <th class="w-28 py-2 px-2 text-center font-medium">On by default</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    <tr v-for="layer in group.layers" :key="layer.key">
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center gap-2 font-medium text-content">
                                                {{ layer.label }}
                                                <span v-if="layer.media" class="ui-badge bg-warning-soft text-warning-fg">consent</span>
                                            </div>
                                            <div class="text-xs text-content-muted">{{ layer.description }}</div>
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <input v-model="form.layers[layer.key].available" type="checkbox" class="h-4 w-4 rounded border-line" />
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <input
                                                v-model="form.layers[layer.key].default"
                                                type="checkbox"
                                                :disabled="!form.layers[layer.key].available"
                                                class="h-4 w-4 rounded border-line disabled:opacity-40"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-content-faint">
                            Disabling a layer's availability hides it from the faculty authoring form. Tests already
                            using a now-unavailable layer stop applying it. Default warnings before disqualification:
                            <strong>{{ maxWarningsDefault }}</strong> (set per test by faculty).
                        </p>
                        <button type="submit" :disabled="form.processing" class="ui-btn-primary">Save settings</button>
                    </div>
                </form>
            </div>

            <!-- Layer guide offcanvas -->
            <Teleport to="body">
                <Transition name="guide-fade">
                    <div
                        v-if="guideOpen"
                        class="fixed inset-0 z-40 bg-black/40"
                        aria-hidden="true"
                        @click="guideOpen = false"
                    />
                </Transition>
                <Transition name="guide-slide">
                    <aside
                        v-if="guideOpen"
                        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col bg-surface shadow-card"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Exam security layer guide"
                        @keydown.esc="guideOpen = false"
                    >
                        <div class="flex items-center justify-between border-b border-line px-6 py-4">
                            <div>
                                <h2 class="flex items-center gap-2 text-lg font-bold text-content">
                                    <BookOpenIcon class="h-5 w-5 text-primary" /> How each layer works
                                </h2>
                                <p class="text-xs text-content-muted">
                                    Step-by-step behaviour of every proctoring layer, with the currently configured timings.
                                </p>
                            </div>
                            <button type="button" class="ui-btn-ghost !px-2" aria-label="Close guide" @click="guideOpen = false">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="flex-1 space-y-8 overflow-y-auto px-6 py-5">
                            <!-- Shared warning/disqualification model -->
                            <section>
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-content-muted">Warnings & disqualification</h3>
                                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-content">
                                    <li>Layers report two kinds of events: <strong>review signals</strong> (logged for faculty, no penalty) and <strong>violations</strong> (counted).</li>
                                    <li>All violations share one per-test warning limit (default <strong>{{ maxWarningsDefault }}</strong>, set by faculty on the test form).</li>
                                    <li>Exceeding the limit auto-submits the attempt as <strong>disqualified — score 0</strong>, even if the answers were correct.</li>
                                    <li>Alerts are audible: a soft double-beep for warnings and a harsher descending tone for blocking/violations — so a student who isn't looking at the screen (face out of frame) still hears it.</li>
                                    <li>Everything a layer records is visible on the faculty attempt-review page (timeline, recordings, risk score).</li>
                                </ol>
                            </section>

                            <section v-for="group in guideGroups" :key="group.key">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-content-muted">{{ group.label }}</h3>
                                <div class="mt-2 space-y-5">
                                    <div v-for="layer in group.layers" :key="layer.key" class="rounded-card border border-line p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-content">{{ layer.label }}</span>
                                            <span v-if="layer.media" class="ui-badge bg-warning-soft text-warning-fg">consent</span>
                                            <span v-if="!layer.available" class="ui-badge bg-neutral-bg text-content-muted">disabled</span>
                                        </div>
                                        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-content-muted">
                                            <li v-for="(step, i) in layer.flow" :key="i">{{ step }}</li>
                                        </ol>
                                    </div>
                                </div>
                            </section>

                            <p class="border-t border-line pt-4 text-xs text-content-faint">
                                Timings shown reflect the current server configuration. Every layer is a signal for
                                human review — recordings and the event timeline are the ground truth, not automatic verdicts.
                            </p>
                        </div>
                    </aside>
                </Transition>
            </Teleport>
        </AppLayout>
    </div>
</template>

<style scoped>
.guide-slide-enter-active,
.guide-slide-leave-active {
    transition: transform 0.25s ease;
}
.guide-slide-enter-from,
.guide-slide-leave-to {
    transform: translateX(100%);
}
.guide-fade-enter-active,
.guide-fade-leave-active {
    transition: opacity 0.25s ease;
}
.guide-fade-enter-from,
.guide-fade-leave-to {
    opacity: 0;
}
</style>

<script setup>
import { ref, computed, reactive, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import { collectFingerprint } from '@/composables/useBrowserFingerprint';
import { useExamEvents } from '@/composables/useExamEvents';
import { useExamRecorder } from '@/composables/useExamRecorder';

const props = defineProps({
    test: { type: Object, required: true },
    attempt: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
    security: {
        type: Object,
        default: () => ({ layers: {}, watermark: null, integrityNotice: null, recording: {} }),
    },
});

// --- which layers are live for this attempt ---------------------------------
const L = computed(() => props.security?.layers ?? {});
const useFullscreen = computed(() => !!L.value.fullscreen);
const useTab = computed(() => !!L.value.tab_switch);
const useClipboard = computed(() => !!L.value.clipboard);
const sequential = computed(() => !!L.value.sequential);
const lockBack = computed(() => !!L.value.lock_back);
const useWatermark = computed(() => !!L.value.watermark);
const useBehavior = computed(() => !!L.value.behavior_log);
const useFingerprint = computed(() => !!L.value.fingerprint);

// --- exam state -------------------------------------------------------------
const started = ref(false);
const finished = ref(false);
const answers = ref({});
const remaining = ref(Math.max(0, props.attempt.remainingSeconds));
const warningOpen = ref(false);
const warningCount = ref(0);
const rootEl = ref(null);
const clock = ref('');

const currentIndex = ref(0);
const enteredAt = reactive({});
let startedAt = Date.now();

let timer = null;
let lastViolationAt = 0;
let lastMouseLog = 0;
let lastClickLog = 0;

const answeredCount = computed(() => Object.values(answers.value).filter((v) => v != null && v !== '').length);
const minutes = computed(() => String(Math.floor(remaining.value / 60)).padStart(2, '0'));
const seconds = computed(() => String(remaining.value % 60).padStart(2, '0'));
const lowTime = computed(() => remaining.value <= 30);

// --- navigation (sequential vs all-at-once) ---------------------------------
const displayList = computed(() => (sequential.value ? [props.questions[currentIndex.value]] : props.questions));
const isFirst = computed(() => currentIndex.value === 0);
const isLast = computed(() => currentIndex.value === props.questions.length - 1);

const questionNumber = (localIndex) => (sequential.value ? currentIndex.value + 1 : localIndex + 1);

const enter = (question) => {
    if (question) enteredAt[question.id] = Date.now();
};

const goNext = () => {
    if (isLast.value) return;
    currentIndex.value += 1;
    enter(props.questions[currentIndex.value]);
};

const goPrev = () => {
    if (isFirst.value || lockBack.value) return;
    currentIndex.value -= 1;
    enter(props.questions[currentIndex.value]);
};

// --- events channel ---------------------------------------------------------
const events = useExamEvents({
    testId: props.test.id,
    onDisqualified: () => autoSubmit(true),
});

// --- media recording (webcam / screen) --------------------------------------
const mediaError = ref('');
const requestingMedia = ref(false);
const recorder = useExamRecorder({
    testId: props.test.id,
    recording: props.security?.recording ?? {},
    onEnded: (kind) => events.warn('recording_ended', { kind }),
});

const onAnswer = (question) => {
    events.markActivity();
    const base = enteredAt[question.id] ?? startedAt;
    events.log('answer', { key: answers.value[question.id] }, {
        questionId: question.id,
        durationMs: Math.max(0, Date.now() - base),
    });
};

// --- integrity notice (shown in the sticky exam header) ---------------------
const integrityNotice = computed(() => props.security?.integrityNotice ?? null);

// --- watermark --------------------------------------------------------------
const watermarkText = computed(() => {
    const w = props.security?.watermark;
    if (!w) return '';
    const shortSession = (w.sessionId ?? '').slice(0, 8);
    return [w.name, w.studentId, shortSession, clock.value].filter(Boolean).join('  ·  ');
});

// --- fullscreen -------------------------------------------------------------
const requestFullscreen = () => {
    const el = rootEl.value ?? document.documentElement;
    if (el.requestFullscreen) return el.requestFullscreen().catch(() => {});
    return Promise.resolve();
};

const exitFullscreen = () => {
    if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
    }
};

// --- lifecycle --------------------------------------------------------------
const begin = async () => {
    // Media consent must be granted up front; a required stream that is denied
    // blocks the exam from starting (institution policy).
    if (recorder.needsMedia) {
        requestingMedia.value = true;
        mediaError.value = '';
        const result = await recorder.requestPermissions();
        requestingMedia.value = false;
        if (!result.ok) {
            mediaError.value = 'Camera / screen access is required for this exam. Please allow it and try again.';
            return;
        }
    }

    if (useFullscreen.value) await requestFullscreen();

    started.value = true;
    startedAt = Date.now();
    updateClock();
    captureFingerprint();
    recorder.start();
    attachGuards();
    events.start();
    startTimer();

    // Prime per-question entry times so answer timing is meaningful.
    if (sequential.value) {
        enter(props.questions[0]);
    } else {
        props.questions.forEach((q) => { enteredAt[q.id] = startedAt; });
    }

    if (remaining.value <= 0) autoSubmit(false);
};

const updateClock = () => {
    clock.value = new Date().toLocaleTimeString();
};

const startTimer = () => {
    timer = window.setInterval(() => {
        updateClock();
        if (useBehavior.value) events.checkIdle(30000);

        remaining.value -= 1;
        if (remaining.value <= 0) {
            remaining.value = 0;
            autoSubmit(false); // time expired — grade what was answered
        }
    }, 1000);
};

const captureFingerprint = () => {
    if (!useFingerprint.value) return;
    try {
        window.axios.post(route('class-tests.fingerprint', props.test.id), { components: collectFingerprint() });
    } catch {
        // fingerprint is best-effort; never block the exam
    }
};

// --- guards -----------------------------------------------------------------
const handleViolation = async (type) => {
    if (!started.value || finished.value) return;

    // A single "leave" can fire blur + visibilitychange together — debounce so it
    // counts as one violation.
    const now = Date.now();
    if (now - lastViolationAt < 1200) return;
    lastViolationAt = now;

    const data = await events.report(type);
    if (data?.disqualified) {
        autoSubmit(true);
        return;
    }
    warningCount.value = data?.violationCount ?? warningCount.value + 1;
    warningOpen.value = true;
};

const onVisibilityChange = () => { if (document.hidden) handleViolation('visibility_hidden'); };
const onFullscreenChange = () => { if (!document.fullscreenElement && started.value && !finished.value) handleViolation('fullscreen_exit'); };
const onBlurViolation = () => handleViolation('tab_blur');

const onClipboardBlock = (e) => {
    e.preventDefault();
    events.warn(e.type);
    return false;
};

const onFocusLossLog = () => events.log('focus_loss');
const onMouseMove = () => {
    events.markActivity();
    const t = Date.now();
    if (t - lastMouseLog > 15000) { lastMouseLog = t; events.log('mouse_move'); }
};
const onClick = (e) => {
    const t = Date.now();
    if (t - lastClickLog > 2000) { lastClickLog = t; events.log('click', { x: e.clientX, y: e.clientY }); }
};
const onResize = () => events.log('resize', { w: window.innerWidth, h: window.innerHeight });

const onBeforeUnload = (e) => {
    if (started.value && !finished.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

const attachGuards = () => {
    if (useTab.value) {
        document.addEventListener('visibilitychange', onVisibilityChange);
        window.addEventListener('blur', onBlurViolation);
    }
    if (useFullscreen.value) {
        document.addEventListener('fullscreenchange', onFullscreenChange);
    }
    if (useClipboard.value) {
        ['contextmenu', 'copy', 'cut', 'paste', 'dragstart', 'drop'].forEach((evt) =>
            document.addEventListener(evt, onClipboardBlock));
    }
    if (useBehavior.value) {
        window.addEventListener('mousemove', onMouseMove);
        document.addEventListener('click', onClick);
        window.addEventListener('resize', onResize);
        // If tab-switching isn't a hard violation, still record focus loss.
        if (!useTab.value) window.addEventListener('blur', onFocusLossLog);
    }
    window.addEventListener('beforeunload', onBeforeUnload);
};

const detachGuards = () => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    window.removeEventListener('blur', onBlurViolation);
    window.removeEventListener('blur', onFocusLossLog);
    ['contextmenu', 'copy', 'cut', 'paste', 'dragstart', 'drop'].forEach((evt) =>
        document.removeEventListener(evt, onClipboardBlock));
    window.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('click', onClick);
    window.removeEventListener('resize', onResize);
    window.removeEventListener('beforeunload', onBeforeUnload);
};

const resumeAfterWarning = async () => {
    warningOpen.value = false;
    if (useFullscreen.value) await requestFullscreen();
};

// --- submission -------------------------------------------------------------
const submitting = ref(false);

const finalise = async (disqualified) => {
    finished.value = true;
    submitting.value = true;
    if (timer) window.clearInterval(timer);
    detachGuards();
    recorder.stop();
    events.stop();
    await events.flush(); // don't lose the tail of the evidence trail

    router.post(route('class-tests.submit', props.test.id), {
        answers: answers.value,
        disqualified,
    }, {
        onFinish: () => exitFullscreen(),
    });
};

const autoSubmit = (disqualified) => {
    if (finished.value) return;
    finalise(disqualified);
};

const submitManually = () => {
    if (finished.value) return;
    finalise(false);
};

onMounted(() => {
    // If the deadline already passed when arriving here, submit immediately.
    if (remaining.value <= 0) {
        started.value = true;
        autoSubmit(false);
    }
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
    detachGuards();
    recorder.stop();
});
</script>

<template>
    <div ref="rootEl" class="exam-root min-h-screen select-none bg-bg">
        <Head :title="props.test.title" />

        <!-- Pre-start gate (needed for a user gesture to enter fullscreen / grant media) -->
        <div v-if="!started" class="flex min-h-screen items-center justify-center p-6">
            <div class="max-w-md rounded-card border border-line bg-surface p-8 text-center shadow-card">
                <ShieldCheckIcon class="mx-auto h-12 w-12 text-primary" />
                <h1 class="mt-4 text-xl font-bold text-content">{{ props.test.title }}</h1>
                <p class="mt-2 text-sm text-content-muted">
                    <template v-if="useFullscreen">The test will open in fullscreen and the timer will start. </template>
                    <template v-else>The timer will start as soon as you begin. </template>
                    <template v-if="useTab || useFullscreen">Leaving the exam screen may disqualify you.</template>
                    <template v-if="recorder.needsMedia"> You will be asked to allow camera / screen recording.</template>
                </p>
                <p v-if="mediaError" class="mt-4 rounded-control bg-danger-bg p-3 text-sm text-danger-fg">{{ mediaError }}</p>
                <button
                    type="button"
                    class="ui-btn-primary mt-6 w-full justify-center py-3 disabled:opacity-50"
                    :disabled="requestingMedia"
                    @click="begin"
                >
                    {{ requestingMedia ? 'Requesting access…' : 'Begin test' }}
                </button>
            </div>
        </div>

        <!-- Exam -->
        <div v-else class="relative mx-auto max-w-3xl px-4 py-6">
            <!-- Identity watermark -->
            <div
                v-if="useWatermark && watermarkText"
                class="pointer-events-none fixed inset-0 z-20 grid select-none overflow-hidden"
                style="grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(6, 1fr);"
                aria-hidden="true"
            >
                <div v-for="n in 18" :key="n" class="flex items-center justify-center">
                    <span class="-rotate-[30deg] whitespace-nowrap text-xs font-semibold text-content" style="opacity: 0.06;">
                        {{ watermarkText }}
                    </span>
                </div>
            </div>

            <!-- Sticky header with timer + (optional) integrity notice -->
            <div class="sticky top-0 z-30 -mx-4 mb-6 border-b border-line bg-bg/95 px-4 py-3 backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="truncate font-bold text-content">{{ props.test.title }}</h1>
                        <p class="text-xs text-content-muted">{{ props.test.course.code }} · Section {{ props.test.section }} · {{ answeredCount }}/{{ questions.length }} answered</p>
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-control px-3 py-1.5 font-mono text-lg font-bold tabular-nums"
                        :class="lowTime ? 'bg-danger-bg text-danger-fg animate-pulse' : 'bg-primary-soft text-primary'"
                    >
                        <ClockIcon class="h-5 w-5" />
                        {{ minutes }}:{{ seconds }}
                    </div>
                </div>
                <p
                    v-if="integrityNotice"
                    class="mt-2 border-t border-line pt-2 text-[11px] italic leading-snug text-content-muted"
                >
                    {{ integrityNotice }}
                </p>
            </div>

            <!-- Questions -->
            <div class="relative z-30 space-y-5">
                <div
                    v-for="(question, qi) in displayList"
                    :key="question.id"
                    class="rounded-card border border-line bg-surface p-5"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <p class="font-medium text-content">
                            <span class="mr-2 text-content-muted">{{ questionNumber(qi) }}.</span>{{ question.questionText }}
                        </p>
                        <span class="flex-shrink-0 text-xs text-content-faint">{{ question.marks }} mark{{ question.marks === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="space-y-2">
                        <label
                            v-for="option in question.options"
                            :key="option.key"
                            class="flex cursor-pointer items-center gap-3 rounded-control border border-line p-3 transition-colors hover:bg-bg"
                            :class="answers[question.id] === option.key ? 'border-primary bg-primary-soft' : ''"
                        >
                            <input
                                type="radio"
                                :name="`q-${question.id}`"
                                :value="option.key"
                                v-model="answers[question.id]"
                                class="h-4 w-4"
                                @change="onAnswer(question)"
                            />
                            <span class="text-sm text-content">{{ option.text }}</span>
                        </label>
                    </div>

                    <!-- Per-question integrity notice: travels with any copied/screenshotted
                         question so AI tools see the request even out of context. -->
                    <p v-if="integrityNotice" class="mt-4 border-t border-line pt-2 text-[10px] italic leading-snug text-content-faint">
                        {{ integrityNotice }}
                    </p>
                </div>
            </div>

            <!-- Navigation (sequential) -->
            <div v-if="sequential" class="relative z-30 mt-6 flex items-center justify-between gap-4">
                <button
                    v-if="!lockBack"
                    type="button"
                    class="ui-btn-ghost disabled:opacity-40"
                    :disabled="isFirst"
                    @click="goPrev"
                >
                    <ArrowLeftIcon class="h-4 w-4" /> Previous
                </button>
                <span v-else class="text-xs text-content-faint">You cannot return to previous questions.</span>

                <button v-if="!isLast" type="button" class="ui-btn-primary" @click="goNext">
                    Next <ArrowRightIcon class="h-4 w-4" />
                </button>
                <button
                    v-else
                    type="button"
                    class="ui-btn-primary px-6 disabled:opacity-50"
                    :disabled="submitting"
                    @click="submitManually"
                >
                    Submit test
                </button>
            </div>

            <!-- Submit (all-at-once) -->
            <div v-else class="relative z-30 mt-6 flex items-center justify-between gap-4">
                <p class="text-sm text-content-muted">{{ answeredCount }} of {{ questions.length }} answered</p>
                <button
                    type="button"
                    class="ui-btn-primary px-6 py-2.5 disabled:opacity-50"
                    :disabled="submitting"
                    @click="submitManually"
                >
                    Submit test
                </button>
            </div>
        </div>

        <!-- Warning overlay -->
        <div v-if="warningOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6">
            <div class="max-w-md rounded-card bg-surface p-8 text-center shadow-card">
                <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-warning-fg" />
                <h2 class="mt-4 text-xl font-bold text-content">Warning {{ warningCount }} of {{ props.test.maxWarnings }}</h2>
                <p class="mt-2 text-sm text-content-muted">
                    You left the exam screen. Leaving again will <strong>disqualify you</strong> and your score will be 0.
                    <template v-if="useFullscreen">Return to fullscreen to continue.</template>
                </p>
                <button type="button" class="ui-btn-primary mt-6 w-full justify-center py-3" @click="resumeAfterWarning">
                    {{ useFullscreen ? 'Return to fullscreen' : 'Continue' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style>
/*
 * When `rootEl` enters fullscreen the browser sizes it to the viewport and
 * clips overflow, so a tall question list becomes unreachable (the student
 * cannot scroll past the first few questions). Force the fullscreen element to
 * own the scroll. Prefixed variants are split into separate rules so an
 * unsupported pseudo-class doesn't invalidate the whole block.
 */
.exam-root:fullscreen {
    height: 100%;
    overflow-y: auto;
}
.exam-root:-webkit-full-screen {
    height: 100%;
    overflow-y: auto;
}
</style>

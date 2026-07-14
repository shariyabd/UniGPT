<script setup>
import { ref, computed, reactive, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    FaceSmileIcon,
} from '@heroicons/vue/24/outline';
import { collectFingerprint } from '@/composables/useBrowserFingerprint';
import { useExamEvents } from '@/composables/useExamEvents';
import { useExamRecorder } from '@/composables/useExamRecorder';
import { useFaceLiveness } from '@/composables/useFaceLiveness';
import { useExamSnapshots } from '@/composables/useExamSnapshots';
import { usePhoneDetection } from '@/composables/usePhoneDetection';
import { useExamSounds } from '@/composables/useExamSounds';

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
const isFullscreen = ref(false);

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

// --- audible alerts (sound reaches a student who isn't looking at the screen)
const sounds = useExamSounds({ enabled: props.security?.soundsEnabled !== false });

// --- snapshot evidence (moment-based photos instead of continuous video) ----
const useSnapshots = computed(() => !!L.value.snapshot_evidence && !!props.security?.snapshots);
const snapshots = useExamSnapshots({
    testId: props.test.id,
    config: props.security?.snapshots ?? {},
});
const snap = (trigger) => { if (useSnapshots.value) snapshots.capture(trigger); };

// --- phone detection (review signal with photo evidence) --------------------
const usePhone = computed(() => !!L.value.phone_detection && !!props.security?.phone);
const phoneDetector = usePhoneDetection({
    config: props.security?.phone ?? {},
    onDetected: ({ score }) => {
        events.warn('phone_detected', { score });
        snap('phone_detected');
        sounds.warning();
    },
    onUnavailable: (reason) => events.log('phone_detection_unavailable', { reason }),
});

// --- face liveness (verification gate + continuous monitoring) --------------
const useLiveness = computed(() => !!L.value.face_liveness && !!props.security?.liveness);
const verifying = ref(false);
const livenessNotice = ref('');
const previewEl = ref(null);

const liveness = useFaceLiveness({
    config: props.security?.liveness ?? {},
    onEvent: async ({ type, severity, meta }) => {
        if (type === 'face_liveness_unavailable') {
            livenessNotice.value = 'Face detection could not start in this browser — the exam continues without it.';
        }
        if (type === 'face_verified') snap('identity');
        if (type === 'face_lost') snap('face_lost');
        if (type === 'multiple_faces') {
            snap('multiple_faces');
            sounds.warning();
        }

        if (severity === 'violation') {
            const data = await events.report(type, meta);
            if (data?.disqualified) {
                autoSubmit(true);
                return;
            }
            warningCount.value = data?.violationCount ?? warningCount.value + 1;
        } else if (severity === 'warning') {
            events.warn(type, meta);
        } else {
            events.log(type, meta);
        }
    },
});

// Top-level refs so the template auto-unwraps them.
const {
    verified: faceVerified,
    blocked: faceBlocked,
    softWarning: faceSoftWarning,
    faceVisible,
    canBypass: canBypassGate,
    secondsWithoutFace,
    warningsUsed,
} = liveness;
const livenessWarningsLeft = computed(() => Math.max(0, liveness.freeWarnings - warningsUsed.value));

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
    // Audio must be unlocked from a user gesture or later alert beeps are blocked.
    sounds.unlock();

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

    // Fullscreen must be requested from the user gesture — before any async
    // verification phase — or the browser rejects it.
    if (useFullscreen.value) await requestFullscreen();

    // Detection layers share the one camera stream the recorder opened.
    const cameraStream = recorder.getStream('webcam');
    if (useSnapshots.value && cameraStream) snapshots.start(cameraStream);
    if (usePhone.value && cameraStream) phoneDetector.start(cameraStream);

    if (useLiveness.value) {
        // Recording (if enabled) starts now so the verification period is on
        // tape, but the questions stay hidden until a live face is confirmed.
        recorder.start();
        verifying.value = true;
        await nextTick();
        if (previewEl.value && cameraStream) previewEl.value.srcObject = cameraStream;
        const { ok } = await liveness.start(cameraStream);
        if (!ok) startExam({ startRecorder: false }); // detector unavailable — degrade gracefully
        return; // otherwise startExam() fires from the faceVerified watcher
    }

    startExam();
};

const startExam = ({ startRecorder = true } = {}) => {
    verifying.value = false;
    started.value = true;
    // The browser may have silently refused the fullscreen request (or the
    // student exited during face verification) — the gate overlay below keeps
    // the paper unusable until fullscreen is actually re-entered.
    isFullscreen.value = !!document.fullscreenElement;
    startedAt = Date.now();
    updateClock();
    captureFingerprint();
    if (startRecorder) recorder.start();
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

watch(faceVerified, (verified) => {
    if (verified && verifying.value) startExam({ startRecorder: false });
});

// Audible alerts on state transitions — the soft beep is the whole point of
// the face-loss stage (the student isn't looking at the banner).
watch(faceSoftWarning, (on) => { if (on) sounds.warning(); });
watch(faceBlocked, (on) => { if (on) sounds.danger(); });

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

    snap('violation'); // photograph who was at the desk when it happened
    sounds.danger();
    const data = await events.report(type);
    if (data?.disqualified) {
        autoSubmit(true);
        return;
    }
    warningCount.value = data?.violationCount ?? warningCount.value + 1;
    warningOpen.value = true;
};

const onVisibilityChange = () => { if (document.hidden) handleViolation('visibility_hidden'); };
const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    if (!document.fullscreenElement && started.value && !finished.value) handleViolation('fullscreen_exit');
};
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
    if (useFullscreen.value) {
        await requestFullscreen();
        isFullscreen.value = !!document.fullscreenElement;
    }
};

// The exam is unusable outside fullscreen when the layer is on — covers the
// initial silent refusal (no fullscreenchange event ever fires) and any state
// drift. The violation modal handles counted exits; this gate handles the rest.
const fullscreenGateOpen = computed(() =>
    useFullscreen.value && started.value && !finished.value && !isFullscreen.value && !warningOpen.value
);

const enterFullscreenFromGate = async () => {
    await requestFullscreen();
    isFullscreen.value = !!document.fullscreenElement;
};

watch(fullscreenGateOpen, (on) => { if (on) sounds.warning(); });

// --- submission -------------------------------------------------------------
const submitting = ref(false);

const finalise = async (disqualified) => {
    finished.value = true;
    submitting.value = true;
    if (timer) window.clearInterval(timer);
    detachGuards();
    liveness.stopDetection();
    phoneDetector.stopDetection();
    snapshots.stopCapture();
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
    liveness.stopDetection();
    phoneDetector.stopDetection();
    snapshots.stopCapture();
    recorder.stop();
});
</script>

<template>
    <div ref="rootEl" class="exam-root min-h-screen select-none bg-bg">
        <Head :title="props.test.title" />

        <!-- Face verification gate — questions stay hidden until a live face is confirmed -->
        <div v-if="verifying" class="flex min-h-screen items-center justify-center p-6">
            <div class="w-full max-w-md rounded-card border border-line bg-surface p-8 text-center shadow-card">
                <FaceSmileIcon class="mx-auto h-12 w-12 text-primary" />
                <h1 class="mt-4 text-xl font-bold text-content">Face verification</h1>
                <p class="mt-2 text-sm text-content-muted">
                    Position your face in the camera frame and <strong>blink</strong>.
                    The questions appear once your face is verified.
                </p>
                <video
                    ref="previewEl"
                    autoplay
                    muted
                    playsinline
                    class="mx-auto mt-5 aspect-video w-full rounded-card border-2 bg-neutral-bg object-cover"
                    :class="faceVisible ? 'border-success-fg' : 'border-line'"
                />
                <p class="mt-3 text-sm" :class="faceVisible ? 'text-success-fg' : 'text-content-muted'">
                    {{ faceVisible ? 'Face detected — now blink to verify.' : 'Looking for your face…' }}
                </p>
                <!-- Escape hatch after repeated failure (covered face, poor camera) —
                     logged for faculty review; recording keeps running regardless. -->
                <div v-if="canBypassGate" class="mt-5 border-t border-line pt-4">
                    <p class="text-xs text-content-muted">
                        Having trouble? You can continue without face verification.
                        This will be noted on your attempt for your faculty to review.
                    </p>
                    <button type="button" class="ui-btn-ghost mt-2 w-full justify-center" @click="liveness.bypassGate()">
                        Continue without face verification
                    </button>
                </div>
            </div>
        </div>

        <!-- Pre-start gate (needed for a user gesture to enter fullscreen / grant media) -->
        <div v-else-if="!started" class="flex min-h-screen items-center justify-center p-6">
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
            <!-- Soft face-loss warning — non-blocking nudge before the overlay -->
            <div
                v-if="faceSoftWarning && !faceBlocked"
                class="fixed inset-x-0 top-0 z-50 flex items-center justify-center gap-2 bg-warning-bg px-4 py-2.5 text-sm font-medium text-warning-fg shadow-card"
            >
                <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0" />
                We can't see you — questions are hidden until you return in front of the camera.
            </div>

            <!-- Fullscreen gate — the paper is unusable until fullscreen is entered.
                 Catches the browser silently refusing the initial request (no
                 fullscreenchange event fires, so no violation is counted). -->
            <div
                v-if="fullscreenGateOpen"
                class="fixed inset-0 z-40 flex items-center justify-center bg-bg/95 p-6 backdrop-blur"
            >
                <div class="max-w-md rounded-card border border-line bg-surface p-8 text-center shadow-card">
                    <ShieldCheckIcon class="mx-auto h-12 w-12 text-primary" />
                    <h2 class="mt-4 text-xl font-bold text-content">Fullscreen required</h2>
                    <p class="mt-2 text-sm text-content-muted">
                        This exam can only be taken in fullscreen. The timer keeps running,
                        so return to fullscreen to continue answering.
                    </p>
                    <button type="button" class="ui-btn-primary mt-6 w-full justify-center py-3" @click="enterFullscreenFromGate">
                        Enter fullscreen
                    </button>
                </div>
            </div>

            <!-- Face-loss blocking overlay — clears automatically when the face returns -->
            <div
                v-if="faceBlocked"
                class="fixed inset-0 z-50 flex items-center justify-center bg-bg/95 p-6 backdrop-blur"
            >
                <div class="max-w-md rounded-card border border-danger-bg bg-surface p-8 text-center shadow-card">
                    <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-danger-fg" />
                    <h2 class="mt-4 text-xl font-bold text-content">Face not detected</h2>
                    <p class="mt-2 text-sm text-content-muted">
                        Your face has not been visible for
                        <strong class="tabular-nums">{{ secondsWithoutFace }}s</strong>.
                        Return in front of the camera to continue — the exam unlocks automatically.
                        The timer keeps running.
                    </p>
                    <p class="mt-4 rounded-control p-3 text-sm" :class="livenessWarningsLeft > 0 ? 'bg-warning-bg text-warning-fg' : 'bg-danger-bg text-danger-fg'">
                        <template v-if="livenessWarningsLeft > 0">
                            Warning {{ warningsUsed }} — {{ livenessWarningsLeft }} free warning{{ livenessWarningsLeft === 1 ? '' : 's' }} left before this counts as a violation.
                        </template>
                        <template v-else>
                            This incident was reported as a violation. Further violations can disqualify you.
                        </template>
                    </p>
                </div>
            </div>

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
                <p
                    v-if="livenessNotice"
                    class="mt-2 rounded-control bg-warning-bg px-3 py-2 text-xs text-warning-fg"
                >
                    {{ livenessNotice }}
                </p>
            </div>

            <!-- Questions -->
            <!-- Questions blur while the face is missing (soft stage) so the paper
                 can't be photographed during the pre-block window. Not a penalty:
                 un-blurs the instant the face returns. -->
            <div
                class="relative z-30 space-y-5 transition-all duration-300"
                :class="faceSoftWarning || faceBlocked || fullscreenGateOpen ? 'pointer-events-none select-none blur-lg' : ''"
                :aria-hidden="faceSoftWarning || faceBlocked || fullscreenGateOpen ? 'true' : undefined"
            >
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

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    ArrowsPointingOutIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    test: { type: Object, required: true },
    attempt: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
});

// --- exam state -------------------------------------------------------------
const started = ref(false);
const finished = ref(false);
const answers = ref({});
const remaining = ref(Math.max(0, props.attempt.remainingSeconds));
const warningOpen = ref(false);
const warningCount = ref(0);
const rootEl = ref(null);

let timer = null;
let lastViolationAt = 0;

const answeredCount = computed(() => Object.values(answers.value).filter((v) => v != null && v !== '').length);

const minutes = computed(() => String(Math.floor(remaining.value / 60)).padStart(2, '0'));
const seconds = computed(() => String(remaining.value % 60).padStart(2, '0'));
const lowTime = computed(() => remaining.value <= 30);

// --- fullscreen + lifecycle -------------------------------------------------
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

const begin = async () => {
    await requestFullscreen();
    started.value = true;
    attachGuards();
    startTimer();
    if (remaining.value <= 0) autoSubmit(false);
};

const startTimer = () => {
    timer = window.setInterval(() => {
        remaining.value -= 1;
        if (remaining.value <= 0) {
            remaining.value = 0;
            autoSubmit(false); // time expired — grade what was answered
        }
    }, 1000);
};

// --- anti-cheat -------------------------------------------------------------
const handleViolation = async () => {
    if (!started.value || finished.value) return;

    // A single "leave" can fire blur + visibilitychange together — debounce so it
    // counts as one violation.
    const now = Date.now();
    if (now - lastViolationAt < 1200) return;
    lastViolationAt = now;

    try {
        const { data } = await window.axios.post(route('class-tests.violation', props.test.id));
        if (data.disqualified) {
            autoSubmit(true); // threshold exceeded → disqualified, score 0
            return;
        }
        warningCount.value = data.violationCount ?? warningCount.value + 1;
        warningOpen.value = true;
    } catch {
        // If the violation could not be recorded, fail safe by warning the student.
        warningOpen.value = true;
    }
};

const onVisibilityChange = () => { if (document.hidden) handleViolation(); };
const onFullscreenChange = () => { if (!document.fullscreenElement && started.value && !finished.value) handleViolation(); };
const onBlur = () => handleViolation();
const block = (e) => { e.preventDefault(); return false; };

const attachGuards = () => {
    document.addEventListener('visibilitychange', onVisibilityChange);
    document.addEventListener('fullscreenchange', onFullscreenChange);
    window.addEventListener('blur', onBlur);
    document.addEventListener('contextmenu', block);
    document.addEventListener('copy', block);
    document.addEventListener('cut', block);
    document.addEventListener('paste', block);
    window.addEventListener('beforeunload', onBeforeUnload);
};

const detachGuards = () => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    window.removeEventListener('blur', onBlur);
    document.removeEventListener('contextmenu', block);
    document.removeEventListener('copy', block);
    document.removeEventListener('cut', block);
    document.removeEventListener('paste', block);
    window.removeEventListener('beforeunload', onBeforeUnload);
};

const onBeforeUnload = (e) => {
    if (started.value && !finished.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

const resumeAfterWarning = async () => {
    warningOpen.value = false;
    await requestFullscreen();
};

// --- submission -------------------------------------------------------------
const submitting = ref(false);

const finalise = (disqualified) => {
    finished.value = true;
    submitting.value = true;
    if (timer) window.clearInterval(timer);
    detachGuards();
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
});
</script>

<template>
    <div ref="rootEl" class="exam-root min-h-screen select-none overflow-y-auto bg-bg">
        <Head :title="props.test.title" />

        <!-- Pre-start gate (needed for a user gesture to enter fullscreen) -->
        <div v-if="!started" class="flex min-h-screen items-center justify-center p-6">
            <div class="max-w-md rounded-card border border-line bg-surface p-8 text-center shadow-card">
                <ArrowsPointingOutIcon class="mx-auto h-12 w-12 text-primary" />
                <h1 class="mt-4 text-xl font-bold text-content">{{ props.test.title }}</h1>
                <p class="mt-2 text-sm text-content-muted">
                    The test will open in fullscreen and the timer will start.
                    Leaving fullscreen or switching tabs may disqualify you.
                </p>
                <button type="button" class="ui-btn-primary mt-6 w-full justify-center py-3" @click="begin">
                    Enter fullscreen & begin
                </button>
            </div>
        </div>

        <!-- Exam -->
        <div v-else class="mx-auto max-w-3xl px-4 py-6">
            <!-- Sticky header with timer -->
            <div class="sticky top-0 z-10 -mx-4 mb-6 flex items-center justify-between border-b border-line bg-bg/95 px-4 py-3 backdrop-blur">
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

            <!-- Questions -->
            <div class="space-y-5">
                <div
                    v-for="(question, qi) in questions"
                    :key="question.id"
                    class="rounded-card border border-line bg-surface p-5"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <p class="font-medium text-content">
                            <span class="mr-2 text-content-muted">{{ qi + 1 }}.</span>{{ question.questionText }}
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
                            />
                            <span class="text-sm text-content">{{ option.text }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-6 flex items-center justify-between gap-4">
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
                    Return to fullscreen to continue.
                </p>
                <button type="button" class="ui-btn-primary mt-6 w-full justify-center py-3" @click="resumeAfterWarning">
                    Return to fullscreen
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

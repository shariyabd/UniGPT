<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import {
    ShieldExclamationIcon,
    ClockIcon,
    CheckCircleIcon,
    VideoCameraIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    test: { type: Object, required: true },
    security: {
        type: Object,
        default: () => ({ layers: {}, recording: {}, integrityNotice: null }),
    },
    inProgress: { type: Boolean, default: false },
});

const agreed = ref(false);
const starting = ref(false);

const layers = computed(() => props.security?.layers ?? {});

// Friendly, student-facing wording for each enabled layer. Layers with no text
// (e.g. the integrity notice) are surfaced separately.
const RULE_TEXT = {
    fullscreen: 'The test runs in fullscreen — stay in fullscreen for the whole test.',
    tab_switch: 'Do not switch tabs or windows, minimise, or let the window lose focus.',
    clipboard: 'Copy, cut, paste, drag and right-click are disabled.',
    sequential: 'Questions are shown one at a time.',
    lock_back: 'Once you answer a question you cannot go back to it.',
    shuffle_questions: 'Questions appear in a randomised order.',
    shuffle_options: 'Answer options are shuffled within each question.',
    watermark: 'Your name, ID and session are watermarked across every question.',
    fingerprint: 'Your browser and device fingerprint is recorded for this attempt.',
    behavior_log: 'Your on-screen activity (focus, timing, clicks) is logged for review.',
    risk_analysis: 'Your activity is analysed for a suspicion score after you submit.',
    webcam: 'Your webcam is recorded for the entire exam.',
    screen_record: 'Your screen is recorded for the entire exam.',
};

const activeRules = computed(() =>
    Object.keys(RULE_TEXT).filter((key) => layers.value[key] && RULE_TEXT[key]).map((key) => RULE_TEXT[key])
);

const showWarnings = computed(() => layers.value.fullscreen || layers.value.tab_switch);
const needsConsent = computed(() => !!(props.security?.recording?.webcam || props.security?.recording?.screen));
const integrityNotice = computed(() => props.security?.integrityNotice ?? null);

const start = () => {
    starting.value = true;
    router.post(route('class-tests.start', props.test.id), {}, {
        onFinish: () => { starting.value = false; },
    });
};
</script>

<template>
    <div>
        <Head :title="`${props.test.title} — Instructions`" />

        <AppLayout>
            <div class="page-container py-8">
                <Link :href="route('class-tests')" class="ui-btn-ghost mb-6 w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to class tests
                </Link>

                <div class="mx-auto max-w-2xl space-y-6">
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-content">{{ props.test.title }}</h1>
                        <p class="mt-1 text-content-muted">
                            {{ props.test.course.code }} · {{ props.test.course.name }} · Section {{ props.test.section }}
                        </p>
                    </div>

                    <Card>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.durationMinutes }}</div>
                                <div class="text-xs text-content-muted">minutes</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.questionCount }}</div>
                                <div class="text-xs text-content-muted">questions</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-content">{{ props.test.totalMarks }}</div>
                                <div class="text-xs text-content-muted">marks</div>
                            </div>
                        </div>
                    </Card>

                    <Card title="Exam rules" :icon="ShieldExclamationIcon">
                        <ul class="space-y-3 text-sm text-content">
                            <li v-for="(rule, i) in activeRules" :key="i" class="flex items-start gap-3">
                                <CheckCircleIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span>{{ rule }}</span>
                            </li>
                            <li v-if="showWarnings" class="flex items-start gap-3">
                                <ShieldExclamationIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-fg" />
                                <span>
                                    You will get <strong>{{ props.test.maxWarnings }} warning{{ props.test.maxWarnings === 1 ? '' : 's' }}</strong>.
                                    Leaving the exam screen beyond that will <strong>disqualify you (score 0)</strong>.
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <ClockIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span>A countdown timer runs throughout. When it reaches zero the test <strong>auto-submits</strong> and grades whatever you've answered.</span>
                            </li>
                        </ul>

                        <p v-if="props.test.description" class="mt-4 whitespace-pre-line rounded-control bg-neutral-bg p-3 text-sm text-content-muted">
                            {{ props.test.description }}
                        </p>
                    </Card>

                    <!-- Media consent — the exam will request camera/screen access -->
                    <Card v-if="needsConsent" title="Recording & consent" :icon="VideoCameraIcon">
                        <p class="text-sm text-content">
                            When you start, your browser will ask permission to record your
                            <template v-if="props.security.recording.webcam && props.security.recording.screen">
                                <strong>webcam and screen</strong>
                            </template>
                            <template v-else-if="props.security.recording.webcam"><strong>webcam</strong></template>
                            <template v-else><strong>screen</strong></template>.
                            Recording lasts for the whole exam and is visible only to your faculty and administrators.
                            You must grant access to begin.
                        </p>
                    </Card>

                    <!-- Assessment integrity notice (AI) -->
                    <Card v-if="integrityNotice">
                        <p class="whitespace-pre-line text-sm italic text-content-muted">{{ integrityNotice }}</p>
                    </Card>

                    <Card v-if="!props.test.isOpen">
                        <p class="text-center text-sm text-danger-fg">This class test is not currently open.</p>
                    </Card>

                    <template v-else>
                        <label class="flex items-center gap-3 rounded-card border border-line p-4">
                            <input v-model="agreed" type="checkbox" class="h-5 w-5 rounded border-line" />
                            <span class="text-sm text-content">I have read and understood the rules above.</span>
                        </label>

                        <button
                            type="button"
                            class="ui-btn-primary w-full justify-center py-3 text-base disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!agreed || starting"
                            @click="start"
                        >
                            {{ inProgress ? 'Resume test' : 'Start test' }}
                        </button>
                    </template>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

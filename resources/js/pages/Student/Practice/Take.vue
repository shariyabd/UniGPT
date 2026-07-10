<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    ClipboardDocumentCheckIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    RectangleStackIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    quiz: { type: Object, required: true },
    attempts: { type: Array, default: () => [] },
});

const toast = useToast();

// question id → chosen answer
const answers = ref({});
const submitting = ref(false);

// Populated once graded; the page swaps from "taking" to "results" state.
const result = ref(null);
const addingToFlashcards = ref(false);
const flashcardDeckUrl = ref(null);

const answeredCount = computed(() => Object.keys(answers.value).length);
const allAnswered = computed(() => answeredCount.value === props.quiz.questions.length);

const submit = async () => {
    if (submitting.value) return;
    submitting.value = true;
    try {
        const { data } = await axios.post(route('practice.submit', props.quiz.id), {
            answers: answers.value,
        });
        result.value = data;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
        toast.error('Could not submit the quiz. Please try again.');
    } finally {
        submitting.value = false;
    }
};

const retake = () => {
    answers.value = {};
    result.value = null;
    flashcardDeckUrl.value = null;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const missedCount = computed(() => result.value
    ? result.value.results.filter((r) => !r.correct).length
    : 0);

const addMissedToFlashcards = async () => {
    if (addingToFlashcards.value || !result.value) return;
    addingToFlashcards.value = true;
    try {
        const { data } = await axios.post(route('practice.flashcards', [props.quiz.id, result.value.attemptId]));
        if (data.deckUrl) {
            flashcardDeckUrl.value = data.deckUrl;
            toast.success('Flashcard deck created from your missed questions.');
        }
    } catch (e) {
        toast.error('Could not create the flashcard deck.');
    } finally {
        addingToFlashcards.value = false;
    }
};

const scorePercent = computed(() => result.value && result.value.total > 0
    ? Math.round((result.value.score / result.value.total) * 100)
    : 0);

const scoreTone = computed(() => {
    if (scorePercent.value >= 80) return 'text-success-fg';
    if (scorePercent.value >= 50) return 'text-warning-fg';
    return 'text-danger-fg';
});

const submittedLabel = (entry) => {
    if (entry.submitted === null || entry.submitted === undefined) return 'No answer';
    if (entry.type === 'true-false') {
        return String(entry.submitted) === 'true' || entry.submitted === true ? 'True' : 'False';
    }
    return entry.submitted;
};

const correctLabel = (entry) => entry.type === 'true-false'
    ? (String(entry.correctAnswer) === 'true' || entry.correctAnswer === true ? 'True' : 'False')
    : entry.correctAnswer;
</script>

<template>
    <div>
        <Head :title="quiz.title" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 max-w-3xl mx-auto">
                <PageHeader
                    :title="quiz.title"
                    :subtitle="`${quiz.questions.length} questions · instant feedback, no proctoring`"
                    :icon="ClipboardDocumentCheckIcon"
                >
                    <template #actions>
                        <Badge variant="neutral">{{ quiz.difficulty }}</Badge>
                        <Badge v-if="quiz.course" variant="info">{{ quiz.course }}</Badge>
                        <Link :href="route('practice.index')" class="ui-btn-secondary">
                            <ArrowLeftIcon class="w-4 h-4" />
                            All quizzes
                        </Link>
                    </template>
                </PageHeader>

                <!-- Results summary -->
                <Card v-if="result">
                    <div class="text-center py-4">
                        <p class="text-sm font-medium text-content-muted">Your score</p>
                        <p class="mt-1 text-4xl font-bold" :class="scoreTone">
                            {{ result.score }}/{{ result.total }}
                            <span class="text-lg font-semibold text-content-faint">({{ scorePercent }}%)</span>
                        </p>
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                            <button @click="retake" class="ui-btn-secondary">
                                <ArrowPathIcon class="w-4 h-4" />
                                Retake quiz
                            </button>
                            <button
                                v-if="missedCount > 0 && !flashcardDeckUrl"
                                @click="addMissedToFlashcards"
                                :disabled="addingToFlashcards"
                                class="ui-btn-primary"
                            >
                                <RectangleStackIcon class="w-4 h-4" />
                                {{ addingToFlashcards ? 'Creating…' : `Add ${missedCount} missed to flashcards` }}
                            </button>
                            <Link v-if="flashcardDeckUrl" :href="flashcardDeckUrl" class="ui-btn-primary">
                                <RectangleStackIcon class="w-4 h-4" />
                                Review flashcard deck
                            </Link>
                        </div>
                    </div>
                </Card>

                <!-- Questions: taking OR reviewing -->
                <template v-if="!result">
                    <Card v-for="(question, index) in quiz.questions" :key="question.id">
                        <p class="font-semibold text-content">
                            <span class="text-content-faint mr-1">{{ index + 1 }}.</span>
                            {{ question.question }}
                        </p>

                        <div class="mt-4 space-y-2">
                            <template v-if="question.type === 'multiple-choice'">
                                <label
                                    v-for="option in question.options"
                                    :key="option"
                                    class="flex items-center gap-3 p-3 rounded-control border cursor-pointer transition-colors"
                                    :class="answers[question.id] === option
                                        ? 'border-primary bg-primary-soft text-primary'
                                        : 'border-line hover:bg-neutral-bg text-content'"
                                >
                                    <input
                                        type="radio"
                                        class="sr-only"
                                        :name="'q' + question.id"
                                        :value="option"
                                        v-model="answers[question.id]"
                                    />
                                    <span class="text-sm">{{ option }}</span>
                                </label>
                            </template>

                            <template v-else>
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        v-for="choice in [true, false]"
                                        :key="String(choice)"
                                        class="flex items-center justify-center gap-2 p-3 rounded-control border cursor-pointer transition-colors text-sm font-medium"
                                        :class="answers[question.id] === choice
                                            ? 'border-primary bg-primary-soft text-primary'
                                            : 'border-line hover:bg-neutral-bg text-content'"
                                    >
                                        <input
                                            type="radio"
                                            class="sr-only"
                                            :name="'q' + question.id"
                                            :value="choice"
                                            v-model="answers[question.id]"
                                        />
                                        {{ choice ? 'True' : 'False' }}
                                    </label>
                                </div>
                            </template>
                        </div>
                    </Card>

                    <div class="sticky bottom-4 z-10">
                        <Card>
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm text-content-muted">{{ answeredCount }}/{{ quiz.questions.length }} answered</p>
                                <button
                                    @click="submit"
                                    :disabled="!allAnswered || submitting"
                                    class="ui-btn-primary"
                                >
                                    <CheckCircleIcon class="w-4 h-4" />
                                    {{ submitting ? 'Grading…' : 'Submit answers' }}
                                </button>
                            </div>
                        </Card>
                    </div>
                </template>

                <template v-else>
                    <Card
                        v-for="(entry, index) in result.results"
                        :key="entry.id"
                        :class="entry.correct ? 'border-l-4 border-l-success-fg' : 'border-l-4 border-l-danger-fg'"
                    >
                        <div class="flex items-start gap-3">
                            <component
                                :is="entry.correct ? CheckCircleIcon : XCircleIcon"
                                class="w-5 h-5 mt-0.5 flex-shrink-0"
                                :class="entry.correct ? 'text-success-fg' : 'text-danger-fg'"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-content">
                                    <span class="text-content-faint mr-1">{{ index + 1 }}.</span>
                                    {{ entry.question }}
                                </p>
                                <p class="mt-2 text-sm" :class="entry.correct ? 'text-success-fg' : 'text-danger-fg'">
                                    Your answer: {{ submittedLabel(entry) }}
                                </p>
                                <p v-if="!entry.correct" class="text-sm text-content">
                                    Correct answer: <strong>{{ correctLabel(entry) }}</strong>
                                </p>
                                <p v-if="entry.explanation" class="mt-2 text-sm text-content-muted bg-neutral-bg rounded-control p-3">
                                    {{ entry.explanation }}
                                </p>
                            </div>
                        </div>
                    </Card>
                </template>

                <!-- Attempt history -->
                <Card v-if="attempts.length > 0 && !result">
                    <template #header>
                        <span class="font-semibold text-content">Previous attempts</span>
                    </template>
                    <ul class="divide-y divide-line">
                        <li v-for="attempt in attempts" :key="attempt.id" class="py-2.5 flex items-center justify-between text-sm">
                            <span class="text-content-muted">{{ attempt.completedAt }}</span>
                            <span class="font-semibold text-content">{{ attempt.score }}/{{ attempt.total }}</span>
                        </li>
                    </ul>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

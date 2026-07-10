<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ClipboardDocumentCheckIcon,
    SparklesIcon,
    TrashIcon,
    PlayIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    quizzes: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const generateForm = useForm({
    topic: '',
    course_id: null,
    question_count: 5,
    difficulty: 'medium',
});

const generate = () => {
    generateForm.post(route('practice.generate'), { preserveScroll: true });
};

const removeQuiz = async (quiz) => {
    const ok = await confirm({
        title: 'Delete practice quiz',
        message: `Delete "${quiz.title}" and its attempt history?`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (ok) {
        router.delete(route('practice.destroy', quiz.id), { preserveScroll: true });
    }
};

const difficultyVariant = (difficulty) => ({
    easy: 'success',
    medium: 'warning',
    hard: 'danger',
}[difficulty] ?? 'neutral');

const scoreLabel = (quiz) => quiz.lastScore === null || quiz.lastScore === undefined
    ? 'Not attempted'
    : `Last ${quiz.lastScore}/${quiz.total} · Best ${quiz.bestScore}/${quiz.total}`;
</script>

<template>
    <div>
        <Head title="Practice Quizzes" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Practice Quizzes"
                    subtitle="Quiz yourself with AI-generated questions, get instant feedback, and turn misses into flashcards."
                    :icon="ClipboardDocumentCheckIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Generate form -->
                    <Card class="h-fit">
                        <template #header>
                            <div class="flex items-center gap-2 font-semibold text-content">
                                <SparklesIcon class="w-5 h-5 text-primary" />
                                Generate a quiz
                            </div>
                        </template>

                        <form @submit.prevent="generate" class="space-y-4">
                            <div>
                                <label for="practice-topic" class="block text-sm font-medium text-content mb-1">Topic</label>
                                <input
                                    id="practice-topic"
                                    v-model="generateForm.topic"
                                    type="text"
                                    required
                                    maxlength="120"
                                    placeholder="e.g. Binary search trees"
                                    class="ui-input w-full"
                                />
                                <p v-if="generateForm.errors.topic" class="mt-1 text-xs text-danger-fg">{{ generateForm.errors.topic }}</p>
                            </div>

                            <div>
                                <label for="practice-course" class="block text-sm font-medium text-content mb-1">Course (optional)</label>
                                <select id="practice-course" v-model="generateForm.course_id" class="ui-input w-full">
                                    <option :value="null">No course</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.code }} — {{ course.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="practice-count" class="block text-sm font-medium text-content mb-1">Questions</label>
                                    <select id="practice-count" v-model.number="generateForm.question_count" class="ui-input w-full">
                                        <option v-for="n in [3, 5, 8, 10, 15]" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="practice-difficulty" class="block text-sm font-medium text-content mb-1">Difficulty</label>
                                    <select id="practice-difficulty" v-model="generateForm.difficulty" class="ui-input w-full">
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="ui-btn-primary w-full" :disabled="generateForm.processing || !generateForm.topic.trim()">
                                <SparklesIcon class="w-4 h-4" />
                                {{ generateForm.processing ? 'Generating…' : 'Generate quiz' }}
                            </button>
                        </form>
                    </Card>

                    <!-- Quiz list -->
                    <div class="lg:col-span-2 space-y-4">
                        <EmptyState
                            v-if="quizzes.length === 0"
                            :icon="AcademicCapIcon"
                            title="No practice quizzes yet"
                            description="Generate your first quiz from any topic you're studying — great before a class test or exam."
                        />

                        <Card v-for="quiz in quizzes" :key="quiz.id">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-content truncate">{{ quiz.title }}</h3>
                                        <Badge :variant="difficultyVariant(quiz.difficulty)">{{ quiz.difficulty }}</Badge>
                                        <Badge v-if="quiz.course" variant="info">{{ quiz.course }}</Badge>
                                    </div>
                                    <p class="mt-1 text-sm text-content-muted">
                                        {{ quiz.questionCount }} questions · {{ quiz.attemptCount }} attempt{{ quiz.attemptCount === 1 ? '' : 's' }} · {{ scoreLabel(quiz) }}
                                    </p>
                                    <p class="text-xs text-content-faint mt-1">Created {{ quiz.createdAt }}</p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <Link :href="route('practice.show', quiz.id)" class="ui-btn-primary">
                                        <PlayIcon class="w-4 h-4" />
                                        {{ quiz.attemptCount > 0 ? 'Retake' : 'Start' }}
                                    </Link>
                                    <button
                                        @click="removeQuiz(quiz)"
                                        class="p-2 rounded-control text-content-faint hover:text-danger-fg hover:bg-danger-bg transition-colors"
                                        title="Delete quiz"
                                        aria-label="Delete quiz"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

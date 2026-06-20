<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    NoSymbolIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    test: { type: Object, required: true },
    attempt: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
});

const isDisqualified = computed(() => props.attempt.status === 'disqualified');

const percentage = computed(() =>
    props.attempt.totalMarks > 0 ? Math.round((props.attempt.score / props.attempt.totalMarks) * 100) : 0
);

const headlineVariant = computed(() => {
    if (isDisqualified.value) return 'danger';
    if (props.attempt.passed === false) return 'warning';
    return 'success';
});

const optionText = (question, key) => {
    if (question.type === 'true_false') return key === 'true' ? 'True' : key === 'false' ? 'False' : '—';
    return question.options?.find((o) => o.key === key)?.text ?? '—';
};
</script>

<template>
    <div>
        <Head :title="`Result — ${props.test.title}`" />

        <AppLayout>
            <div class="page-container py-8">
                <Link :href="route('class-tests')" class="ui-btn-ghost mb-6 w-fit">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to class tests
                </Link>

                <div class="mx-auto max-w-2xl space-y-6">
                    <!-- Headline score -->
                    <Card>
                        <div class="text-center">
                            <NoSymbolIcon v-if="isDisqualified" class="mx-auto h-14 w-14 text-danger-fg" />
                            <CheckCircleIcon v-else class="mx-auto h-14 w-14 text-success-fg" />

                            <h1 class="mt-3 text-2xl font-bold text-content">{{ props.test.title }}</h1>
                            <p class="text-sm text-content-muted">
                                {{ props.test.course.code }} · Section {{ props.test.section }}
                            </p>

                            <div class="mt-4 text-4xl font-extrabold text-content">
                                {{ props.attempt.score }} <span class="text-2xl text-content-muted">/ {{ props.attempt.totalMarks }}</span>
                            </div>
                            <p class="mt-1 text-content-muted">{{ percentage }}%</p>

                            <div class="mt-4 flex items-center justify-center gap-2">
                                <Badge v-if="isDisqualified" variant="danger">Disqualified — score 0</Badge>
                                <Badge v-else-if="props.attempt.status === 'expired'" variant="warning">Time expired</Badge>
                                <Badge v-else variant="success">Submitted</Badge>

                                <Badge v-if="props.attempt.passed === true" variant="success">Passed</Badge>
                                <Badge v-else-if="props.attempt.passed === false && !isDisqualified" variant="warning">Below pass mark</Badge>
                            </div>

                            <p v-if="isDisqualified" class="mt-3 text-sm text-danger-fg">
                                You left the exam screen more than the allowed number of times.
                            </p>
                        </div>
                    </Card>

                    <!-- Answer review -->
                    <Card title="Answer review">
                        <div class="space-y-4">
                            <div
                                v-for="(question, qi) in questions"
                                :key="question.id"
                                class="rounded-card border border-line p-4"
                                :class="question.isCorrect ? 'border-success-fg/30' : 'border-danger-fg/30'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <p class="font-medium text-content">
                                        <span class="mr-1.5 text-content-muted">{{ qi + 1 }}.</span>{{ question.questionText }}
                                    </p>
                                    <CheckCircleIcon v-if="question.isCorrect" class="h-5 w-5 flex-shrink-0 text-success-fg" />
                                    <XCircleIcon v-else class="h-5 w-5 flex-shrink-0 text-danger-fg" />
                                </div>
                                <div class="space-y-1 text-sm">
                                    <p class="text-content-muted">
                                        Your answer:
                                        <span :class="question.isCorrect ? 'text-success-fg' : 'text-danger-fg'">
                                            {{ question.selectedAnswer ? optionText(question, question.selectedAnswer) : 'Not answered' }}
                                        </span>
                                    </p>
                                    <p v-if="!question.isCorrect" class="text-content-muted">
                                        Correct answer: <span class="text-success-fg">{{ optionText(question, question.correctAnswer) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

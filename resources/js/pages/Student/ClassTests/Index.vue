<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ClipboardDocumentListIcon,
    ClockIcon,
    CheckCircleIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    tests: { type: Array, default: () => [] },
});

const stateOf = (test) => {
    if (test.attemptStatus === 'in_progress') return { label: 'In progress', variant: 'warning' };
    if (test.attemptStatus === 'disqualified') return { label: 'Disqualified', variant: 'danger' };
    if (test.attemptStatus) return { label: 'Completed', variant: 'success' };
    if (!test.isOpen) return { label: 'Not open', variant: 'slate' };
    return { label: 'Available', variant: 'primary' };
};

const isDone = (test) => ['submitted', 'expired', 'disqualified'].includes(test.attemptStatus);
</script>

<template>
    <div>
        <Head title="Class Tests" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Class Tests"
                    subtitle="Timed quizzes set by your instructors. Read the rules before you start."
                    :icon="ClipboardDocumentListIcon"
                />

                <EmptyState
                    v-if="tests.length === 0"
                    title="No class tests"
                    description="There are no class tests available for your sections right now."
                    :icon="ClipboardDocumentListIcon"
                />

                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card v-for="test in tests" :key="test.id" hover>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-content-muted">
                                    {{ test.course.code }} · Section {{ test.section }}
                                </div>
                                <h3 class="font-bold text-content">{{ test.title }}</h3>
                            </div>
                            <Badge :variant="stateOf(test).variant">{{ stateOf(test).label }}</Badge>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-content-muted">
                            <span class="flex items-center gap-1"><ClockIcon class="h-4 w-4" /> {{ test.durationMinutes }} min</span>
                            <span>{{ test.questionCount }} questions</span>
                            <span>{{ test.totalMarks }} marks</span>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div v-if="isDone(test)" class="flex items-center gap-1.5 text-sm font-semibold text-success-fg">
                                <CheckCircleIcon class="h-4 w-4" />
                                <span v-if="test.score !== null">Score: {{ test.score }} / {{ test.totalMarks }}</span>
                                <span v-else>Completed</span>
                            </div>
                            <span v-else></span>

                            <Link
                                v-if="isDone(test)"
                                :href="route('class-tests.result', test.id)"
                                class="ui-btn-ghost"
                            >
                                View result <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                            <Link
                                v-else-if="test.isOpen"
                                :href="route('class-tests.show', test.id)"
                                class="ui-btn-primary"
                            >
                                {{ test.attemptStatus === 'in_progress' ? 'Resume' : 'Start' }} <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

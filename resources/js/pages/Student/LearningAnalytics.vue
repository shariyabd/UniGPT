<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatChart from '@/components/charts/StatChart.vue';
import {
    ChartBarIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentListIcon,
    FireIcon,
    CalendarDaysIcon,
    ChatBubbleLeftRightIcon,
    Squares2X2Icon,
    PencilSquareIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    analytics: { type: Object, required: true },
    conceptMastery: { type: Object, default: () => ({ concepts: [], weakCount: 0 }) },
});

// --- Concept mastery map -------------------------------------------------
const concepts = computed(() => props.conceptMastery.concepts ?? []);

// Mastery tier → tile styling (weakest concepts jump out).
const masteryTier = (mastery) => {
    if (mastery >= 80) return { label: 'Strong', class: 'bg-success-bg text-success-fg border-success-fg/20' };
    if (mastery >= 60) return { label: 'Developing', class: 'bg-primary-soft text-primary border-primary/20' };
    if (mastery >= 40) return { label: 'Shaky', class: 'bg-warning-bg text-warning-fg border-warning-fg/20' };
    return { label: 'Weak', class: 'bg-danger-bg text-danger-fg border-danger-fg/20' };
};

const sourceSummary = (entry) => {
    const parts = [];
    if (entry.sources.classTests) parts.push(`Tests ${entry.sources.classTests.accuracy}%`);
    if (entry.sources.practice) parts.push(`Practice ${entry.sources.practice.accuracy}%`);
    if (entry.sources.flashcards) parts.push(`Cards ${entry.sources.flashcards.learned}/${entry.sources.flashcards.total}`);
    return parts.join(' · ');
};

// One-click adaptive review: feed the weak topic straight into the existing
// AI generators; both endpoints redirect to the created quiz/deck.
const generating = ref(null);

const practiceConcept = (entry) => {
    generating.value = `practice:${entry.concept}`;
    router.post(route('practice.generate'), {
        topic: entry.concept,
        question_count: 5,
        difficulty: entry.mastery < 40 ? 'easy' : 'medium',
    }, { onFinish: () => { generating.value = null; } });
};

const flashcardsConcept = (entry) => {
    generating.value = `cards:${entry.concept}`;
    router.post(route('flashcards.generate'), {
        topic: entry.concept,
        count: 10,
        difficulty: entry.mastery < 40 ? 'easy' : 'medium',
    }, { onFinish: () => { generating.value = null; } });
};

const s = computed(() => props.analytics.summary);

const dash = (value, suffix = '') => (value === null || value === undefined ? '—' : `${value}${suffix}`);

const cards = computed(() => [
    { label: 'CGPA', value: dash(s.value.cgpa), icon: AcademicCapIcon, color: 'primary' },
    { label: 'Attendance', value: dash(s.value.attendanceRate, '%'), icon: CalendarDaysIcon, color: 'success' },
    { label: 'Avg Test Score', value: dash(s.value.avgTestScore, '%'), icon: ClipboardDocumentCheckIcon, color: 'warning' },
    { label: 'Avg Assignment', value: dash(s.value.avgAssignmentScore, '%'), icon: ClipboardDocumentListIcon, color: 'primary' },
    { label: 'Study Streak', value: `${s.value.studyStreak} day${s.value.studyStreak === 1 ? '' : 's'}`, icon: FireIcon, color: 'danger' },
    { label: 'AI Sessions', value: String(s.value.aiSessions), icon: ChatBubbleLeftRightIcon, color: 'primary' },
]);

const has = (series) => Array.isArray(series) && series.length > 0;
</script>

<template>
    <div>
        <Head title="My Progress" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="My Progress"
                    subtitle="Track your academic performance and learning engagement over time."
                    :icon="ChartBarIcon"
                />

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <StatCard
                        v-for="card in cards"
                        :key="card.label"
                        :label="card.label"
                        :value="card.value"
                        :icon="card.icon"
                        :color="card.color"
                    />
                </div>

                <!-- Concept mastery map + adaptive review -->
                <Card>
                    <template #header>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="ui-card-title flex items-center gap-2">
                                <Squares2X2Icon class="w-5 h-5 text-primary" />
                                Concept mastery
                            </h2>
                            <span v-if="conceptMastery.weakCount > 0" class="ui-badge bg-danger-bg text-danger-fg">
                                {{ conceptMastery.weakCount }} concept{{ conceptMastery.weakCount === 1 ? '' : 's' }} need review
                            </span>
                        </div>
                    </template>

                    <div v-if="concepts.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        <div
                            v-for="entry in concepts"
                            :key="entry.concept"
                            :class="`rounded-card border p-4 ${masteryTier(entry.mastery).class}`"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-content truncate" :title="entry.concept">
                                        {{ entry.concept }}
                                    </p>
                                    <p class="text-xs text-content-muted mt-0.5">
                                        <span v-if="entry.course" class="font-medium">{{ entry.course }} · </span>
                                        {{ sourceSummary(entry) }}
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-2xl font-bold leading-none">{{ entry.mastery }}%</p>
                                    <p class="text-xs font-medium mt-1">{{ masteryTier(entry.mastery).label }}</p>
                                </div>
                            </div>

                            <div v-if="entry.weak" class="flex items-center gap-2 mt-3">
                                <button
                                    @click="practiceConcept(entry)"
                                    :disabled="generating !== null"
                                    class="ui-btn-secondary text-xs py-1.5 px-2.5"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                    {{ generating === `practice:${entry.concept}` ? 'Generating…' : 'Practice this' }}
                                </button>
                                <button
                                    @click="flashcardsConcept(entry)"
                                    :disabled="generating !== null"
                                    class="ui-btn-secondary text-xs py-1.5 px-2.5"
                                >
                                    <RectangleStackIcon class="w-3.5 h-3.5" />
                                    {{ generating === `cards:${entry.concept}` ? 'Generating…' : 'Make flashcards' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        title="No concept data yet"
                        description="Take practice quizzes, study flashcards or complete class tests and your per-topic mastery will map out here."
                        :icon="Squares2X2Icon"
                    />
                </Card>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <template #header><h2 class="ui-card-title">GPA trend</h2></template>
                        <StatChart
                            v-if="has(analytics.gpaTrend)"
                            type="line"
                            :series="analytics.gpaTrend"
                            label="GPA"
                            color="#6366f1"
                            :max="4"
                        />
                        <EmptyState
                            v-else
                            title="No graded semesters yet"
                            description="Your GPA trend appears once you have graded courses."
                            :icon="AcademicCapIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Attendance by course</h2></template>
                        <StatChart
                            v-if="has(analytics.attendanceByCourse)"
                            type="bar"
                            :series="analytics.attendanceByCourse"
                            label="Attendance %"
                            color="#10b981"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No attendance recorded"
                            description="Attendance appears once your instructors mark sessions."
                            :icon="CalendarDaysIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Class-test scores</h2></template>
                        <StatChart
                            v-if="has(analytics.testTrend)"
                            type="line"
                            :series="analytics.testTrend"
                            label="Score %"
                            color="#f59e0b"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No class tests taken"
                            description="Your class-test performance appears here after you submit a test."
                            :icon="ClipboardDocumentCheckIcon"
                        />
                    </Card>

                    <Card>
                        <template #header><h2 class="ui-card-title">Assignment scores by course</h2></template>
                        <StatChart
                            v-if="has(analytics.assignmentByCourse)"
                            type="bar"
                            :series="analytics.assignmentByCourse"
                            label="Score %"
                            color="#6366f1"
                            :max="100"
                        />
                        <EmptyState
                            v-else
                            title="No graded assignments"
                            description="Assignment scores appear once your submissions are graded."
                            :icon="ClipboardDocumentListIcon"
                        />
                    </Card>

                    <Card class="lg:col-span-2">
                        <template #header><h2 class="ui-card-title">Weekly activity (last 8 weeks)</h2></template>
                        <StatChart
                            v-if="has(analytics.activityTrend)"
                            type="bar"
                            :series="analytics.activityTrend"
                            label="Actions"
                            color="#8b5cf6"
                        />
                        <EmptyState
                            v-else
                            title="No recent activity"
                            description="Your engagement trend builds as you use the platform."
                            :icon="FireIcon"
                        />
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

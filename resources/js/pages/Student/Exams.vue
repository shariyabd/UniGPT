<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    PencilSquareIcon,
    CalendarIcon,
    ClockIcon,
    MapPinIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const typeVariant = (type) => ({
    midterm: 'warning',
    final: 'danger',
    quiz: 'info',
    practical: 'success',
}[type] ?? 'slate');

// Distinct courses across every exam — drives the filter dropdown.
const courseOptions = computed(() => {
    const seen = new Map();
    for (const exam of [...props.upcoming, ...props.past]) {
        if (exam.course && exam.course.code && !seen.has(exam.course.code)) {
            seen.set(exam.course.code, exam.course.name || exam.course.code);
        }
    }
    return [...seen.entries()].map(([code, name]) => ({ code, name }));
});

const selectedCourse = ref('all');

const matchesCourse = (exam) =>
    selectedCourse.value === 'all' || (exam.course && exam.course.code === selectedCourse.value);

const filteredUpcoming = computed(() => props.upcoming.filter(matchesCourse));
const filteredPast = computed(() => props.past.filter(matchesCourse));
</script>

<template>
    <div>
        <Head title="Exams" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Exams"
                    subtitle="Upcoming and past exams for your courses."
                    :icon="PencilSquareIcon"
                >
                    <template v-if="courseOptions.length > 1" #actions>
                        <label class="sr-only" for="exam-course-filter">Filter by course</label>
                        <select id="exam-course-filter" v-model="selectedCourse" class="ui-input w-auto">
                            <option value="all">All courses</option>
                            <option v-for="course in courseOptions" :key="course.code" :value="course.code">{{ course.code }}</option>
                        </select>
                    </template>
                </PageHeader>

                <!-- Upcoming -->
                <section class="space-y-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold tracking-tight text-content">Upcoming</h2>
                        <Badge variant="slate">{{ filteredUpcoming.length }}</Badge>
                    </div>

                    <EmptyState
                        v-if="filteredUpcoming.length === 0"
                        :icon="CalendarIcon"
                        title="No upcoming exams"
                        :description="selectedCourse === 'all' ? 'You have no exams scheduled right now. Check back later.' : 'No upcoming exams for this course.'"
                    />

                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
                        <div v-for="exam in filteredUpcoming" :key="exam.id" class="ui-card p-5 transition-all hover:shadow-card hover:-translate-y-0.5">
                            <div class="flex items-start gap-4">
                                <!-- Date tile -->
                                <div class="flex-shrink-0 w-16 rounded-card bg-primary-soft py-2 text-center text-primary">
                                    <div class="text-2xl font-bold leading-none">{{ exam.date.slice(8, 10) }}</div>
                                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide opacity-80">{{ exam.dateLabel.split(' ')[0] }}</div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-semibold text-content-muted">{{ exam.course.code }}</span>
                                        <Badge :variant="typeVariant(exam.type)">{{ exam.typeLabel }}</Badge>
                                        <Badge v-if="exam.countdown" variant="primary">{{ exam.countdown }}</Badge>
                                    </div>
                                    <h3 class="mt-1 font-bold tracking-tight text-content truncate">{{ exam.title }}</h3>

                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-sm text-content-muted">
                                        <span class="flex items-center gap-1.5">
                                            <CalendarIcon class="h-4 w-4 text-content-faint" /> {{ exam.dateLabel }}
                                        </span>
                                        <span v-if="exam.startTime" class="flex items-center gap-1.5">
                                            <ClockIcon class="h-4 w-4 text-content-faint" /> {{ exam.startTime }}<span v-if="exam.durationMinutes"> · {{ exam.durationMinutes }} min</span>
                                        </span>
                                        <span v-if="exam.location" class="flex items-center gap-1.5">
                                            <MapPinIcon class="h-4 w-4 text-content-faint" /> {{ exam.location }}
                                        </span>
                                        <span v-if="exam.totalMarks" class="flex items-center gap-1.5">
                                            <AcademicCapIcon class="h-4 w-4 text-content-faint" /> {{ exam.totalMarks }} marks
                                        </span>
                                    </div>

                                    <p v-if="exam.instructions" class="mt-3 flex items-start gap-1.5 text-sm text-content-muted">
                                        <DocumentTextIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-content-faint" />
                                        <span>{{ exam.instructions }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Past -->
                <section v-if="filteredPast.length > 0" class="space-y-4">
                    <h2 class="text-lg font-bold tracking-tight text-content">Past</h2>

                    <Card padding="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-line">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Exam</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Course</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Type</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-content-faint">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="exam in filteredPast"
                                        :key="exam.id"
                                        class="border-b border-line hover:bg-bg transition-colors"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2 font-semibold text-content">
                                                <span class="ui-icon-tile bg-primary-soft text-primary">
                                                    <ClipboardDocumentCheckIcon class="h-4 w-4" />
                                                </span>
                                                <span class="truncate">{{ exam.title }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-content-muted">{{ exam.course.code }}</td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="typeVariant(exam.type)">{{ exam.typeLabel }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right text-content-muted">{{ exam.dateLabel }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </section>
            </div>
        </AppLayout>
    </div>
</template>

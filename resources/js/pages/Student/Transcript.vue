<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    AcademicCapIcon,
    PrinterIcon,
    ChartBarIcon,
    CheckCircleIcon,
    BookOpenIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    student: { type: Object, default: () => ({}) },
    semesters: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const print = () => {
    if (typeof window !== 'undefined') {
        window.print();
    }
};

const gradeVariant = (grade) => {
    if (!grade) return 'slate';
    if (grade.startsWith('A')) return 'success';
    if (grade.startsWith('B')) return 'info';
    if (grade.startsWith('C')) return 'warning';
    if (grade.startsWith('D')) return 'warning';
    return 'danger';
};

const studentMeta = (student) => {
    const parts = [];
    if (student.studentId) parts.push(student.studentId);
    if (student.department) parts.push(student.department);
    return parts.join(' • ');
};
</script>

<template>
    <div>
        <Head title="Transcript" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Transcript"
                    :subtitle="[student.name, studentMeta(student)].filter(Boolean).join(' • ')"
                    :icon="AcademicCapIcon"
                >
                    <template #actions>
                        <button
                            type="button"
                            @click="print"
                            class="ui-btn-secondary print:hidden"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Print
                        </button>
                    </template>
                </PageHeader>

                <!-- Summary -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Cumulative GPA"
                        :value="summary.cgpa ?? '—'"
                        :icon="ChartBarIcon"
                        color="brand"
                    />
                    <StatCard
                        label="Credits Completed"
                        :value="summary.completedCredits ?? 0"
                        :icon="CheckCircleIcon"
                        color="emerald"
                    />
                    <StatCard
                        label="Credits Enrolled"
                        :value="summary.totalCredits ?? 0"
                        :icon="RectangleStackIcon"
                        color="blue"
                    />
                    <StatCard
                        label="Courses"
                        :value="summary.coursesCount ?? 0"
                        :icon="BookOpenIcon"
                        color="violet"
                    />
                </div>

                <EmptyState
                    v-if="semesters.length === 0"
                    title="No transcript yet"
                    description="No enrolment records yet."
                    :icon="AcademicCapIcon"
                />

                <!-- Semesters -->
                <div v-else class="space-y-6">
                    <Card
                        v-for="term in semesters"
                        :key="term.semester"
                        padding="p-0"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6 border-b border-line">
                            <h2 class="font-semibold text-content">{{ term.title }}</h2>
                            <div class="flex items-center gap-3 text-sm">
                                <Badge variant="slate">{{ term.credits }} credits</Badge>
                                <Badge variant="brand">GPA {{ term.gpa ?? '—' }}</Badge>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-line">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Course</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-content-faint">Credits</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-content-faint">Grade</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-content-faint">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="course in term.courses"
                                        :key="course.code"
                                        class="border-b border-line hover:bg-bg transition-colors"
                                    >
                                        <td class="px-4 py-3 font-mono text-content-muted">{{ course.code }}</td>
                                        <td class="px-4 py-3 font-medium text-content">{{ course.name }}</td>
                                        <td class="px-4 py-3 text-center text-content-muted">{{ course.credits }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <Badge :variant="gradeVariant(course.grade)">{{ course.grade ?? 'IP' }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-center text-content-muted">
                                            {{ course.points ?? '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

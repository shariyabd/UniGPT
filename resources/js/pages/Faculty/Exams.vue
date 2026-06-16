<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    PencilSquareIcon,
    CalendarDaysIcon,
    MapPinIcon,
    ClockIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const all = computed(() => [...props.upcoming, ...props.past]);

const typeVariant = (type) => ({
    midterm: 'warning',
    final: 'danger',
    quiz: 'info',
    practical: 'success',
}[type] ?? 'slate');
</script>

<template>
    <div>
        <Head title="Course Exams" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Exams"
                    subtitle="Scheduled exams across the courses you teach. Scheduling is managed by the administration."
                    :icon="PencilSquareIcon"
                />

                <EmptyState
                    v-if="all.length === 0"
                    title="No exams scheduled"
                    description="No exams have been scheduled for your courses yet."
                    :icon="CalendarDaysIcon"
                />

                <div v-else class="space-y-6 sm:space-y-8">
                    <section v-if="upcoming.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-5 w-5 text-success" />
                            <h2 class="text-lg font-semibold text-content">Upcoming</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <Card
                                v-for="exam in upcoming"
                                :key="exam.id"
                                hover
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-success">
                                        <AcademicCapIcon class="h-4 w-4" />
                                        {{ exam.course.code }}
                                    </span>
                                    <Badge :variant="typeVariant(exam.type)">{{ exam.typeLabel }}</Badge>
                                </div>

                                <h3 class="font-semibold text-content">{{ exam.title }}</h3>

                                <div class="flex flex-wrap gap-x-4 gap-y-2 mt-3 text-sm text-content-muted">
                                    <span class="flex items-center gap-1.5">
                                        <CalendarDaysIcon class="h-4 w-4" /> {{ exam.dateLabel }}
                                    </span>
                                    <span v-if="exam.startTime" class="flex items-center gap-1.5">
                                        <ClockIcon class="h-4 w-4" /> {{ exam.startTime }}
                                    </span>
                                    <span v-if="exam.location" class="flex items-center gap-1.5">
                                        <MapPinIcon class="h-4 w-4" /> {{ exam.location }}
                                    </span>
                                </div>
                            </Card>
                        </div>
                    </section>

                    <section v-if="past.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2">
                            <ClockIcon class="h-5 w-5 text-content-faint" />
                            <h2 class="text-lg font-semibold text-content">Past</h2>
                        </div>

                        <Card padding="p-0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-line">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Exam</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Course</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="exam in past"
                                            :key="exam.id"
                                            class="border-b border-line hover:bg-bg transition-colors"
                                        >
                                            <td class="px-4 py-3 font-medium text-content">{{ exam.title }}</td>
                                            <td class="px-4 py-3 text-content-muted">{{ exam.course.code }}</td>
                                            <td class="px-4 py-3">
                                                <Badge :variant="typeVariant(exam.type)">{{ exam.typeLabel }}</Badge>
                                            </td>
                                            <td class="px-4 py-3 text-content-muted">{{ exam.dateLabel }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    </section>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

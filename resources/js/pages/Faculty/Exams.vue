<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CalendarDaysIcon, MapPinIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const all = computed(() => [...props.upcoming, ...props.past]);

const typeBadge = (type) => ({
    midterm: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    final: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    quiz: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    practical: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
}[type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300');
</script>

<template>
    <Head title="Course Exams" />

    <AppLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <CalendarDaysIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Course Exams</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Scheduled exams across the courses you teach. Scheduling is managed by the administration.</p>
                </div>
            </div>

            <div v-if="all.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                No exams scheduled for your courses yet.
            </div>

            <div v-else class="space-y-6">
                <div v-if="upcoming.length > 0">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Upcoming</h2>
                    <div class="space-y-3">
                        <div
                            v-for="exam in upcoming"
                            :key="exam.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ exam.course.code }}</span>
                                <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium', typeBadge(exam.type)]">{{ exam.typeLabel }}</span>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ exam.title }}</h3>
                            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-4 h-4" /> {{ exam.dateLabel }}</span>
                                <span v-if="exam.startTime" class="flex items-center gap-1"><ClockIcon class="w-4 h-4" /> {{ exam.startTime }}</span>
                                <span v-if="exam.location" class="flex items-center gap-1"><MapPinIcon class="w-4 h-4" /> {{ exam.location }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="past.length > 0">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Past</h2>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                        <div v-for="exam in past" :key="exam.id" class="p-4 flex items-center justify-between opacity-75">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ exam.title }}</p>
                                <p class="text-xs text-gray-400">{{ exam.course.code }} • {{ exam.typeLabel }}</p>
                            </div>
                            <span class="text-sm text-gray-400">{{ exam.dateLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CalendarDaysIcon, MapPinIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const typeBadge = (type) => ({
    midterm: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    final: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    quiz: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    practical: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
}[type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300');
</script>

<template>
    <Head title="Exam Schedule" />

    <AppLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center">
                    <CalendarDaysIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exam Schedule</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Upcoming and past exams for your courses.</p>
                </div>
            </div>

            <section class="mb-8">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Upcoming</h2>
                <div v-if="upcoming.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-8 text-center text-gray-500 dark:text-gray-400">
                    No upcoming exams scheduled.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="exam in upcoming"
                        :key="exam.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 flex items-start gap-4"
                    >
                        <div class="flex-shrink-0 w-16 text-center">
                            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ exam.date.slice(8, 10) }}</div>
                            <div class="text-xs uppercase text-gray-400">{{ exam.dateLabel.split(' ')[0] }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ exam.course.code }}</span>
                                <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium', typeBadge(exam.type)]">{{ exam.typeLabel }}</span>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ exam.title }}</h3>
                            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-4 h-4" /> {{ exam.dateLabel }}</span>
                                <span v-if="exam.startTime" class="flex items-center gap-1"><ClockIcon class="w-4 h-4" /> {{ exam.startTime }}<span v-if="exam.durationMinutes"> · {{ exam.durationMinutes }} min</span></span>
                                <span v-if="exam.location" class="flex items-center gap-1"><MapPinIcon class="w-4 h-4" /> {{ exam.location }}</span>
                                <span v-if="exam.totalMarks">{{ exam.totalMarks }} marks</span>
                            </div>
                            <p v-if="exam.instructions" class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ exam.instructions }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="past.length > 0">
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
            </section>
        </div>
    </AppLayout>
</template>

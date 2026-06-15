<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { AcademicCapIcon, PrinterIcon } from '@heroicons/vue/24/outline';

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

const gradeColor = (grade) => {
    if (!grade) return 'text-gray-400';
    if (grade.startsWith('A')) return 'text-green-600 dark:text-green-400';
    if (grade.startsWith('B')) return 'text-blue-600 dark:text-blue-400';
    if (grade.startsWith('C')) return 'text-amber-600 dark:text-amber-400';
    if (grade.startsWith('D')) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <Head title="Transcript" />

    <AppLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <AcademicCapIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Academic Transcript</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ student.name }}
                            <span v-if="student.studentId"> • {{ student.studentId }}</span>
                            <span v-if="student.department"> • {{ student.department }}</span>
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="print"
                    class="inline-flex items-center gap-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 print:hidden"
                >
                    <PrinterIcon class="w-4 h-4" /> Print
                </button>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ summary.cgpa ?? '—' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Cumulative GPA</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ summary.completedCredits ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Credits Completed</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ summary.totalCredits ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Credits Enrolled</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ summary.coursesCount ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Courses</div>
                </div>
            </div>

            <div v-if="semesters.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                No enrolment records yet.
            </div>

            <!-- Semesters -->
            <div v-else class="space-y-6">
                <div
                    v-for="term in semesters"
                    :key="term.semester"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden"
                >
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ term.title }}</h2>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ term.credits }} credits</span>
                            <span class="font-semibold text-gray-900 dark:text-white">GPA: {{ term.gpa ?? '—' }}</span>
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-3 font-medium">Code</th>
                                <th class="px-6 py-3 font-medium">Course</th>
                                <th class="px-6 py-3 font-medium text-center">Credits</th>
                                <th class="px-6 py-3 font-medium text-center">Grade</th>
                                <th class="px-6 py-3 font-medium text-center">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <tr v-for="course in term.courses" :key="course.code">
                                <td class="px-6 py-3 font-mono text-gray-500 dark:text-gray-400">{{ course.code }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-white">{{ course.name }}</td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-300">{{ course.credits }}</td>
                                <td class="px-6 py-3 text-center font-bold" :class="gradeColor(course.grade)">
                                    {{ course.grade ?? 'IP' }}
                                </td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-300">
                                    {{ course.points ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

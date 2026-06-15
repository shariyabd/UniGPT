<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    DocumentTextIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';

// Real data from the backend (CourseController@show → CourseService::courseDetail).
const props = defineProps({
    course: { type: Object, required: true },
});

const activeTab = ref('overview');

const students = computed(() => props.course.students ?? []);
const materials = computed(() => props.course.materials ?? []);
const assignments = computed(() => props.course.assignments ?? []);
const schedule = computed(() => props.course.schedule ?? {});

const tabs = computed(() => [
    { id: 'overview', label: 'Overview', icon: AcademicCapIcon },
    { id: 'students', label: `Students (${students.value.length})`, icon: UserGroupIcon },
    { id: 'materials', label: `Materials (${materials.value.length})`, icon: BookOpenIcon },
    { id: 'assignments', label: `Assignments (${assignments.value.length})`, icon: DocumentTextIcon },
]);

const statusColor = (status) => ({
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    enrolled: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    dropped: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
}[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400');

const formatDate = (date) => date
    ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
</script>

<template>
    <div>
        <Head :title="`${course.code} — ${course.name}`" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    🎓 {{ course.code }}
                                </h1>
                                <p class="text-xl text-white/90 mb-1">{{ course.name }}</p>
                                <p class="text-white/80">
                                    {{ course.semester }} • {{ course.credits }} credits
                                    <span v-if="course.department"> • {{ course.department }}</span>
                                    <span v-if="course.instructor"> • {{ course.instructor }}</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white text-center">
                                    <div class="text-sm font-medium">Enrolled</div>
                                    <div class="text-2xl font-bold">
                                        {{ course.enrollment?.current ?? students.length }}
                                        <span class="text-base font-normal text-white/70">/ {{ course.enrollment?.maximum ?? '—' }}</span>
                                    </div>
                                </div>
                                <Link
                                    :href="route('faculty.courses.attendance', course.id)"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    Attendance
                                </Link>
                                <Link
                                    href="/faculty/courses"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Courses
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Tabs -->
                        <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="`flex items-center gap-2 px-6 py-4 font-medium text-sm whitespace-nowrap transition-colors ${
                                    activeTab === tab.id
                                        ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border-b-2 border-teal-600'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                                }`"
                            >
                                <component :is="tab.icon" class="w-5 h-5" />
                                {{ tab.label }}
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Overview -->
                            <div v-if="activeTab === 'overview'" class="space-y-6">
                                <div v-if="course.description">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ course.description }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Schedule</h4>
                                        <dl class="space-y-2 text-sm">
                                            <div v-if="schedule.lectures" class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Lectures</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ schedule.lectures }}</dd>
                                            </div>
                                            <div v-if="schedule.classroom" class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Classroom</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ schedule.classroom }}</dd>
                                            </div>
                                            <div v-if="schedule.office_hours" class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Office hours</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ schedule.office_hours }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-center">
                                            <UserGroupIcon class="w-6 h-6 mx-auto text-blue-600 dark:text-blue-400 mb-1" />
                                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ students.length }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Students</div>
                                        </div>
                                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl text-center">
                                            <BookOpenIcon class="w-6 h-6 mx-auto text-purple-600 dark:text-purple-400 mb-1" />
                                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ materials.length }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Materials</div>
                                        </div>
                                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl text-center">
                                            <DocumentTextIcon class="w-6 h-6 mx-auto text-orange-600 dark:text-orange-400 mb-1" />
                                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ assignments.length }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Assignments</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Students -->
                            <div v-else-if="activeTab === 'students'">
                                <div v-if="students.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    No students enrolled yet.
                                </div>
                                <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <th class="px-4 py-3">Student</th>
                                            <th class="px-4 py-3">ID</th>
                                            <th class="px-4 py-3">Grade</th>
                                            <th class="px-4 py-3">Progress</th>
                                            <th class="px-4 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                        <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ student.name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ student.email }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ student.studentId ?? '—' }}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ student.currentGrade ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="bg-teal-500 h-2 rounded-full" :style="{ width: (student.progress ?? 0) + '%' }"></div>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ student.progress ?? 0 }}%</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span :class="`px-2 py-1 text-xs font-medium rounded-full ${statusColor(student.status)}`">
                                                    {{ student.status ?? 'enrolled' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Materials -->
                            <div v-else-if="activeTab === 'materials'">
                                <div v-if="materials.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    No materials uploaded yet.
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="material in materials"
                                        :key="material.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex items-center gap-3">
                                            <BookOpenIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">{{ material.title }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ material.type }}<span v-if="material.week"> • Week {{ material.week }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                            {{ material.downloads ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignments -->
                            <div v-else-if="activeTab === 'assignments'">
                                <div v-if="assignments.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    No assignments created yet.
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="assignment in assignments"
                                        :key="assignment.id"
                                        class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">{{ assignment.title }}</div>
                                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>{{ assignment.type }}</span>
                                                    <span class="flex items-center gap-1">
                                                        <CalendarIcon class="w-3.5 h-3.5" /> Due {{ formatDate(assignment.dueDate) }}
                                                    </span>
                                                    <span>{{ assignment.totalPoints }} pts</span>
                                                </div>
                                            </div>
                                            <div class="text-right text-sm">
                                                <div class="font-semibold text-gray-900 dark:text-white">
                                                    {{ assignment.graded }} / {{ assignment.submissions }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">graded</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

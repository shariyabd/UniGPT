<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    DocumentTextIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline';

// Real data from the backend (CourseController@show → CourseService::courseDetail).
const props = defineProps({
    course: { type: Object, required: true },
});

const activeTab = ref('overview');

// --- Material management ---
const showMaterialForm = ref(false);
const materialForm = useForm({
    title: '',
    description: '',
    type: 'lecture',
    week: null,
    is_published: true,
    file: null,
});

const submitMaterial = () => {
    materialForm.post(route('faculty.courses.materials.store', props.course.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            materialForm.reset();
            showMaterialForm.value = false;
        },
    });
};

const deleteMaterial = (materialId) => {
    if (!confirm('Delete this material? This cannot be undone.')) return;
    router.delete(route('faculty.courses.materials.destroy', [props.course.id, materialId]), {
        preserveScroll: true,
    });
};

const deleteCourse = () => {
    if (!confirm(`Delete ${props.course.code}? This removes the course and its materials.`)) return;
    router.delete(route('faculty.courses.destroy', props.course.id));
};

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
                                    :href="route('faculty.courses.edit', course.id)"
                                    class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-5 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    <PencilSquareIcon class="w-4 h-4" /> Edit
                                </Link>
                                <button
                                    type="button"
                                    @click="deleteCourse"
                                    class="inline-flex items-center gap-1 bg-red-500/30 backdrop-blur-lg border border-white/20 rounded-xl text-white px-5 py-3 font-medium hover:bg-red-500/50 transition-all"
                                >
                                    <TrashIcon class="w-4 h-4" /> Delete
                                </button>
                                <Link
                                    :href="route('faculty.courses.attendance', course.id)"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-5 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    Attendance
                                </Link>
                                <Link
                                    href="/faculty/courses"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-5 py-3 font-medium hover:bg-white/30 transition-all"
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
                                <div class="flex justify-end mb-4">
                                    <button
                                        type="button"
                                        @click="showMaterialForm = !showMaterialForm"
                                        class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700"
                                    >
                                        <PlusIcon class="w-4 h-4" /> Add Material
                                    </button>
                                </div>

                                <!-- Add material form -->
                                <form
                                    v-if="showMaterialForm"
                                    @submit.prevent="submitMaterial"
                                    class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-xl space-y-4"
                                >
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                            <input v-model="materialForm.title" type="text"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <p v-if="materialForm.errors.title" class="text-xs text-red-500 mt-1">{{ materialForm.errors.title }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select v-model="materialForm.type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="lecture">Lecture</option>
                                                <option value="slides">Slides</option>
                                                <option value="reading">Reading</option>
                                                <option value="assignment">Assignment</option>
                                                <option value="video">Video</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Week</label>
                                            <input v-model.number="materialForm.week" type="number" min="1" max="52"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File (optional)</label>
                                            <input type="file" @input="materialForm.file = $event.target.files[0]"
                                                class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                            <p v-if="materialForm.errors.file" class="text-xs text-red-500 mt-1">{{ materialForm.errors.file }}</p>
                                        </div>
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input v-model="materialForm.is_published" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Publish (visible to students)</span>
                                    </label>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="showMaterialForm = false"
                                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">Cancel</button>
                                        <button type="submit" :disabled="materialForm.processing"
                                            class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                            {{ materialForm.processing ? 'Saving…' : 'Save' }}
                                        </button>
                                    </div>
                                </form>

                                <div v-if="materials.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    No materials yet.
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="material in materials"
                                        :key="material.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <BookOpenIcon class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0" />
                                            <div class="min-w-0">
                                                <div class="font-medium text-gray-900 dark:text-white truncate">
                                                    {{ material.title }}
                                                    <span v-if="!material.isPublished" class="inline-flex items-center gap-1 ml-1 text-xs text-yellow-600 dark:text-yellow-400">
                                                        <EyeSlashIcon class="w-3 h-3" /> draft
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ material.type }}<span v-if="material.week"> • Week {{ material.week }}</span>
                                                    <span v-if="material.fileName"> • {{ material.fileName }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 flex-shrink-0">
                                            <span class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                                <ArrowDownTrayIcon class="w-4 h-4" /> {{ material.downloads ?? 0 }}
                                            </span>
                                            <a
                                                v-if="material.downloadUrl"
                                                :href="material.downloadUrl"
                                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >
                                                Download
                                            </a>
                                            <button
                                                type="button"
                                                @click="deleteMaterial(material.id)"
                                                class="text-red-500 hover:text-red-700"
                                                title="Delete material"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
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

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CalendarDaysIcon, PencilSquareIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    exams: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    course_id: '',
    title: '',
    type: 'midterm',
    exam_date: '',
    start_time: '',
    duration_minutes: '',
    location: '',
    total_marks: '',
    instructions: '',
});

const isEditing = computed(() => editingId.value !== null);

const startCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const startEdit = (exam) => {
    editingId.value = exam.id;
    form.clearErrors();
    form.course_id = exam.course.id;
    form.title = exam.title;
    form.type = exam.type;
    form.exam_date = exam.date;
    form.start_time = exam.startTime ? exam.startTime.slice(0, 5) : '';
    form.duration_minutes = exam.durationMinutes ?? '';
    form.location = exam.location ?? '';
    form.total_marks = exam.totalMarks ?? '';
    form.instructions = exam.instructions ?? '';
};

const submit = () => {
    if (isEditing.value) {
        form.patch(route('admin.exams.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => startCreate(),
        });
    } else {
        form.post(route('admin.exams.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};

const remove = (exam) => {
    if (confirm(`Delete exam "${exam.title}"?`)) {
        router.delete(route('admin.exams.destroy', exam.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Exam Management" />

    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center">
                    <CalendarDaysIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exam Management</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Schedule exams; enrolled students are notified automatically.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form -->
                <form @submit.prevent="submit" class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-4 h-fit">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ isEditing ? 'Edit exam' : 'Schedule exam' }}</h2>
                        <button v-if="isEditing" type="button" @click="startCreate" class="text-xs text-gray-400 hover:text-indigo-600">+ New</button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Course</label>
                        <select v-model="form.course_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>— Select —</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                        </select>
                        <p v-if="form.errors.course_id" class="text-xs text-red-500 mt-1">{{ form.errors.course_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <input v-model="form.title" type="text" placeholder="Midterm Examination"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select v-model="form.type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                            <input v-model="form.exam_date" type="date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.exam_date" class="text-xs text-red-500 mt-1">{{ form.errors.exam_date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start time</label>
                            <input v-model="form.start_time" type="time"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.start_time" class="text-xs text-red-500 mt-1">{{ form.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Duration (min)</label>
                            <input v-model="form.duration_minutes" type="number" min="1" max="600"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                            <input v-model="form.location" type="text" placeholder="Hall A"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total marks</label>
                            <input v-model="form.total_marks" type="number" min="1" max="1000"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instructions</label>
                        <textarea v-model="form.instructions" rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <PlusIcon v-if="!isEditing" class="w-4 h-4" />
                        {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Schedule exam') }}
                    </button>
                </form>

                <!-- List -->
                <div class="lg:col-span-2">
                    <div v-if="exams.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                        No exams scheduled yet.
                    </div>
                    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                        <div v-for="exam in exams" :key="exam.id" class="p-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ exam.course.code }}</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium">{{ exam.typeLabel }}</span>
                                </div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ exam.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ exam.dateLabel }}<span v-if="exam.startTime"> · {{ exam.startTime.slice(0, 5) }}</span><span v-if="exam.location"> · {{ exam.location }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button type="button" @click="startEdit(exam)" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button type="button" @click="remove(exam)" class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

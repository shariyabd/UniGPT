<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    course: {
        type: Object,
        default: null,
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const isEdit = computed(() => props.course !== null);

const form = useForm({
    code: props.course?.code ?? '',
    name: props.course?.name ?? '',
    description: props.course?.description ?? '',
    department_id: props.course?.department_id ?? null,
    semester: props.course?.semester ?? null,
    credits: props.course?.credits ?? 3,
    max_enrollment: props.course?.max_enrollment ?? 60,
    is_active: props.course?.is_active ?? true,
    schedule: {
        lectures: props.course?.schedule?.lectures ?? '',
        classroom: props.course?.schedule?.classroom ?? '',
        office_hours: props.course?.schedule?.office_hours ?? '',
    },
});

const submit = () => {
    if (isEdit.value) {
        form.patch(route('faculty.courses.update', props.course.id));
    } else {
        form.post(route('faculty.courses.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Course' : 'New Course'" />

    <AppLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Link
                :href="isEdit ? route('faculty.courses.show', course.id) : route('faculty.courses')"
                class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 mb-4"
            >
                <ArrowLeftIcon class="w-4 h-4 mr-1" /> Back
            </Link>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ isEdit ? `Edit ${course.code}` : 'New Course' }}
            </h1>

            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                        <input v-model="form.code" type="text" placeholder="CS301"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="Data Structures & Algorithms"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    <p v-if="form.errors.description" class="text-xs text-red-500 mt-1">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                        <select v-model="form.department_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null">— None —</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                        <input v-model.number="form.semester" type="number" min="1" max="12"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.semester" class="text-xs text-red-500 mt-1">{{ form.errors.semester }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Credits</label>
                        <input v-model.number="form.credits" type="number" min="1" max="12"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.credits" class="text-xs text-red-500 mt-1">{{ form.errors.credits }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Enrollment</label>
                        <input v-model.number="form.max_enrollment" type="number" min="1" max="1000"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.max_enrollment" class="text-xs text-red-500 mt-1">{{ form.errors.max_enrollment }}</p>
                    </div>
                </div>

                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">Schedule</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <input v-model="form.schedule.lectures" type="text" placeholder="Mon/Wed 10:00–11:30"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input v-model="form.schedule.classroom" type="text" placeholder="Room 101"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input v-model="form.schedule.office_hours" type="text" placeholder="Tue 14:00–16:00"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </fieldset>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active (visible &amp; open for enrollment)</span>
                </label>

                <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <Link
                        :href="isEdit ? route('faculty.courses.show', course.id) : route('faculty.courses')"
                        class="px-5 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        {{ form.processing ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Course') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

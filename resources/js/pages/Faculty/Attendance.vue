<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    CalendarDaysIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    date: {
        type: String,
        required: true,
    },
    roster: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => ['present', 'absent', 'late', 'excused'],
    },
    summary: {
        type: Object,
        default: () => ({ sessions: 0, records: 0, rate: null }),
    },
});

// Local, editable copy of the roster (status per student).
const entries = ref(props.roster.map((student) => ({ ...student })));
const selectedDate = ref(props.date);

// Reloading on a new date re-renders the page with fresh roster props.
watch(entries, () => {}, { deep: true });

const reloadForDate = () => {
    router.get(
        route('faculty.courses.attendance', props.course.id),
        { date: selectedDate.value },
        { preserveState: false, preserveScroll: true },
    );
};

const setStatus = (studentId, status) => {
    const entry = entries.value.find((e) => e.id === studentId);
    if (entry) entry.status = status;
};

const setAll = (status) => {
    entries.value.forEach((entry) => { entry.status = status; });
};

const presentCount = computed(
    () => entries.value.filter((e) => e.status !== 'absent').length,
);

const statusClasses = (status, active) => {
    const map = {
        present: active ? 'bg-green-500 text-white' : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20',
        absent: active ? 'bg-red-500 text-white' : 'text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20',
        late: active ? 'bg-yellow-500 text-white' : 'text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
        excused: active ? 'bg-blue-500 text-white' : 'text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20',
    };
    return map[status] ?? '';
};

const form = useForm({ date: props.date, entries: [] });

const submit = () => {
    form.date = selectedDate.value;
    form.entries = entries.value.map((e) => ({
        user_id: e.id,
        status: e.status,
        notes: e.notes ?? null,
    }));
    form.post(route('faculty.courses.attendance.store', props.course.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Attendance — ${course.code}`" />

    <AppLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('faculty.courses.show', course.id)"
                    class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 mb-2"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-1" /> Back to course
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Attendance — {{ course.code }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">{{ course.name }}</p>
            </div>

            <!-- Controls -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex flex-wrap items-end gap-4 justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <CalendarDaysIcon class="w-4 h-4 inline mr-1" /> Session date
                        </label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="reloadForDate"
                        />
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1">
                            <UserGroupIcon class="w-5 h-5" /> {{ entries.length }} students
                        </span>
                        <span class="inline-flex items-center gap-1 text-green-600">
                            <CheckCircleIcon class="w-5 h-5" /> {{ presentCount }} present
                        </span>
                        <span v-if="summary.rate !== null">Course rate: <strong>{{ summary.rate }}%</strong></span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500 self-center mr-1">Mark all:</span>
                    <button
                        v-for="status in statuses"
                        :key="status"
                        type="button"
                        class="px-3 py-1 rounded-lg text-sm font-medium border border-gray-200 dark:border-gray-600 capitalize"
                        :class="statusClasses(status, false)"
                        @click="setAll(status)"
                    >
                        {{ status }}
                    </button>
                </div>
            </div>

            <!-- Roster -->
            <div v-if="entries.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div
                    v-for="student in entries"
                    :key="student.id"
                    class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <img :src="student.avatar" :alt="student.name" class="w-9 h-9 rounded-full" />
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ student.name }}</p>
                            <p class="text-xs text-gray-500">{{ student.studentId }}</p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button
                            v-for="status in statuses"
                            :key="status"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium capitalize transition-colors"
                            :class="statusClasses(status, student.status === status)"
                            @click="setStatus(student.id, status)"
                        >
                            {{ status }}
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/40 flex justify-end">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
                        @click="submit"
                    >
                        {{ form.processing ? 'Saving…' : 'Save attendance' }}
                    </button>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <UserGroupIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No students are enrolled in this course.</p>
            </div>
        </div>
    </AppLayout>
</template>

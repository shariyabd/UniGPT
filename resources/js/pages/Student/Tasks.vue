<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PencilSquareIcon, TrashIcon, PlusIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    priority: 'medium',
    course_id: null,
    is_completed: false,
});

const pending = computed(() => props.tasks.filter((t) => !t.isCompleted));
const done = computed(() => props.tasks.filter((t) => t.isCompleted));

const priorityBadge = (priority) => ({
    high: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    low: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
}[priority] ?? 'bg-gray-100 text-gray-600');

const startCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const startEdit = (task) => {
    editingId.value = task.id;
    form.clearErrors();
    form.title = task.title;
    form.description = task.description ?? '';
    form.due_date = task.dueDate ?? '';
    form.priority = task.priority;
    form.course_id = task.courseId ?? null;
    form.is_completed = task.isCompleted;
};

const submit = () => {
    if (editingId.value) {
        form.patch(route('tasks.update', editingId.value), { preserveScroll: true, onSuccess: startCreate });
    } else {
        form.post(route('tasks.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    }
};

const toggle = (task) => {
    router.patch(route('tasks.toggle', task.id), {}, { preserveScroll: true });
};

const remove = (task) => {
    if (confirm('Delete this task?')) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Tasks" />

    <AppLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">My Tasks</h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form -->
                <form @submit.prevent="submit" class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-4 h-fit">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ editingId ? 'Edit task' : 'New task' }}</h2>
                        <button v-if="editingId" type="button" @click="startCreate" class="text-xs text-gray-400 hover:text-indigo-600">+ New</button>
                    </div>
                    <div>
                        <input v-model="form.title" type="text" placeholder="What needs doing?"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>
                    <textarea v-model="form.description" rows="2" placeholder="Details (optional)"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Due date</label>
                            <input v-model="form.due_date" type="date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Priority</label>
                            <select v-model="form.priority"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                    </div>
                    <select v-model="form.course_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option :value="null">— No course —</option>
                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                    </select>
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <PlusIcon v-if="!editingId" class="w-4 h-4" />
                        {{ form.processing ? 'Saving…' : (editingId ? 'Save changes' : 'Add task') }}
                    </button>
                </form>

                <!-- List -->
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <h2 class="text-sm font-semibold uppercase text-gray-400 mb-2">To do ({{ pending.length }})</h2>
                        <div v-if="pending.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-8 text-center text-gray-500 dark:text-gray-400">
                            Nothing pending. 🎉
                        </div>
                        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                            <div v-for="task in pending" :key="task.id" class="p-4 flex items-start gap-3">
                                <button type="button" @click="toggle(task)" class="mt-0.5 text-gray-300 hover:text-green-500">
                                    <CheckCircleIcon class="w-6 h-6" />
                                </button>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
                                    <p v-if="task.description" class="text-sm text-gray-500 dark:text-gray-400">{{ task.description }}</p>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium', priorityBadge(task.priority)]">{{ task.priority }}</span>
                                        <span v-if="task.courseCode" class="text-xs font-semibold text-indigo-500">{{ task.courseCode }}</span>
                                        <span v-if="task.dueDate" class="text-xs" :class="task.isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400'">
                                            {{ task.isOverdue ? 'Overdue · ' : 'Due ' }}{{ task.dueDate }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" @click="startEdit(task)" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="remove(task)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="done.length > 0">
                        <h2 class="text-sm font-semibold uppercase text-gray-400 mb-2">Completed ({{ done.length }})</h2>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                            <div v-for="task in done" :key="task.id" class="p-4 flex items-center gap-3">
                                <button type="button" @click="toggle(task)" class="text-green-500">
                                    <CheckCircleSolid class="w-6 h-6" />
                                </button>
                                <p class="flex-1 text-gray-400 line-through">{{ task.title }}</p>
                                <button type="button" @click="remove(task)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
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

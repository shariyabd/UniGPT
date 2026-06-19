<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { PencilSquareIcon, TrashIcon, PlusIcon, CheckCircleIcon, FlagIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    tasks: { type: Object, default: () => ({ data: [] }) },
    courses: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
});

const taskItems = computed(() => props.tasks.data ?? []);

const editingId = ref(null);

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    priority: 'medium',
    course_id: null,
    is_completed: false,
});

const pending = computed(() => taskItems.value.filter((t) => !t.isCompleted));
const done = computed(() => taskItems.value.filter((t) => t.isCompleted));

const priorityVariant = (priority) => ({
    high: 'danger',
    medium: 'warning',
    low: 'success',
}[priority] ?? 'neutral');

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
    <div>
        <Head title="Tasks" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Tasks"
                    subtitle="Track your personal to-dos, deadlines and priorities."
                    :icon="CheckCircleIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form -->
                    <Card class="h-fit">
                        <template #header>
                            <div class="flex items-center justify-between">
                                <h2 class="ui-card-title">{{ editingId ? 'Edit task' : 'New task' }}</h2>
                                <button
                                    v-if="editingId"
                                    type="button"
                                    @click="startCreate"
                                    class="ui-btn-ghost text-xs"
                                >
                                    <PlusIcon class="w-4 h-4" />
                                    New
                                </button>
                            </div>
                        </template>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="ui-label" for="task-title">Title</label>
                                <input
                                    id="task-title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="What needs doing?"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.title" class="text-xs text-danger-fg mt-1">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label class="ui-label" for="task-description">Details</label>
                                <textarea
                                    id="task-description"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Details (optional)"
                                    class="ui-input resize-none"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ui-label" for="task-due-date">Due date</label>
                                    <input id="task-due-date" v-model="form.due_date" type="date" class="ui-input" />
                                </div>
                                <div>
                                    <label class="ui-label" for="task-priority">Priority</label>
                                    <select id="task-priority" v-model="form.priority" class="ui-input">
                                        <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="ui-label" for="task-course">Course</label>
                                <select id="task-course" v-model="form.course_id" class="ui-input">
                                    <option :value="null">— No course —</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                </select>
                            </div>

                            <button type="submit" :disabled="form.processing" class="ui-btn-primary w-full">
                                <PlusIcon v-if="!editingId" class="w-4 h-4" />
                                {{ form.processing ? 'Saving…' : (editingId ? 'Save changes' : 'Add task') }}
                            </button>
                        </form>
                    </Card>

                    <!-- List -->
                    <div class="lg:col-span-2 space-y-6">
                        <section class="space-y-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-content-muted">
                                To do ({{ pending.length }})
                            </h2>

                            <EmptyState
                                v-if="pending.length === 0"
                                title="All clear"
                                description="Nothing pending — add a task to get started."
                                :icon="CheckCircleIcon"
                            />

                            <div v-else class="space-y-3">
                                <Card
                                    v-for="task in pending"
                                    :key="task.id"
                                    padding="p-4"
                                    hover
                                >
                                    <div class="flex items-start gap-3">
                                        <button
                                            type="button"
                                            @click="toggle(task)"
                                            aria-label="Mark task complete"
                                            class="mt-0.5 text-content-faint hover:text-success-fg transition-colors"
                                        >
                                            <CheckCircleIcon class="w-6 h-6" />
                                        </button>

                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-content">{{ task.title }}</p>
                                            <p v-if="task.description" class="text-sm text-content-muted mt-0.5">{{ task.description }}</p>
                                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                                <Badge :variant="priorityVariant(task.priority)" class="gap-1">
                                                    <FlagIcon class="w-3 h-3" />
                                                    {{ task.priority }}
                                                </Badge>
                                                <Badge v-if="task.courseCode" variant="primary">{{ task.courseCode }}</Badge>
                                                <span
                                                    v-if="task.dueDate"
                                                    class="text-xs"
                                                    :class="task.isOverdue ? 'text-danger-fg font-semibold' : 'text-content-muted'"
                                                >
                                                    {{ task.isOverdue ? 'Overdue · ' : 'Due ' }}{{ task.dueDate }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button
                                                type="button"
                                                @click="startEdit(task)"
                                                aria-label="Edit task"
                                                class="ui-btn-ghost p-1.5"
                                            >
                                                <PencilSquareIcon class="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="remove(task)"
                                                aria-label="Delete task"
                                                class="ui-btn-ghost p-1.5 text-content-faint hover:text-danger-fg"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </Card>
                            </div>
                        </section>

                        <section v-if="done.length > 0" class="space-y-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-content-muted">
                                Completed ({{ done.length }})
                            </h2>
                            <Card padding="p-0">
                                <div class="divide-y divide-line">
                                    <div
                                        v-for="task in done"
                                        :key="task.id"
                                        class="p-4 flex items-center gap-3"
                                    >
                                        <button
                                            type="button"
                                            @click="toggle(task)"
                                            aria-label="Mark task incomplete"
                                            class="text-success-fg hover:text-success-fg/80 transition-colors"
                                        >
                                            <CheckCircleSolid class="w-6 h-6" />
                                        </button>
                                        <p class="flex-1 text-content-faint line-through">{{ task.title }}</p>
                                        <button
                                            type="button"
                                            @click="remove(task)"
                                            aria-label="Delete task"
                                            class="ui-btn-ghost p-1.5 text-content-faint hover:text-danger-fg"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </Card>
                        </section>

                        <Pagination :paginator="tasks" label="tasks" />
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

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
    <div>
        <Head title="Exam Management" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Exam Management"
                    subtitle="Schedule exams across courses; enrolled students are notified automatically."
                    :icon="PencilSquareIcon"
                >
                    <template #actions>
                        <button
                            v-if="isEditing"
                            type="button"
                            @click="startCreate"
                            class="ui-btn-secondary"
                        >
                            <PlusIcon class="w-4 h-4" />
                            New exam
                        </button>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form -->
                    <Card
                        class="lg:col-span-1 h-fit"
                        :title="isEditing ? 'Edit exam' : 'Schedule exam'"
                        :icon="PencilSquareIcon"
                    >
                        <template #actions>
                            <button
                                v-if="isEditing"
                                type="button"
                                @click="startCreate"
                                class="ui-btn-ghost text-xs"
                            >
                                <PlusIcon class="w-3.5 h-3.5" />
                                New
                            </button>
                        </template>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="ui-label">Course</label>
                                <select v-model="form.course_id" class="ui-input">
                                    <option value="" disabled>— Select —</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                                </select>
                                <p v-if="form.errors.course_id" class="text-xs text-danger-fg mt-1">{{ form.errors.course_id }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Title</label>
                                <input v-model="form.title" type="text" placeholder="Midterm Examination" class="ui-input" />
                                <p v-if="form.errors.title" class="text-xs text-danger-fg mt-1">{{ form.errors.title }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ui-label">Type</label>
                                    <select v-model="form.type" class="ui-input">
                                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ui-label">Date</label>
                                    <input v-model="form.exam_date" type="date" class="ui-input" />
                                    <p v-if="form.errors.exam_date" class="text-xs text-danger-fg mt-1">{{ form.errors.exam_date }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ui-label">Start time</label>
                                    <input v-model="form.start_time" type="time" class="ui-input" />
                                    <p v-if="form.errors.start_time" class="text-xs text-danger-fg mt-1">{{ form.errors.start_time }}</p>
                                </div>
                                <div>
                                    <label class="ui-label">Duration (min)</label>
                                    <input v-model="form.duration_minutes" type="number" min="1" max="600" class="ui-input" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ui-label">Location</label>
                                    <input v-model="form.location" type="text" placeholder="Hall A" class="ui-input" />
                                </div>
                                <div>
                                    <label class="ui-label">Total marks</label>
                                    <input v-model="form.total_marks" type="number" min="1" max="1000" class="ui-input" />
                                </div>
                            </div>

                            <div>
                                <label class="ui-label">Instructions</label>
                                <textarea v-model="form.instructions" rows="2" class="ui-input resize-none"></textarea>
                            </div>

                            <button type="submit" :disabled="form.processing" class="ui-btn-primary w-full">
                                <PlusIcon v-if="!isEditing" class="w-4 h-4" />
                                {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Schedule exam') }}
                            </button>
                        </form>
                    </Card>

                    <!-- List -->
                    <div class="lg:col-span-2">
                        <Card v-if="exams.length === 0" padding="p-0">
                            <EmptyState
                                title="No exams scheduled yet"
                                description="Use the form to schedule your first exam. Enrolled students will be notified automatically."
                                :icon="CalendarDaysIcon"
                            />
                        </Card>

                        <Card v-else padding="p-0">
                            <ul class="divide-y divide-line">
                                <li
                                    v-for="exam in exams"
                                    :key="exam.id"
                                    class="p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-bg transition-colors"
                                >
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div class="hidden sm:flex w-10 h-10 rounded-control bg-primary-soft items-center justify-center flex-shrink-0">
                                            <AcademicCapIcon class="w-5 h-5 text-primary" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <span class="text-xs font-semibold text-primary">{{ exam.course.code }}</span>
                                                <Badge variant="violet">{{ exam.typeLabel }}</Badge>
                                            </div>
                                            <p class="font-semibold text-content mt-0.5 truncate">{{ exam.title }}</p>
                                            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-content-muted">
                                                <span class="inline-flex items-center gap-1">
                                                    <CalendarDaysIcon class="w-3.5 h-3.5" />
                                                    {{ exam.dateLabel }}
                                                </span>
                                                <span v-if="exam.startTime" class="inline-flex items-center gap-1">
                                                    <ClockIcon class="w-3.5 h-3.5" />
                                                    {{ exam.startTime.slice(0, 5) }}
                                                </span>
                                                <span v-if="exam.location" class="inline-flex items-center gap-1">
                                                    <MapPinIcon class="w-3.5 h-3.5" />
                                                    {{ exam.location }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button
                                            type="button"
                                            @click="startEdit(exam)"
                                            aria-label="Edit exam"
                                            class="p-2 rounded-control text-content-faint hover:text-primary hover:bg-primary-soft transition-colors"
                                        >
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="remove(exam)"
                                            aria-label="Delete exam"
                                            class="p-2 rounded-control text-content-faint hover:text-danger-fg hover:bg-danger-bg transition-colors"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

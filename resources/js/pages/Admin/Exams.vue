<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    AcademicCapIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    exams: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const showModal = ref(false);
const editingId = ref(null);

// Filters
const searchQuery = ref('');
const selectedCourse = ref('all');
const selectedType = ref('all');

const form = useForm({
    course_id: '',
    section_id: '',
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

// Sections of the course currently selected in the form — drives the section
// picker. An empty section means "all sections of the course".
const availableSections = computed(() => {
    const course = props.courses.find((c) => c.id === form.course_id);
    return course?.sections ?? [];
});

// Changing the course invalidates any previously chosen section.
const onCourseChange = () => {
    form.section_id = '';
};

const filteredExams = computed(() => {
    let filtered = props.exams;

    if (selectedCourse.value !== 'all') {
        filtered = filtered.filter((exam) => exam.course.id === selectedCourse.value);
    }

    if (selectedType.value !== 'all') {
        filtered = filtered.filter((exam) => exam.type === selectedType.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((exam) =>
            exam.title.toLowerCase().includes(query) ||
            (exam.course.code ?? '').toLowerCase().includes(query) ||
            (exam.course.name ?? '').toLowerCase().includes(query) ||
            (exam.location ?? '').toLowerCase().includes(query)
        );
    }

    return filtered;
});

const hasActiveFilters = computed(() =>
    searchQuery.value !== '' || selectedCourse.value !== 'all' || selectedType.value !== 'all'
);

const clearFilters = () => {
    searchQuery.value = '';
    selectedCourse.value = 'all';
    selectedType.value = 'all';
};

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (exam) => {
    editingId.value = exam.id;
    form.clearErrors();
    form.course_id = exam.course.id;
    form.section_id = exam.sectionId ?? '';
    form.title = exam.title;
    form.type = exam.type;
    form.exam_date = exam.date;
    form.start_time = exam.startTime ? exam.startTime.slice(0, 5) : '';
    form.duration_minutes = exam.durationMinutes ?? '';
    form.location = exam.location ?? '';
    form.total_marks = exam.totalMarks ?? '';
    form.instructions = exam.instructions ?? '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };

    if (isEditing.value) {
        form.patch(route('admin.exams.update', editingId.value), options);
    } else {
        form.post(route('admin.exams.store'), options);
    }
};

const remove = async (exam) => {
    const confirmed = await confirm({
        title: 'Delete exam',
        message: `This will permanently delete "${exam.title}". This action cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger'
    });

    if (!confirmed) return;

    router.delete(route('admin.exams.destroy', exam.id), { preserveScroll: true });
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
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            New exam
                        </button>
                    </template>
                </PageHeader>

                <!-- Empty state (no exams at all) -->
                <Card v-if="exams.length === 0" padding="p-0">
                    <EmptyState
                        title="No exams scheduled yet"
                        description="Schedule your first exam. Enrolled students will be notified automatically."
                        :icon="CalendarDaysIcon"
                    >
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            New exam
                        </button>
                    </EmptyState>
                </Card>

                <template v-else>
                    <!-- Search & Filters -->
                    <Card>
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="relative w-full lg:max-w-md">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by title, course, or location..."
                                    class="ui-input pl-10"
                                    aria-label="Search exams"
                                />
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <select v-model="selectedCourse" class="ui-input sm:w-auto" aria-label="Filter by course">
                                    <option value="all">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.code }} — {{ course.name }}
                                    </option>
                                </select>

                                <select v-model="selectedType" class="ui-input sm:w-auto" aria-label="Filter by type">
                                    <option value="all">All Types</option>
                                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>

                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    @click="clearFilters"
                                    class="ui-btn-ghost text-primary"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                    Clear
                                </button>
                            </div>
                        </div>
                    </Card>

                    <p class="text-sm text-content-muted">
                        Showing {{ filteredExams.length }} of {{ exams.length }} exam{{ exams.length === 1 ? '' : 's' }}
                    </p>

                    <!-- List -->
                    <Card padding="p-0">
                        <ul v-if="filteredExams.length > 0" class="divide-y divide-line">
                            <li
                                v-for="exam in filteredExams"
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
                                            <Badge v-if="exam.section" variant="slate">Section {{ exam.section }}</Badge>
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
                                        @click="openEdit(exam)"
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

                        <!-- No matches for current filters -->
                        <EmptyState
                            v-else
                            title="No exams match your filters"
                            description="Try adjusting your search or filters."
                            :icon="MagnifyingGlassIcon"
                        >
                            <button type="button" @click="clearFilters" class="ui-btn-secondary">
                                <XMarkIcon class="w-4 h-4" />
                                Clear filters
                            </button>
                        </EmptyState>
                    </Card>
                </template>
            </div>

            <!-- Create / Edit modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeModal"></div>

                    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">
                                {{ isEditing ? 'Edit exam' : 'Schedule exam' }}
                            </h3>
                            <button type="button" @click="closeModal" class="ui-btn-ghost p-2" aria-label="Close">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <div>
                                    <label class="ui-label">Course *</label>
                                    <select v-model="form.course_id" class="ui-input" @change="onCourseChange">
                                        <option value="" disabled>— Select —</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} — {{ course.name }}</option>
                                    </select>
                                    <p v-if="form.errors.course_id" class="text-xs text-danger-fg mt-1">{{ form.errors.course_id }}</p>
                                </div>

                                <div>
                                    <label class="ui-label">Section</label>
                                    <select v-model="form.section_id" class="ui-input" :disabled="!form.course_id">
                                        <option value="">All sections</option>
                                        <option v-for="section in availableSections" :key="section.id" :value="section.id">
                                            Section {{ section.label }}
                                        </option>
                                    </select>
                                    <p class="text-xs text-content-muted mt-1">
                                        Leave as “All sections” to schedule this exam for every section of the course.
                                    </p>
                                    <p v-if="form.errors.section_id" class="text-xs text-danger-fg mt-1">{{ form.errors.section_id }}</p>
                                </div>

                                <div>
                                    <label class="ui-label">Title *</label>
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
                                        <label class="ui-label">Date *</label>
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
                            </div>

                            <div class="flex flex-shrink-0 items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeModal" class="ui-btn-ghost">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="ui-btn-primary">
                                    {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Schedule exam') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
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
    exams: { type: Object, default: () => ({ data: [] }) },
    courses: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const examItems = computed(() => props.exams.data ?? []);

const { confirm } = useConfirm();

const showModal = ref(false);
const editingId = ref(null);

// Filters are applied SERVER-SIDE (across all pages), seeded from the URL so a
// shared/bookmarked filtered link restores its state.
const searchQuery = ref(props.filters.search ?? '');
const selectedCourse = ref(props.filters.course_id ? Number(props.filters.course_id) : 'all');
const selectedDepartment = ref(props.filters.department_id ? Number(props.filters.department_id) : 'all');
const selectedSection = ref(props.filters.section ?? 'all');
const selectedType = ref(props.filters.type ?? 'all');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

// Push the current filter state to the server (resets to page 1). Search is
// debounced; the dropdowns fire immediately.
// Clearing a SearchableSelect emits null; treat it the same as the "all" sentinel.
const isAll = (value) => value === 'all' || value === null;

const applyFilters = () => {
    router.get(
        route('admin.exams'),
        {
            search: searchQuery.value || undefined,
            course_id: !isAll(selectedCourse.value) ? selectedCourse.value : undefined,
            department_id: !isAll(selectedDepartment.value) ? selectedDepartment.value : undefined,
            section: !isAll(selectedSection.value) ? selectedSection.value : undefined,
            type: !isAll(selectedType.value) ? selectedType.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});
watch([selectedCourse, selectedDepartment, selectedSection, selectedType, dateFrom, dateTo], applyFilters);

// Filter dropdown options (the empty "All …" entry is handled by placeholder + clearable).
const courseFilterOptions = computed(() =>
    props.courses.map((course) => ({ value: course.id, label: `${course.code} — ${course.name}` })),
);
const departmentFilterOptions = computed(() =>
    props.departments.map((department) => ({ value: department.id, label: department.name })),
);
const sectionFilterOptions = computed(() =>
    props.sections.map((label) => ({ value: label, label: `Section ${label}` })),
);
const typeFilterOptions = computed(() =>
    props.types.map((t) => ({ value: t.value, label: t.label })),
);

// Form dropdown options.
const courseFormOptions = computed(() =>
    props.courses.map((course) => ({ value: course.id, label: `${course.code} — ${course.name}` })),
);
// "All Sections" is an explicit first option (value '') so scheduling for every
// section of a course is a deliberate choice rather than a hidden empty state.
// An empty string is converted to null server-side → exam created per section.
const sectionFormOptions = computed(() => [
    { value: '', label: 'All Sections' },
    ...availableSections.value.map((section) => ({ value: section.id, label: `Section ${section.label}` })),
]);
const typeFormOptions = computed(() =>
    props.types.map((t) => ({ value: t.value, label: t.label })),
);

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

const hasActiveFilters = computed(() =>
    searchQuery.value !== '' || !isAll(selectedCourse.value) || !isAll(selectedDepartment.value)
    || !isAll(selectedSection.value) || !isAll(selectedType.value)
    || dateFrom.value !== '' || dateTo.value !== ''
);

// Total exams matched by the active filters, across all pages.
const totalExams = computed(() => props.exams.meta?.total ?? props.exams.total ?? examItems.value.length);

const clearFilters = () => {
    searchQuery.value = '';
    selectedCourse.value = 'all';
    selectedDepartment.value = 'all';
    selectedSection.value = 'all';
    selectedType.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    // The dropdown watcher fires applyFilters; nudge in case nothing changed.
    applyFilters();
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
                    :count="totalExams"
                >
                    <template #actions>
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            New exam
                        </button>
                    </template>
                </PageHeader>

                <!-- Empty state (no exams at all, and no filters narrowing them out) -->
                <Card v-if="totalExams === 0 && !hasActiveFilters" padding="p-0">
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
                                    placeholder="Search by title, course code, section, or location..."
                                    class="ui-input pl-10"
                                    aria-label="Search exams"
                                />
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="sm:w-56">
                                    <SearchableSelect
                                        v-model="selectedDepartment"
                                        :options="departmentFilterOptions"
                                        placeholder="All Departments"
                                        clearable
                                    />
                                </div>

                                <div class="sm:w-56">
                                    <SearchableSelect
                                        v-model="selectedCourse"
                                        :options="courseFilterOptions"
                                        placeholder="All Courses"
                                        clearable
                                    />
                                </div>

                                <div class="sm:w-44">
                                    <SearchableSelect
                                        v-model="selectedSection"
                                        :options="sectionFilterOptions"
                                        placeholder="All Sections"
                                        clearable
                                    />
                                </div>

                                <div class="sm:w-44">
                                    <SearchableSelect
                                        v-model="selectedType"
                                        :options="typeFilterOptions"
                                        placeholder="All Types"
                                        clearable
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="dateFrom"
                                        type="date"
                                        class="ui-input sm:w-auto"
                                        aria-label="Exams from date"
                                        :max="dateTo || undefined"
                                    />
                                    <span class="text-content-faint">–</span>
                                    <input
                                        v-model="dateTo"
                                        type="date"
                                        class="ui-input sm:w-auto"
                                        aria-label="Exams to date"
                                        :min="dateFrom || undefined"
                                    />
                                </div>

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
                        {{ totalExams }} exam{{ totalExams === 1 ? '' : 's' }} match{{ totalExams === 1 ? 'es' : '' }} your filters
                    </p>

                    <!-- List -->
                    <Card padding="p-0">
                        <ul v-if="examItems.length > 0" class="divide-y divide-line">
                            <li
                                v-for="exam in examItems"
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

                    <Pagination :paginator="exams" label="exams" />
                </template>
            </div>

            <!-- Create / Edit modal -->
            <Teleport to="body">
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
                                    <SearchableSelect
                                        v-model="form.course_id"
                                        :options="courseFormOptions"
                                        placeholder="— Select —"
                                        @update:model-value="onCourseChange"
                                    />
                                    <p v-if="form.errors.course_id" class="text-xs text-danger-fg mt-1">{{ form.errors.course_id }}</p>
                                </div>

                                <div>
                                    <label class="ui-label">Section</label>
                                    <SearchableSelect
                                        v-model="form.section_id"
                                        :options="sectionFormOptions"
                                        placeholder="All Sections"
                                        :disabled="!form.course_id"
                                    />
                                    <p class="text-xs text-content-muted mt-1">
                                        Choose “All Sections” to schedule this exam for every section of the course.
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
                                        <SearchableSelect v-model="form.type" :options="typeFormOptions" />
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
            </Teleport>
        </AppLayout>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    DocumentTextIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    EyeIcon,
    EyeSlashIcon,
    XMarkIcon,
    ArrowLeftIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    MapPinIcon,
    PresentationChartLineIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

// Real data from the backend (CourseController@show → CourseService::courseDetail).
const props = defineProps({
    course: { type: Object, required: true },
});

const { confirm } = useConfirm();

const activeTab = ref('overview');

// --- Section switching (a faculty may teach several sections of one course) ---
const sections = computed(() => props.course.sections ?? []);
const selectedSection = ref(props.course.activeSectionId);

const changeSection = () => {
    router.get(
        route('faculty.courses.show', props.course.id),
        { section: selectedSection.value },
        { preserveState: false, preserveScroll: true },
    );
};

// --- Material management ---
const showMaterialForm = ref(false);
const materialForm = useForm({
    section_id: props.course.activeSectionId,
    title: '',
    description: '',
    type: 'lecture',
    week: null,
    is_published: true,
    file: null,
});

const submitMaterial = () => {
    materialForm.section_id = props.course.activeSectionId;
    materialForm.post(route('faculty.courses.materials.store', props.course.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            materialForm.reset();
            showMaterialForm.value = false;
        },
    });
};

const deleteMaterial = async (materialId) => {
    const ok = await confirm({
        title: 'Delete material',
        message: 'Delete this material? This cannot be undone.',
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('faculty.courses.materials.destroy', [props.course.id, materialId]), {
        preserveScroll: true,
    });
};

const students = computed(() => props.course.students ?? []);
const materials = computed(() => props.course.materials ?? []);
const assignments = computed(() => props.course.assignments ?? []);
const schedule = computed(() => props.course.schedule ?? {});

// --- Assignment / quiz management (view, edit, status, delete) ---
const viewingAssignment = ref(null);

const editForm = useForm({
    title: '',
    description: '',
    type: 'homework',
    total_points: 100,
    due_at: null,
    status: 'published',
});
const editingId = ref(null);

const openView = (assignment) => {
    viewingAssignment.value = assignment;
};

const openEdit = (assignment) => {
    editingId.value = assignment.id;
    editForm.clearErrors();
    editForm.title = assignment.title;
    editForm.description = assignment.description ?? '';
    editForm.type = assignment.type ?? 'homework';
    editForm.total_points = assignment.totalPoints ?? 100;
    editForm.due_at = assignment.dueDate ?? null;
    editForm.status = assignment.status ?? 'published';
};

const submitEdit = () => {
    editForm.patch(route('faculty.assignments.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
};

const toggleStatus = (assignment) => {
    router.patch(route('faculty.assignments.status', assignment.id), {}, { preserveScroll: true });
};

const deleteAssignment = async (assignment) => {
    const ok = await confirm({
        title: 'Delete assignment',
        message: `Delete "${assignment.title}"? This also removes its submissions. This cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('faculty.assignments.destroy', assignment.id), { preserveScroll: true });
};

const assignmentKind = (assignment) => (assignment.isQuiz ? 'Quiz' : 'Assignment');

const tabs = computed(() => [
    { id: 'overview', label: 'Overview', icon: AcademicCapIcon },
    { id: 'students', label: `Students (${students.value.length})`, icon: UserGroupIcon },
    { id: 'materials', label: `Materials (${materials.value.length})`, icon: BookOpenIcon },
    { id: 'assignments', label: `Assignments (${assignments.value.length})`, icon: DocumentTextIcon },
]);

const statusVariant = (status) => ({
    completed: 'success',
    enrolled: 'info',
    dropped: 'danger',
}[status] || 'slate');

const formatDate = (date) => date
    ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
</script>

<template>
    <div>
        <Head :title="`${course.code} — ${course.name}`" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    :title="course.name"
                    :subtitle="`${course.code} • ${course.semester} • ${course.credits} credits`"
                    :icon="BookOpenIcon"
                    eyebrow="Course"
                >
                    <template #actions>
                        <div v-if="sections.length > 1" class="flex items-center gap-2">
                            <label for="course-section" class="text-sm text-content-muted">Section</label>
                            <select
                                id="course-section"
                                v-model="selectedSection"
                                class="ui-input h-9 py-0 max-w-[9rem]"
                                @change="changeSection"
                            >
                                <option v-for="s in sections" :key="s.id" :value="s.id">
                                    Section {{ s.label }}
                                </option>
                            </select>
                        </div>
                        <Link
                            href="/faculty/courses"
                            class="ui-btn-ghost"
                        >
                            <ArrowLeftIcon class="w-4 h-4" />
                            Courses
                        </Link>
                        <Link
                            :href="route('faculty.courses.attendance', { course: course.id, section: course.activeSectionId })"
                            class="ui-btn-secondary"
                        >
                            <ClipboardDocumentCheckIcon class="w-4 h-4" />
                            Attendance
                        </Link>
                    </template>
                </PageHeader>

                <!-- Summary stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <StatCard
                        label="Enrolled"
                        :value="`${course.enrollment?.current ?? students.length} / ${course.enrollment?.maximum ?? '—'}`"
                        :icon="UserGroupIcon"
                        color="emerald"
                        hint="Current capacity"
                    />
                    <StatCard
                        label="Students"
                        :value="students.length"
                        :icon="UserGroupIcon"
                        color="blue"
                    />
                    <StatCard
                        label="Materials"
                        :value="materials.length"
                        :icon="BookOpenIcon"
                        color="violet"
                    />
                    <StatCard
                        label="Assignments"
                        :value="assignments.length"
                        :icon="DocumentTextIcon"
                        color="amber"
                    />
                </div>

                <!-- Tabs -->
                <Card padding="p-0">
                    <div class="flex border-b border-line overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="`flex items-center gap-2 px-5 py-4 font-medium text-sm whitespace-nowrap transition-colors ${
                                activeTab === tab.id
                                    ? 'bg-primary-soft text-primary border-b-2 border-primary'
                                    : 'text-content-muted hover:text-content border-b-2 border-transparent'
                            }`"
                        >
                            <component :is="tab.icon" class="w-5 h-5" />
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <!-- Overview -->
                        <div v-if="activeTab === 'overview'" class="space-y-6">
                            <div v-if="course.description">
                                <h3 class="text-lg font-semibold text-content mb-2">Description</h3>
                                <p class="text-content-muted">{{ course.description }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-sm text-content-muted">
                                <Badge v-if="course.department" variant="success" dot>{{ course.department }}</Badge>
                                <Badge v-if="course.instructor" variant="slate">{{ course.instructor }}</Badge>
                            </div>

                            <div class="rounded-card border border-line bg-bg p-5">
                                <h4 class="font-semibold text-content mb-3 flex items-center gap-2">
                                    <CalendarIcon class="w-5 h-5 text-primary" />
                                    Schedule
                                </h4>
                                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div v-if="schedule.lectures" class="flex items-start gap-2">
                                        <PresentationChartLineIcon class="w-4 h-4 mt-0.5 text-content-faint flex-shrink-0" />
                                        <div>
                                            <dt class="text-content-muted">Lectures</dt>
                                            <dd class="text-content font-medium">{{ schedule.lectures }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="schedule.classroom" class="flex items-start gap-2">
                                        <MapPinIcon class="w-4 h-4 mt-0.5 text-content-faint flex-shrink-0" />
                                        <div>
                                            <dt class="text-content-muted">Classroom</dt>
                                            <dd class="text-content font-medium">{{ schedule.classroom }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="schedule.office_hours" class="flex items-start gap-2">
                                        <ClockIcon class="w-4 h-4 mt-0.5 text-content-faint flex-shrink-0" />
                                        <div>
                                            <dt class="text-content-muted">Office hours</dt>
                                            <dd class="text-content font-medium">{{ schedule.office_hours }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Students -->
                        <div v-else-if="activeTab === 'students'">
                            <EmptyState
                                v-if="students.length === 0"
                                title="No students enrolled yet"
                                description="Enrolled students will appear here once they join the course."
                                :icon="UserGroupIcon"
                            />
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Student</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Grade</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Progress</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="student in students"
                                            :key="student.id"
                                            class="border-b border-line hover:bg-bg transition-colors"
                                        >
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-content">{{ student.name }}</div>
                                                <div class="text-sm text-content-muted">{{ student.email }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-content-muted">{{ student.studentId ?? '—' }}</td>
                                            <td class="px-4 py-3 font-semibold text-content">{{ student.currentGrade ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-28 bg-neutral-bg rounded-full h-2">
                                                        <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: (student.progress ?? 0) + '%' }"></div>
                                                    </div>
                                                    <span class="text-xs text-content-muted">{{ student.progress ?? 0 }}%</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <Badge :variant="statusVariant(student.status)">
                                                    {{ student.status ?? 'enrolled' }}
                                                </Badge>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Materials -->
                        <div v-else-if="activeTab === 'materials'">
                            <div class="flex justify-end mb-4">
                                <button
                                    type="button"
                                    @click="showMaterialForm = !showMaterialForm"
                                    class="ui-btn-primary"
                                >
                                    <PlusIcon class="w-4 h-4" />
                                    Add Material
                                </button>
                            </div>

                            <!-- Add material form -->
                            <form
                                v-if="showMaterialForm"
                                @submit.prevent="submitMaterial"
                                class="mb-6 p-5 border border-line rounded-card bg-bg space-y-4"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="ui-label">Title</label>
                                        <input v-model="materialForm.title" type="text" class="ui-input" />
                                        <p v-if="materialForm.errors.title" class="text-xs text-danger-fg mt-1">{{ materialForm.errors.title }}</p>
                                    </div>
                                    <div>
                                        <label class="ui-label">Type</label>
                                        <select v-model="materialForm.type" class="ui-input">
                                            <option value="lecture">Lecture</option>
                                            <option value="slides">Slides</option>
                                            <option value="reading">Reading</option>
                                            <option value="assignment">Assignment</option>
                                            <option value="video">Video</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Week</label>
                                        <input v-model.number="materialForm.week" type="number" min="1" max="52" class="ui-input" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="ui-label">File (optional)</label>
                                        <input type="file" @input="materialForm.file = $event.target.files[0]"
                                            class="w-full text-sm text-content-muted file:mr-3 file:py-2 file:px-4 file:rounded-control file:border-0 file:bg-primary-soft file:text-primary hover:file:bg-primary-soft" />
                                        <p v-if="materialForm.errors.file" class="text-xs text-danger-fg mt-1">{{ materialForm.errors.file }}</p>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="materialForm.is_published" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                    <span class="text-sm text-content">Publish (visible to students)</span>
                                </label>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="showMaterialForm = false" class="ui-btn-secondary">Cancel</button>
                                    <button type="submit" :disabled="materialForm.processing" class="ui-btn-primary disabled:opacity-50">
                                        {{ materialForm.processing ? 'Saving…' : 'Save' }}
                                    </button>
                                </div>
                            </form>

                            <EmptyState
                                v-if="materials.length === 0"
                                title="No materials yet"
                                description="Upload lectures, slides, readings, or videos for your students."
                                :icon="BookOpenIcon"
                            />
                            <div v-else class="space-y-3">
                                <div
                                    v-for="material in materials"
                                    :key="material.id"
                                    class="flex items-center justify-between p-4 border border-line bg-surface rounded-card hover:bg-bg transition-colors"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-control bg-primary-soft flex items-center justify-center">
                                            <BookOpenIcon class="w-5 h-5 text-primary" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-content truncate flex items-center gap-2">
                                                {{ material.title }}
                                                <Badge v-if="!material.isPublished" variant="warning">
                                                    <EyeSlashIcon class="w-3 h-3" /> draft
                                                </Badge>
                                            </div>
                                            <div class="text-xs text-content-muted">
                                                {{ material.type }}<span v-if="material.week"> • Week {{ material.week }}</span>
                                                <span v-if="material.fileName"> • {{ material.fileName }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <span class="flex items-center gap-1 text-sm text-content-muted">
                                            <ArrowDownTrayIcon class="w-4 h-4" /> {{ material.downloads ?? 0 }}
                                        </span>
                                        <a
                                            v-if="material.downloadUrl"
                                            :href="material.downloadUrl"
                                            class="text-sm font-medium text-primary hover:underline"
                                        >
                                            Download
                                        </a>
                                        <button
                                            type="button"
                                            @click="deleteMaterial(material.id)"
                                            class="text-danger-fg hover:opacity-80 transition-colors"
                                            aria-label="Delete material"
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
                            <!-- Generate with AI (deep-links into the assistant's tools, course pre-selected) -->
                            <div class="mb-4 flex flex-wrap justify-end gap-2">
                                <Link :href="route('faculty.ai-assistant', { tool: 'quiz', course: course.name })" class="ui-btn-secondary">
                                    <SparklesIcon class="w-4 h-4" />
                                    Generate Quiz
                                </Link>
                                <Link :href="route('faculty.ai-assistant', { tool: 'assignment', course: course.name })" class="ui-btn-primary">
                                    <SparklesIcon class="w-4 h-4" />
                                    Generate Assignment
                                </Link>
                            </div>

                            <EmptyState
                                v-if="assignments.length === 0"
                                title="No assignments yet"
                                description="Use the AI Teaching Assistant to generate a quiz or assignment, then publish it to this course."
                                :icon="DocumentTextIcon"
                            >
                                <Link :href="route('faculty.ai-assistant', { tool: 'assignment', course: course.name })" class="ui-btn-primary">
                                    <SparklesIcon class="w-4 h-4" />
                                    Generate with AI
                                </Link>
                            </EmptyState>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="assignment in assignments"
                                    :key="assignment.id"
                                    class="p-4 border border-line bg-surface rounded-card hover:bg-bg transition-colors"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-content">{{ assignment.title }}</span>
                                                <Badge :variant="assignment.isQuiz ? 'info' : 'slate'">{{ assignmentKind(assignment) }}</Badge>
                                                <Badge :variant="assignment.status === 'published' ? 'success' : 'warning'" dot>
                                                    {{ assignment.status === 'published' ? 'Published' : 'Draft' }}
                                                </Badge>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-content-muted">
                                                <span class="capitalize">{{ assignment.type }}</span>
                                                <span class="flex items-center gap-1">
                                                    <CalendarIcon class="w-3.5 h-3.5" /> Due {{ formatDate(assignment.dueDate) }}
                                                </span>
                                                <span>{{ assignment.totalPoints }} pts</span>
                                                <span>{{ assignment.graded }} / {{ assignment.submissions }} graded</span>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-shrink-0 items-center gap-1">
                                            <button type="button" @click="openView(assignment)" class="ui-btn-ghost h-8 px-2" title="View details">
                                                <EyeIcon class="w-4 h-4" />
                                                <span class="hidden sm:inline">View</span>
                                            </button>
                                            <button type="button" @click="openEdit(assignment)" class="ui-btn-ghost h-8 px-2" title="Edit">
                                                <PencilSquareIcon class="w-4 h-4" />
                                                <span class="hidden sm:inline">Edit</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="toggleStatus(assignment)"
                                                class="ui-btn-ghost h-8 px-2"
                                                :title="assignment.status === 'published' ? 'Move to draft' : 'Publish'"
                                            >
                                                <component :is="assignment.status === 'published' ? EyeSlashIcon : ArrowUpTrayIcon" class="w-4 h-4" />
                                                <span class="hidden sm:inline">{{ assignment.status === 'published' ? 'Unpublish' : 'Publish' }}</span>
                                            </button>
                                            <button type="button" @click="deleteAssignment(assignment)" class="rounded-control p-1.5 text-danger-fg hover:bg-danger-bg" aria-label="Delete assignment" title="Delete">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- View assignment / quiz details -->
            <Teleport to="body">
            <div v-if="viewingAssignment" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="viewingAssignment = null"></div>
                    <div class="relative w-full max-w-2xl bg-surface rounded-card border border-line shadow-card max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-line bg-surface p-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-bold text-content">{{ viewingAssignment.title }}</h3>
                                    <Badge :variant="viewingAssignment.isQuiz ? 'info' : 'slate'">{{ assignmentKind(viewingAssignment) }}</Badge>
                                    <Badge :variant="viewingAssignment.status === 'published' ? 'success' : 'warning'" dot>
                                        {{ viewingAssignment.status === 'published' ? 'Published' : 'Draft' }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-xs text-content-muted">
                                    {{ viewingAssignment.totalPoints }} pts · Due {{ formatDate(viewingAssignment.dueDate) }} ·
                                    {{ viewingAssignment.graded }} / {{ viewingAssignment.submissions }} graded
                                </p>
                            </div>
                            <div class="flex flex-shrink-0 items-center gap-2">
                                <button type="button" @click="openEdit(viewingAssignment); viewingAssignment = null" class="ui-btn-secondary">
                                    <PencilSquareIcon class="w-4 h-4" /> Edit
                                </button>
                                <button type="button" @click="viewingAssignment = null" class="ui-btn-ghost h-9 w-9 p-0" aria-label="Close">
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div v-if="viewingAssignment.description">
                                <h4 class="text-sm font-semibold text-content mb-1">{{ viewingAssignment.isQuiz ? 'Questions' : 'Details' }}</h4>
                                <pre class="whitespace-pre-wrap font-sans text-sm text-content-muted">{{ viewingAssignment.description }}</pre>
                            </div>
                            <p v-else class="text-sm text-content-muted">No description provided.</p>

                            <div v-if="viewingAssignment.rubric && viewingAssignment.rubric.length" class="rounded-card border border-line p-4">
                                <h4 class="text-sm font-semibold text-content mb-2">Grading rubric</h4>
                                <ul class="space-y-1 text-sm text-content-muted">
                                    <li v-for="(c, i) in viewingAssignment.rubric" :key="i" class="flex items-center justify-between gap-3">
                                        <span>{{ c.criterion }}</span>
                                        <span class="font-medium text-content">{{ c.points }} pts</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </Teleport>

            <!-- Edit assignment / quiz -->
            <Teleport to="body">
            <div v-if="editingId" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="editingId = null"></div>
                    <form @submit.prevent="submitEdit" class="relative w-full max-w-2xl bg-surface rounded-card border border-line shadow-card max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-line bg-surface p-5">
                            <h3 class="text-lg font-bold text-content">Edit {{ editForm.type === 'quiz' ? 'quiz' : 'assignment' }}</h3>
                            <button type="button" @click="editingId = null" class="ui-btn-ghost h-9 w-9 p-0" aria-label="Close">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="ui-label">Title</label>
                                <input v-model="editForm.title" type="text" class="ui-input" />
                                <p v-if="editForm.errors.title" class="mt-1 text-xs text-danger-fg">{{ editForm.errors.title }}</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="ui-label">Type</label>
                                    <select v-model="editForm.type" class="ui-input">
                                        <option value="quiz">Quiz</option>
                                        <option value="homework">Homework</option>
                                        <option value="project">Project</option>
                                        <option value="research">Research</option>
                                        <option value="lab">Lab</option>
                                        <option value="case study">Case Study</option>
                                        <option value="presentation">Presentation</option>
                                        <option value="exam">Exam</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ui-label">Total points</label>
                                    <input v-model.number="editForm.total_points" type="number" min="1" max="1000" class="ui-input" />
                                    <p v-if="editForm.errors.total_points" class="mt-1 text-xs text-danger-fg">{{ editForm.errors.total_points }}</p>
                                </div>
                                <div>
                                    <label class="ui-label">Status</label>
                                    <select v-model="editForm.status" class="ui-input">
                                        <option value="published">Published</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="ui-label">Due date</label>
                                <input v-model="editForm.due_at" type="date" class="ui-input max-w-xs" />
                            </div>
                            <div>
                                <label class="ui-label">{{ editForm.type === 'quiz' ? 'Questions / content' : 'Description' }}</label>
                                <textarea v-model="editForm.description" rows="8" class="ui-input resize-y font-mono text-sm"></textarea>
                            </div>
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-line bg-surface p-5">
                            <button type="button" @click="editingId = null" class="ui-btn-secondary">Cancel</button>
                            <button type="submit" :disabled="editForm.processing" class="ui-btn-primary disabled:opacity-50">
                                {{ editForm.processing ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </Teleport>
        </AppLayout>
    </div>
</template>

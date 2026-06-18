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
    AcademicCapIcon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    XMarkIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    UserGroupIcon,
    UserPlusIcon,
    UserMinusIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    faculty: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    terms: { type: Array, default: () => [] },
});

const { confirm, notify } = useConfirm();

/* ---- Search + filters ---- */
const search = ref('');
const departmentFilter = ref(null);
const semesterFilter = ref(null);

const semesterOptions = computed(() =>
    [...new Set(props.courses.map((c) => c.semester).filter((s) => s != null))].sort((a, b) => a - b),
);

const filteredCourses = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.courses.filter((course) => {
        const matchesText = !q
            || course.code.toLowerCase().includes(q)
            || course.name.toLowerCase().includes(q);
        const matchesDept = departmentFilter.value === null || course.departmentId === departmentFilter.value;
        const matchesSem = semesterFilter.value === null || course.semester === semesterFilter.value;
        return matchesText && matchesDept && matchesSem;
    });
});

const expanded = ref(null);
const toggleExpand = (id) => { expanded.value = expanded.value === id ? null : id; };

/* ---- Course catalog ---- */
const showCourseModal = ref(false);
const editingCourseId = ref(null);
const isEditingCourse = computed(() => editingCourseId.value !== null);

const courseForm = useForm({
    code: '',
    name: '',
    description: '',
    department_id: null,
    semester: null,
    credits: 3,
});

const openCreateCourse = () => {
    editingCourseId.value = null;
    courseForm.reset();
    courseForm.clearErrors();
    showCourseModal.value = true;
};

const openEditCourse = (course) => {
    editingCourseId.value = course.id;
    courseForm.clearErrors();
    courseForm.code = course.code;
    courseForm.name = course.name;
    courseForm.description = course.description ?? '';
    courseForm.department_id = course.departmentId;
    courseForm.semester = course.semester;
    courseForm.credits = course.credits;
    showCourseModal.value = true;
};

const closeCourseModal = () => { showCourseModal.value = false; courseForm.reset(); courseForm.clearErrors(); };

const submitCourse = () => {
    const options = { preserveScroll: true, onSuccess: closeCourseModal };
    if (isEditingCourse.value) {
        courseForm.patch(route('admin.courses.update', editingCourseId.value), options);
    } else {
        courseForm.post(route('admin.courses.store'), options);
    }
};

const removeCourse = async (course) => {
    const enrolled = course.sections.reduce((n, s) => n + (s.studentCount || 0), 0);
    if (enrolled > 0) {
        await notify({ title: 'Cannot delete course', message: `"${course.code}" still has enrolled students.` });
        return;
    }
    const ok = await confirm({
        title: 'Delete course',
        message: `Permanently delete "${course.code} — ${course.name}" and all its sections?`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('admin.courses.destroy', course.id), { preserveScroll: true });
};

/* ---- Sections ---- */
const showSectionModal = ref(false);
const editingSectionId = ref(null);
const sectionCourse = ref(null);
const isEditingSection = computed(() => editingSectionId.value !== null);

const currentTermId = computed(() => props.terms.find((t) => t.is_current)?.id ?? props.terms[0]?.id ?? null);

const sectionForm = useForm({
    label: 'A',
    term_id: null,
    faculty_id: null,
    max_enrollment: 60,
    is_active: true,
    schedule: { lectures: '', classroom: '', office_hours: '' },
});

const openCreateSection = (course) => {
    sectionCourse.value = course;
    editingSectionId.value = null;
    sectionForm.reset();
    sectionForm.clearErrors();
    // Suggest the next section label and default to the current term.
    const used = course.sections.map((s) => s.label);
    sectionForm.label = ['A', 'B', 'C', 'D', 'E', 'F'].find((l) => !used.includes(l)) || '';
    sectionForm.term_id = currentTermId.value;
    showSectionModal.value = true;
};

const openEditSection = (course, section) => {
    sectionCourse.value = course;
    editingSectionId.value = section.id;
    sectionForm.clearErrors();
    sectionForm.label = section.label;
    sectionForm.term_id = section.termId;
    sectionForm.faculty_id = section.facultyId;
    sectionForm.max_enrollment = section.maxEnrollment;
    sectionForm.is_active = section.isActive;
    sectionForm.schedule = {
        lectures: section.schedule?.lectures ?? '',
        classroom: section.schedule?.classroom ?? '',
        office_hours: section.schedule?.office_hours ?? '',
    };
    showSectionModal.value = true;
};

const closeSectionModal = () => { showSectionModal.value = false; sectionForm.reset(); sectionForm.clearErrors(); };

const submitSection = () => {
    const options = { preserveScroll: true, onSuccess: closeSectionModal };
    if (isEditingSection.value) {
        sectionForm.patch(route('admin.sections.update', editingSectionId.value), options);
    } else {
        sectionForm.post(route('admin.sections.store', sectionCourse.value.id), options);
    }
};

const removeSection = async (section) => {
    if (section.studentCount > 0) {
        await notify({ title: 'Cannot delete section', message: `Section ${section.label} still has enrolled students.` });
        return;
    }
    const ok = await confirm({
        title: 'Delete section',
        message: `Delete section ${section.label}?`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('admin.sections.destroy', section.id), { preserveScroll: true });
};

/* ---- Roster (registrar enroll / drop) ---- */
const showRoster = ref(false);
const rosterSectionId = ref(null);
const enrollForm = useForm({ student_id: null });

// Derive the live section from props so the modal reflects enroll/drop reloads.
const rosterSection = computed(() => {
    for (const course of props.courses) {
        const section = (course.sections || []).find((s) => s.id === rosterSectionId.value);
        if (section) return { ...section, courseCode: course.code, courseName: course.name };
    }
    return null;
});

const enrolledRoster = computed(() => (rosterSection.value?.roster || []).filter((r) => r.status === 'enrolled'));

const availableStudents = computed(() => {
    const enrolledIds = new Set(enrolledRoster.value.map((r) => r.id));
    return props.students.filter((s) => !enrolledIds.has(s.id));
});

const openRoster = (section) => {
    rosterSectionId.value = section.id;
    enrollForm.reset();
    enrollForm.clearErrors();
    showRoster.value = true;
};
const closeRoster = () => { showRoster.value = false; rosterSectionId.value = null; enrollForm.reset(); };

const submitEnroll = () => {
    enrollForm.post(route('admin.sections.enroll', rosterSectionId.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => enrollForm.reset(),
    });
};

const dropStudent = async (student) => {
    const ok = await confirm({
        title: 'Drop student',
        message: `Drop ${student.name} from Section ${rosterSection.value?.label}?`,
        confirmLabel: 'Drop',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('admin.sections.drop', [rosterSectionId.value, student.id]), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div>
        <Head title="Courses" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Course Catalog"
                    subtitle="Manage catalog courses and assign faculty to sections (offerings)."
                    :icon="AcademicCapIcon"
                >
                    <template #actions>
                        <button type="button" @click="openCreateCourse" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" /> New course
                        </button>
                    </template>
                </PageHeader>

                <EmptyState
                    v-if="courses.length === 0"
                    title="No courses yet"
                    description="Create your first catalog course, then add sections and assign faculty."
                    :icon="AcademicCapIcon"
                >
                    <button type="button" @click="openCreateCourse" class="ui-btn-primary">
                        <PlusIcon class="w-4 h-4" /> New course
                    </button>
                </EmptyState>

                <div v-else class="space-y-4">
                    <!-- Search + filters -->
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-content-faint" />
                            <input v-model="search" type="search" placeholder="Search by code or name…" class="ui-input pl-9" />
                        </div>
                        <select v-model="departmentFilter" class="ui-input sm:w-56">
                            <option :value="null">All departments</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                        <select v-model="semesterFilter" class="ui-input sm:w-44">
                            <option :value="null">All semesters</option>
                            <option v-for="s in semesterOptions" :key="s" :value="s">Semester {{ s }}</option>
                        </select>
                    </div>

                    <p class="text-sm text-content-muted">Showing {{ filteredCourses.length }} of {{ courses.length }} course(s)</p>

                    <EmptyState
                        v-if="filteredCourses.length === 0"
                        title="No matching courses"
                        description="Try a different search term or clear the filters."
                        :icon="MagnifyingGlassIcon"
                    />

                    <Card v-for="course in filteredCourses" :key="course.id" padding="p-0">
                        <!-- Course row -->
                        <div class="flex items-start justify-between gap-3 p-4 sm:p-5">
                            <button type="button" class="flex items-start gap-3 min-w-0 text-left" @click="toggleExpand(course.id)">
                                <span class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                    <component :is="expanded === course.id ? ChevronDownIcon : ChevronRightIcon" class="w-5 h-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-content">{{ course.code }}</span>
                                        <Badge variant="violet">{{ course.credits }} cr</Badge>
                                        <Badge v-if="course.semester" variant="slate">Sem {{ course.semester }}</Badge>
                                        <Badge variant="primary">{{ course.sections.length }} section{{ course.sections.length === 1 ? '' : 's' }}</Badge>
                                    </span>
                                    <span class="block truncate text-sm text-content-muted mt-0.5">{{ course.name }}<span v-if="course.department"> · {{ course.department }}</span></span>
                                </span>
                            </button>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button type="button" @click="openEditCourse(course)" aria-label="Edit course" class="ui-btn-ghost p-2">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button type="button" @click="removeCourse(course)" aria-label="Delete course" class="ui-btn-danger p-2">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Sections -->
                        <div v-if="expanded === course.id" class="border-t border-line bg-bg/50 p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-content">Sections</h4>
                                <button type="button" @click="openCreateSection(course)" class="ui-btn-secondary text-sm">
                                    <PlusIcon class="w-4 h-4" /> Add section
                                </button>
                            </div>

                            <p v-if="course.sections.length === 0" class="text-sm text-content-muted">
                                No sections yet — add one and assign a faculty member.
                            </p>

                            <div
                                v-for="section in course.sections"
                                :key="section.id"
                                class="flex items-center justify-between gap-3 rounded-control border border-line bg-surface p-3"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-content">Section {{ section.label }}</span>
                                        <Badge v-if="section.term" variant="slate">{{ section.term }}</Badge>
                                        <Badge :variant="section.isActive ? 'success' : 'danger'" dot>{{ section.isActive ? 'Active' : 'Inactive' }}</Badge>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-content-muted mt-1">
                                        <span>{{ section.faculty || 'Unassigned' }}</span>
                                        <span class="inline-flex items-center gap-1.5"><UserGroupIcon class="w-4 h-4" /> {{ section.studentCount }}/{{ section.maxEnrollment }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" @click="openRoster(section)" aria-label="Manage roster" title="Manage roster" class="ui-btn-ghost p-2">
                                        <UserGroupIcon class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="openEditSection(course, section)" aria-label="Edit section" class="ui-btn-ghost p-2">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="removeSection(section)" aria-label="Delete section" class="ui-btn-danger p-2">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Course modal -->
            <div v-if="showCourseModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeCourseModal"></div>
                    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">{{ isEditingCourse ? 'Edit course' : 'New course' }}</h3>
                            <button type="button" @click="closeCourseModal" class="ui-btn-ghost p-2" aria-label="Close"><XMarkIcon class="h-5 w-5" /></button>
                        </div>
                        <form @submit.prevent="submitCourse" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-1">
                                        <label class="ui-label">Code *</label>
                                        <input v-model="courseForm.code" type="text" placeholder="CS301" class="ui-input" />
                                        <p v-if="courseForm.errors.code" class="text-xs text-danger-fg mt-1">{{ courseForm.errors.code }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="ui-label">Name *</label>
                                        <input v-model="courseForm.name" type="text" placeholder="Data Structures" class="ui-input" />
                                        <p v-if="courseForm.errors.name" class="text-xs text-danger-fg mt-1">{{ courseForm.errors.name }}</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="ui-label">Description</label>
                                    <textarea v-model="courseForm.description" rows="2" class="ui-input resize-none"></textarea>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="ui-label">Department</label>
                                        <select v-model="courseForm.department_id" class="ui-input">
                                            <option :value="null">—</option>
                                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Semester</label>
                                        <input v-model.number="courseForm.semester" type="number" min="1" max="12" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label">Credits *</label>
                                        <input v-model.number="courseForm.credits" type="number" min="1" max="12" class="ui-input" />
                                        <p v-if="courseForm.errors.credits" class="text-xs text-danger-fg mt-1">{{ courseForm.errors.credits }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeCourseModal" class="ui-btn-ghost">Cancel</button>
                                <button type="submit" :disabled="courseForm.processing" class="ui-btn-primary">
                                    {{ courseForm.processing ? 'Saving…' : (isEditingCourse ? 'Save changes' : 'Create course') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section modal -->
            <div v-if="showSectionModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeSectionModal"></div>
                    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">
                                {{ isEditingSection ? 'Edit section' : 'Add section' }}<span v-if="sectionCourse"> · {{ sectionCourse.code }}</span>
                            </h3>
                            <button type="button" @click="closeSectionModal" class="ui-btn-ghost p-2" aria-label="Close"><XMarkIcon class="h-5 w-5" /></button>
                        </div>
                        <form @submit.prevent="submitSection" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Section label *</label>
                                        <input v-model="sectionForm.label" type="text" placeholder="A" class="ui-input" />
                                        <p v-if="sectionForm.errors.label" class="text-xs text-danger-fg mt-1">{{ sectionForm.errors.label }}</p>
                                    </div>
                                    <div>
                                        <label class="ui-label">Term</label>
                                        <select v-model="sectionForm.term_id" class="ui-input">
                                            <option :value="null">—</option>
                                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}{{ t.is_current ? ' (current)' : '' }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="ui-label">Faculty (instructor)</label>
                                    <select v-model="sectionForm.faculty_id" class="ui-input">
                                        <option :value="null">Unassigned</option>
                                        <option v-for="f in faculty" :key="f.id" :value="f.id">{{ f.name }}</option>
                                    </select>
                                    <p v-if="sectionForm.errors.faculty_id" class="text-xs text-danger-fg mt-1">{{ sectionForm.errors.faculty_id }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Max enrollment *</label>
                                        <input v-model.number="sectionForm.max_enrollment" type="number" min="1" max="1000" class="ui-input" />
                                        <p v-if="sectionForm.errors.max_enrollment" class="text-xs text-danger-fg mt-1">{{ sectionForm.errors.max_enrollment }}</p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-content-muted self-end pb-2.5">
                                        <input v-model="sectionForm.is_active" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                        Active
                                    </label>
                                </div>
                                <div>
                                    <label class="ui-label">Lecture schedule</label>
                                    <input v-model="sectionForm.schedule.lectures" type="text" placeholder="Mon/Wed 10:00–11:30" class="ui-input" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Classroom</label>
                                        <input v-model="sectionForm.schedule.classroom" type="text" placeholder="Room 101" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label">Office hours</label>
                                        <input v-model="sectionForm.schedule.office_hours" type="text" placeholder="Tue 14:00–16:00" class="ui-input" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeSectionModal" class="ui-btn-ghost">Cancel</button>
                                <button type="submit" :disabled="sectionForm.processing" class="ui-btn-primary">
                                    {{ sectionForm.processing ? 'Saving…' : (isEditingSection ? 'Save changes' : 'Add section') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Roster modal (registrar enroll / drop) -->
            <div v-if="showRoster && rosterSection" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeRoster"></div>
                    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">
                                Roster · {{ rosterSection.courseCode }} Section {{ rosterSection.label }}
                                <span class="text-content-muted font-normal">({{ enrolledRoster.length }}/{{ rosterSection.maxEnrollment }})</span>
                            </h3>
                            <button type="button" @click="closeRoster" class="ui-btn-ghost p-2" aria-label="Close"><XMarkIcon class="h-5 w-5" /></button>
                        </div>

                        <div class="flex min-h-0 flex-1 flex-col">
                            <!-- Enroll form -->
                            <form @submit.prevent="submitEnroll" class="flex items-end gap-3 border-b border-line p-6">
                                <div class="flex-1">
                                    <label class="ui-label">Enroll a student</label>
                                    <select v-model="enrollForm.student_id" class="ui-input">
                                        <option :value="null">Select a student…</option>
                                        <option v-for="s in availableStudents" :key="s.id" :value="s.id">
                                            {{ s.name }}<span v-if="s.student_id"> ({{ s.student_id }})</span>
                                        </option>
                                    </select>
                                    <p v-if="enrollForm.errors.student_id" class="text-xs text-danger-fg mt-1">{{ enrollForm.errors.student_id }}</p>
                                </div>
                                <button
                                    type="submit"
                                    class="ui-btn-primary"
                                    :disabled="enrollForm.processing || !enrollForm.student_id || enrolledRoster.length >= rosterSection.maxEnrollment"
                                >
                                    <UserPlusIcon class="w-4 h-4" /> Enroll
                                </button>
                            </form>

                            <!-- Current roster -->
                            <div class="flex-1 overflow-y-auto p-6 space-y-2">
                                <p v-if="enrolledRoster.length === 0" class="text-sm text-content-muted">No students enrolled yet.</p>
                                <div
                                    v-for="student in enrolledRoster"
                                    :key="student.id"
                                    class="flex items-center justify-between gap-3 rounded-control border border-line bg-surface p-3"
                                >
                                    <div class="min-w-0">
                                        <p class="font-medium text-content truncate">{{ student.name }}</p>
                                        <p v-if="student.studentId" class="text-xs text-content-muted">{{ student.studentId }}</p>
                                    </div>
                                    <button type="button" @click="dropStudent(student)" aria-label="Drop student" class="ui-btn-danger p-2">
                                        <UserMinusIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

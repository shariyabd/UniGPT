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
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    EyeSlashIcon,
    ArrowLeftIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    MapPinIcon,
    PresentationChartLineIcon,
} from '@heroicons/vue/24/outline';

// Real data from the backend (CourseController@show → CourseService::courseDetail).
const props = defineProps({
    course: { type: Object, required: true },
});

const activeTab = ref('overview');

// --- Material management ---
const showMaterialForm = ref(false);
const materialForm = useForm({
    title: '',
    description: '',
    type: 'lecture',
    week: null,
    is_published: true,
    file: null,
});

const submitMaterial = () => {
    materialForm.post(route('faculty.courses.materials.store', props.course.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            materialForm.reset();
            showMaterialForm.value = false;
        },
    });
};

const deleteMaterial = (materialId) => {
    if (!confirm('Delete this material? This cannot be undone.')) return;
    router.delete(route('faculty.courses.materials.destroy', [props.course.id, materialId]), {
        preserveScroll: true,
    });
};

const deleteCourse = () => {
    if (!confirm(`Delete ${props.course.code}? This removes the course and its materials.`)) return;
    router.delete(route('faculty.courses.destroy', props.course.id));
};

const students = computed(() => props.course.students ?? []);
const materials = computed(() => props.course.materials ?? []);
const assignments = computed(() => props.course.assignments ?? []);
const schedule = computed(() => props.course.schedule ?? {});

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
                        <Link
                            href="/faculty/courses"
                            class="ui-btn-ghost"
                        >
                            <ArrowLeftIcon class="w-4 h-4" />
                            Courses
                        </Link>
                        <Link
                            :href="route('faculty.courses.attendance', course.id)"
                            class="ui-btn-secondary"
                        >
                            <ClipboardDocumentCheckIcon class="w-4 h-4" />
                            Attendance
                        </Link>
                        <Link
                            :href="route('faculty.courses.edit', course.id)"
                            class="ui-btn-secondary"
                        >
                            <PencilSquareIcon class="w-4 h-4" />
                            Edit
                        </Link>
                        <button
                            type="button"
                            @click="deleteCourse"
                            class="ui-btn-danger"
                        >
                            <TrashIcon class="w-4 h-4" />
                            Delete
                        </button>
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
                            <EmptyState
                                v-if="assignments.length === 0"
                                title="No assignments created yet"
                                description="Assignments you create will be listed here with submission progress."
                                :icon="DocumentTextIcon"
                            />
                            <div v-else class="space-y-3">
                                <div
                                    v-for="assignment in assignments"
                                    :key="assignment.id"
                                    class="p-4 border border-line bg-surface rounded-card hover:bg-bg transition-colors"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-medium text-content">{{ assignment.title }}</div>
                                            <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-content-muted">
                                                <span>{{ assignment.type }}</span>
                                                <span class="flex items-center gap-1">
                                                    <CalendarIcon class="w-3.5 h-3.5" /> Due {{ formatDate(assignment.dueDate) }}
                                                </span>
                                                <span>{{ assignment.totalPoints }} pts</span>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm">
                                            <div class="font-semibold text-content">
                                                {{ assignment.graded }} / {{ assignment.submissions }}
                                            </div>
                                            <div class="text-xs text-content-muted">graded</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

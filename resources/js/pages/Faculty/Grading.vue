<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    PencilIcon,
    MagnifyingGlassIcon,
    CalendarIcon,
    SparklesIcon,
    ClipboardDocumentCheckIcon,
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

// Similarity screening runs on embeddings, so scores are a review signal —
// format as a percentage for display.
const similarityPercent = (score) => `${Math.round((score ?? 0) * 100)}%`;

// Component state
// 'all' shows submissions across every assignment (the default); a numeric id
// narrows to one assignment.
const selectedAssignment = ref('all');
const selectedSubmission = ref(null);
const showGradingPanel = ref(false);
const filterStatus = ref('all');
const sortBy = ref('submitted_date');
const searchQuery = ref('');
const currentGrade = ref('');
const currentFeedback = ref('');
const currentRubricScores = ref({});
const isGrading = ref(false);

// Real data from the backend (GradingController@index → GradingService::overview)
const props = defineProps({
    courseData: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    activeSectionId: { type: [Number, null], default: null },
    assignments: { type: Array, default: () => [] },
    submissions: { type: Array, default: () => [] },
    courseId: { type: Number, default: null },
    // True when showing every course/section the faculty teaches (the default).
    allCourses: { type: Boolean, default: false },
});

// A faculty teaching several sections of a course grades one section at a time.
const selectedSection = ref(props.activeSectionId);

// The grading view defaults to "All Courses" (null) and lets faculty narrow to
// any single course they teach.
const selectedCourse = ref(props.courseId ?? null);

const changeCourse = () => {
    // "All Courses" (null) goes back to the aggregated index; a specific course
    // loads its scoped grading view.
    router.get(
        selectedCourse.value
            ? route('faculty.course.grading', selectedCourse.value)
            : route('faculty.grading'),
        {},
        { preserveState: false, preserveScroll: true },
    );
};

const changeSection = () => {
    if (!props.courseData?.id) return;
    router.get(
        route('faculty.course.grading', props.courseData.id),
        { section: selectedSection.value },
        { preserveState: false, preserveScroll: true },
    );
};

const courseData = computed(() => props.courseData ?? { code: '—', name: 'No course assigned', students: 0 });

// Local working copies seeded from the server props. A successful grade triggers
// an Inertia reload that updates the props (without remounting this component),
// so we re-sync the local copies whenever the props change.
const assignments = ref([...props.assignments]);
const submissions = ref([...props.submissions]);

watch(() => props.assignments, (value) => { assignments.value = [...value]; });
watch(() => props.submissions, (value) => { submissions.value = [...value]; });

// Filter and sort options
const statusOptions = [
    { value: 'all', label: 'All Submissions' },
    { value: 'submitted', label: 'Pending Grading' },
    { value: 'graded', label: 'Graded' },
    { value: 'late', label: 'Late Submissions' }
];

const sortOptions = [
    { value: 'submitted_date', label: 'Submission Date' },
    { value: 'student_name', label: 'Student Name' },
    { value: 'grade', label: 'Grade' }
];

// Computed properties — null in "All Assignments" mode (no single assignment).
const currentAssignment = computed(() => {
    if (selectedAssignment.value === 'all') return null;
    return assignments.value.find(a => a.id === selectedAssignment.value) || null;
});

// The assignment shown in the grading modal: always the one the selected
// submission belongs to. In "All Assignments" mode currentAssignment is null,
// so the modal must resolve the assignment from the submission itself —
// otherwise opening it dereferences null and the Grade button appears broken.
const gradingAssignment = computed(() => {
    if (selectedSubmission.value) {
        return assignments.value.find(a => a.id === selectedSubmission.value.assignmentId)
            ?? currentAssignment.value;
    }
    return currentAssignment.value;
});

const filteredSubmissions = computed(() => {
    // "All Assignments" shows every submission; otherwise scope to the selected one.
    let filtered = selectedAssignment.value === 'all'
        ? [...submissions.value]
        : submissions.value.filter(s => s.assignmentId === selectedAssignment.value);

    // Apply status filter
    if (filterStatus.value !== 'all') {
        switch (filterStatus.value) {
            case 'submitted':
                filtered = filtered.filter(s => s.status === 'submitted');
                break;
            case 'graded':
                filtered = filtered.filter(s => s.status === 'graded');
                break;
            case 'late':
                filtered = filtered.filter(s => s.isLate);
                break;
        }
    }

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(s =>
            s.student.name.toLowerCase().includes(query) ||
            s.student.email.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    switch (sortBy.value) {
        case 'student_name':
            filtered.sort((a, b) => a.student.name.localeCompare(b.student.name));
            break;
        case 'grade':
            filtered.sort((a, b) => (b.grade || 0) - (a.grade || 0));
            break;
        case 'submitted_date':
        default:
            filtered.sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt));
    }

    return filtered;
});

const hasAssignments = computed(() => assignments.value.length > 0);

const gradingStats = computed(() => {
    // Aggregate across the selected assignment, or all of them in "All" mode.
    const list = currentAssignment.value ? [currentAssignment.value] : assignments.value;

    if (list.length === 0) {
        return { completionRate: 0, pendingCount: 0, averageGrade: 0, totalSubmissions: 0 };
    }

    const total = list.reduce((sum, a) => sum + a.submissions.total, 0);
    const graded = list.reduce((sum, a) => sum + a.submissions.graded, 0);
    const pending = list.reduce((sum, a) => sum + a.submissions.pending, 0);
    const withAvg = list.filter(a => a.averageGrade != null);
    const averageGrade = withAvg.length
        ? Math.round((withAvg.reduce((sum, a) => sum + a.averageGrade, 0) / withAvg.length) * 10) / 10
        : 0;

    return {
        completionRate: total > 0 ? Math.round((graded / total) * 100) : 0,
        pendingCount: pending,
        averageGrade,
        totalSubmissions: total,
    };
});

// Client-side pagination over the filtered submissions list.
const PER_PAGE = 15;
const currentPage = ref(1);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSubmissions.value.length / PER_PAGE)));

const paginatedSubmissions = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredSubmissions.value.slice(start, start + PER_PAGE);
});

// Any filter/scope change resets to the first page so results aren't hidden.
watch([filterStatus, sortBy, searchQuery, selectedAssignment, () => props.submissions], () => {
    currentPage.value = 1;
});

const goToPage = (page) => {
    currentPage.value = Math.min(Math.max(1, page), totalPages.value);
};

// Utility functions
const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const getStatusColor = (status, isLate = false) => {
    if (isLate) return 'bg-warning-bg text-warning-fg';

    const colors = {
        submitted: 'bg-primary-soft text-primary',
        graded: 'bg-success-bg text-success-fg',
        missing: 'bg-danger-bg text-danger-fg'
    };
    return colors[status] || colors.submitted;
};

const getStatusVariant = (status, isLate = false) => {
    if (isLate) return 'warning';
    const variants = {
        submitted: 'info',
        graded: 'success',
        missing: 'danger',
    };
    return variants[status] || 'info';
};

const getGradeColor = (grade) => {
    if (grade >= 90) return 'text-success-fg';
    if (grade >= 80) return 'text-primary';
    if (grade >= 70) return 'text-warning-fg';
    return 'text-danger-fg';
};

// Actions
const selectAssignment = (assignmentId) => {
    selectedAssignment.value = assignmentId;
};

const openGradingPanel = (submission) => {
    selectedSubmission.value = submission;
    currentGrade.value = submission.grade || '';
    currentFeedback.value = submission.feedback || '';
    // Seed rubric inputs from any previously-saved per-criterion scores.
    currentRubricScores.value = { ...(submission.rubricScores || {}) };
    aiJustifications.value = {};
    aiDraftSource.value = null;
    showGradingPanel.value = true;
};

const closeGradingPanel = () => {
    showGradingPanel.value = false;
    selectedSubmission.value = null;
    currentGrade.value = '';
    currentFeedback.value = '';
    currentRubricScores.value = {};
    aiJustifications.value = {};
    aiDraftSource.value = null;
};

// Running total of the rubric inputs; lets faculty apply it as the grade.
const rubricTotal = computed(() =>
    Object.values(currentRubricScores.value).reduce(
        (sum, value) => sum + (parseFloat(value) || 0),
        0,
    ),
);

const applyRubricTotal = () => {
    currentGrade.value = rubricTotal.value;
};

const toast = useToast();
const isDraftingFeedback = ref(false);

// --- AI grade draft ------------------------------------------------------
// Prefills rubric scores + grade + feedback from the submission text; the
// faculty member reviews and edits before saving.
const isDraftingGrade = ref(false);
const aiJustifications = ref({});
const aiDraftSource = ref(null);

const formatDraftFeedback = (data) => {
    const parts = [data.feedback];
    if (data.strengths?.length) {
        parts.push('\nStrengths:\n' + data.strengths.map((s) => `• ${s}`).join('\n'));
    }
    if (data.improvements?.length) {
        parts.push('\nTo improve:\n' + data.improvements.map((s) => `• ${s}`).join('\n'));
    }
    return parts.filter(Boolean).join('\n');
};

const draftGrade = async () => {
    if (!selectedSubmission.value) return;

    isDraftingGrade.value = true;

    try {
        const { data } = await axios.post(
            route('faculty.submissions.draft-grade', selectedSubmission.value.id)
        );

        (data.criteria || []).forEach((criterion) => {
            currentRubricScores.value[criterion.name] = criterion.score;
            if (criterion.justification) {
                aiJustifications.value[criterion.name] = criterion.justification;
            }
        });
        if (data.suggestedGrade !== null && data.suggestedGrade !== undefined) {
            currentGrade.value = data.suggestedGrade;
        }
        currentFeedback.value = formatDraftFeedback(data);
        aiDraftSource.value = data.source;
    } catch (e) {
        toast.error('Could not draft a grade. Please try again.');
    } finally {
        isDraftingGrade.value = false;
    }
};

const draftFeedback = async () => {
    if (!selectedSubmission.value) return;

    isDraftingFeedback.value = true;

    try {
        const { data } = await axios.post(
            route('faculty.submissions.feedback', selectedSubmission.value.id)
        );

        const parts = [data.feedback];
        if (data.strengths?.length) {
            parts.push('\nStrengths:\n' + data.strengths.map((s) => `• ${s}`).join('\n'));
        }
        if (data.improvements?.length) {
            parts.push('\nTo improve:\n' + data.improvements.map((s) => `• ${s}`).join('\n'));
        }
        currentFeedback.value = parts.filter(Boolean).join('\n');
    } catch (e) {
        toast.error('Could not draft feedback. Please try again.');
    } finally {
        isDraftingFeedback.value = false;
    }
};

const submitGrade = () => {
    if (!selectedSubmission.value || currentGrade.value === '') return;

    isGrading.value = true;

    const rubricScores = Object.keys(currentRubricScores.value).length
        ? currentRubricScores.value
        : null;

    router.post(route('faculty.submissions.grade', selectedSubmission.value.id), {
        grade: parseFloat(currentGrade.value),
        feedback: currentFeedback.value || null,
        rubric_scores: rubricScores,
    }, {
        preserveScroll: true,
        // A fresh visit re-seeds assignments/submissions from the server.
        onSuccess: () => {
            // Success toast comes from the server flash (centralized pipeline).
            closeGradingPanel();
        },
        onError: (errors) => {
            toast.error(Object.values(errors)[0] || 'Could not save the grade.');
        },
        onFinish: () => {
            isGrading.value = false;
        },
    });
};

</script>

<template>
    <div>
        <Head title="Grading Interface" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Grading"
                    :subtitle="`${courseData.code} · ${courseData.name} · ${courseData.students} students`"
                    :icon="ClipboardDocumentCheckIcon"
                    eyebrow="Faculty"
                >
                    <template #actions>
                        <div class="flex items-center gap-3">
                            <div class="hidden sm:flex flex-col items-end rounded-card border border-success-fg/20 bg-success-bg px-4 py-2">
                                <span class="text-xs font-medium text-success-fg">Grading Progress</span>
                                <span class="text-xl font-bold text-success-fg">{{ gradingStats.completionRate }}%</span>
                                <span class="text-[11px] text-success-fg/80">{{ gradingStats.pendingCount }} pending</span>
                            </div>
                            <Link href="/faculty/dashboard" class="ui-btn-secondary">
                                <ArrowLeftIcon class="w-4 h-4" />
                                Dashboard
                            </Link>
                        </div>
                    </template>
                </PageHeader>

                <!-- Assignment Selection & Stats -->
                <Card>
                    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                        <!-- Course + Assignment + Section Selectors -->
                        <div class="flex flex-1 flex-wrap items-end gap-4">
                            <div v-if="courses.length >= 1" class="min-w-[14rem]">
                                <label class="ui-label">Course</label>
                                <select
                                    v-model="selectedCourse"
                                    class="ui-input"
                                    @change="changeCourse"
                                >
                                    <option :value="null">All Courses</option>
                                    <option v-for="c in courses" :key="c.id" :value="c.id">
                                        {{ c.code }} — {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex-1 min-w-[14rem]">
                                <label class="ui-label">Select Assignment to Grade</label>
                                <select
                                    v-if="hasAssignments"
                                    v-model="selectedAssignment"
                                    @change="selectAssignment(selectedAssignment)"
                                    class="ui-input max-w-md"
                                >
                                    <option value="all">All Assignments ({{ assignments.length }})</option>
                                    <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                        <template v-if="allCourses">{{ assignment.courseCode }} · </template>{{ assignment.title }} ({{ assignment.submissions.pending }} pending)
                                    </option>
                                </select>
                                <p v-else class="ui-input max-w-md bg-neutral-bg text-content-muted">
                                    No assignments yet
                                </p>
                            </div>

                            <div v-if="sections.length > 1">
                                <label class="ui-label">Section</label>
                                <select
                                    v-model="selectedSection"
                                    class="ui-input max-w-[9rem]"
                                    @change="changeSection"
                                >
                                    <option v-for="s in sections" :key="s.id" :value="s.id">
                                        Section {{ s.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Assignment Stats -->
                        <div v-if="currentAssignment" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="text-center rounded-card bg-bg px-4 py-3">
                                <div class="text-2xl font-bold text-content">{{ currentAssignment.submissions.total }}</div>
                                <div class="text-xs text-content-muted">Total</div>
                            </div>
                            <div class="text-center rounded-card bg-bg px-4 py-3">
                                <div class="text-2xl font-bold text-success-fg">{{ currentAssignment.submissions.graded }}</div>
                                <div class="text-xs text-content-muted">Graded</div>
                            </div>
                            <div class="text-center rounded-card bg-bg px-4 py-3">
                                <div class="text-2xl font-bold text-warning-fg">{{ currentAssignment.submissions.pending }}</div>
                                <div class="text-xs text-content-muted">Pending</div>
                            </div>
                            <div class="text-center rounded-card bg-bg px-4 py-3">
                                <div class="text-2xl font-bold text-danger-fg">{{ currentAssignment.submissions.late }}</div>
                                <div class="text-xs text-content-muted">Late</div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Details -->
                    <div v-if="currentAssignment" class="mt-6 p-4 bg-bg rounded-card">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-content-muted">Type:</span>
                                <span class="ml-2 text-content">{{ currentAssignment.type }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-content-muted">Due Date:</span>
                                <span class="ml-2 text-content">{{ formatDate(currentAssignment.dueDate) }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-content-muted">Total Points:</span>
                                <span class="ml-2 font-bold text-content">{{ currentAssignment.totalPoints }}</span>
                            </div>
                        </div>
                        <div v-if="currentAssignment.averageGrade" class="mt-2 text-sm">
                            <span class="font-medium text-content-muted">Average Grade:</span>
                            <span :class="`ml-2 font-bold ${getGradeColor(currentAssignment.averageGrade)}`">
                                {{ currentAssignment.averageGrade.toFixed(1) }}%
                            </span>
                        </div>
                    </div>

                    <!-- No assignments yet for this course/section -->
                    <EmptyState
                        v-if="!hasAssignments"
                        :icon="DocumentTextIcon"
                        title="No assignments to grade yet"
                        :description="props.courseData
                            ? 'This section has no assignments. Create an assignment to start collecting and grading submissions.'
                            : 'You are not assigned to any teaching section yet.'"
                    />
                </Card>

                <!-- Filters and Controls -->
                <Card v-if="hasAssignments">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-content-faint" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search students..."
                                class="ui-input pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <select v-model="filterStatus" class="ui-input sm:w-auto">
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <!-- Sort By -->
                        <select v-model="sortBy" class="ui-input sm:w-auto">
                            <option v-for="sort in sortOptions" :key="sort.value" :value="sort.value">
                                Sort by {{ sort.label }}
                            </option>
                        </select>
                    </div>
                </Card>

                <!-- Submissions List -->
                <Card v-if="hasAssignments" padding="p-0">
                    <!-- List Header -->
                    <div class="px-5 sm:px-6 py-3 border-b border-line">
                        <span class="text-sm font-medium text-content-muted">
                            {{ filteredSubmissions.length }} Submissions
                        </span>
                    </div>

                    <!-- Submissions -->
                    <div class="divide-y divide-line">
                        <div
                            v-for="submission in paginatedSubmissions"
                            :key="submission.id"
                            class="p-5 sm:p-6 hover:bg-bg transition-colors"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Student Avatar -->
                                <img
                                    :src="submission.student.avatar"
                                    :alt="submission.student.name"
                                    class="w-12 h-12 rounded-full flex-shrink-0 ring-2 ring-line"
                                />

                                <!-- Submission Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-content">
                                                {{ submission.student.name }}
                                            </h3>
                                            <p class="text-sm text-content-muted truncate">
                                                {{ submission.student.email }}
                                            </p>
                                            <p v-if="selectedAssignment === 'all'" class="mt-1 text-xs font-medium text-primary">
                                                <span v-if="submission.courseCode">{{ submission.courseCode }} · </span>{{ submission.assignmentTitle }}
                                            </p>

                                            <!-- Submission Info -->
                                            <div class="flex flex-wrap items-center gap-3 mt-2 text-sm">
                                                <div class="flex items-center gap-1.5">
                                                    <CalendarIcon class="w-4 h-4 text-content-faint" />
                                                    <span class="text-content-muted">
                                                        {{ formatDateTime(submission.submittedAt) }}
                                                    </span>
                                                </div>

                                                <Badge :variant="getStatusVariant(submission.status, submission.isLate)">
                                                    {{ submission.isLate ? 'Late' : submission.status }}
                                                </Badge>

                                                <span
                                                    v-if="submission.peerReview"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-pill bg-primary-soft text-primary border border-primary/20"
                                                    title="Average anonymous peer rating"
                                                >
                                                    Peer {{ submission.peerReview.average }}★ ({{ submission.peerReview.completed }})
                                                </span>

                                                <span
                                                    v-if="submission.similarity"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-pill bg-warning-bg text-warning-fg border border-warning-fg/20"
                                                    title="High similarity with another submission — open the grading panel for details"
                                                >
                                                    <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                                                    {{ similarityPercent(submission.similarity.maxScore) }} similarity
                                                </span>
                                            </div>

                                            <!-- Existing Grade & Feedback -->
                                            <div v-if="submission.status === 'graded'" class="mt-4 p-3 bg-success-bg border border-success-fg/20 rounded-card">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <CheckCircleIcon class="w-4 h-4 text-success-fg" />
                                                    <span class="text-sm font-medium text-success-fg">
                                                        Graded: {{ submission.grade }}/{{ submission.totalPoints }}
                                                    </span>
                                                    <span :class="`font-bold ${getGradeColor(submission.grade)}`">
                                                        ({{ ((submission.grade / submission.totalPoints) * 100).toFixed(1) }}%)
                                                    </span>
                                                </div>
                                                <p v-if="submission.feedback" class="text-sm text-content-muted whitespace-pre-line">
                                                    {{ submission.feedback }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Grade Display -->
                                        <div class="text-right flex-shrink-0">
                                            <div v-if="submission.grade !== null" :class="`text-3xl font-bold ${getGradeColor(submission.grade)}`">
                                                {{ submission.grade }}
                                            </div>
                                            <div v-else class="text-3xl font-bold text-content-faint">
                                                —
                                            </div>
                                            <div class="text-xs text-content-muted">
                                                / {{ submission.totalPoints }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2 mt-4">
                                        <button
                                            @click="openGradingPanel(submission)"
                                            class="ui-btn-primary"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                            {{ submission.status === 'graded' ? 'Re-grade' : 'Grade' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <EmptyState
                        v-if="filteredSubmissions.length === 0"
                        :icon="DocumentTextIcon"
                        title="No submissions found"
                        description="Try adjusting your filters or search terms."
                    />

                    <!-- Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="flex items-center justify-between gap-4 border-t border-line px-5 sm:px-6 py-3"
                    >
                        <span class="text-sm text-content-muted">
                            Page {{ currentPage }} of {{ totalPages }} · {{ filteredSubmissions.length }} results
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                class="ui-btn-secondary"
                                :disabled="currentPage === 1"
                                @click="goToPage(currentPage - 1)"
                            >
                                Previous
                            </button>
                            <button
                                class="ui-btn-secondary"
                                :disabled="currentPage === totalPages"
                                @click="goToPage(currentPage + 1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Grading Panel Modal -->
            <Teleport to="body">
            <div v-if="showGradingPanel" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeGradingPanel"></div>

                    <div class="relative w-full max-w-4xl bg-surface rounded-card border border-line shadow-card max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-content">
                                        Grade Submission
                                    </h3>
                                    <p class="text-content-muted">
                                        {{ selectedSubmission?.student.name }} - {{ gradingAssignment?.title }}
                                    </p>
                                </div>
                                <button
                                    @click="closeGradingPanel"
                                    aria-label="Close grading panel"
                                    class="p-2 text-content-faint hover:text-content-muted rounded-control hover:bg-bg transition-colors"
                                >
                                    <XCircleIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Left Column - Submission Info -->
                                <div>
                                    <!-- Student Info -->
                                    <div class="mb-6 p-4 bg-bg rounded-card">
                                        <div class="flex items-center gap-3 mb-3">
                                            <img
                                                :src="selectedSubmission?.student.avatar"
                                                :alt="selectedSubmission?.student.name"
                                                class="w-12 h-12 rounded-full ring-2 ring-line"
                                            />
                                            <div>
                                                <h4 class="font-semibold text-content">
                                                    {{ selectedSubmission?.student.name }}
                                                </h4>
                                                <p class="text-sm text-content-muted">
                                                    {{ selectedSubmission?.student.email }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="font-medium text-content-muted">Submitted:</span>
                                                <div class="text-content">
                                                    {{ formatDateTime(selectedSubmission?.submittedAt) }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="font-medium text-content-muted">Status:</span>
                                                <span :class="`ml-1 px-2 py-1 text-xs rounded-pill ${getStatusColor(selectedSubmission?.status, selectedSubmission?.isLate)}`">
                                                    {{ selectedSubmission?.isLate ? 'Late' : selectedSubmission?.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submitted Work -->
                                    <div class="mt-6">
                                        <label class="ui-label">Submitted Work</label>
                                        <div
                                            v-if="selectedSubmission?.content"
                                            class="mt-1 max-h-64 overflow-y-auto whitespace-pre-wrap rounded-card border border-line bg-bg p-4 text-sm text-content"
                                        >{{ selectedSubmission.content }}</div>
                                        <a
                                            v-if="selectedSubmission?.fileUrl"
                                            :href="selectedSubmission.fileUrl"
                                            class="ui-btn-secondary mt-3 inline-flex"
                                        >
                                            <ArrowDownTrayIcon class="h-4 w-4" />
                                            {{ selectedSubmission.fileName || 'Download attachment' }}
                                        </a>
                                        <p
                                            v-if="!selectedSubmission?.content && !selectedSubmission?.fileUrl"
                                            class="mt-1 text-sm text-content-muted"
                                        >No written response or attachment was submitted.</p>
                                    </div>

                                    <!-- Similarity Screening -->
                                    <div v-if="selectedSubmission?.similarity" class="mt-6">
                                        <label class="ui-label flex items-center gap-1.5">
                                            <ExclamationTriangleIcon class="w-4 h-4 text-warning-fg" />
                                            Similarity check
                                        </label>
                                        <div class="mt-1 space-y-3">
                                            <div
                                                v-for="match in selectedSubmission.similarity.matches"
                                                :key="match.submissionId"
                                                class="rounded-card border border-warning-fg/30 bg-warning-bg/40 p-4"
                                            >
                                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                                    <span class="font-semibold text-content">{{ match.student || 'Another student' }}</span>
                                                    <span class="px-2 py-0.5 text-xs font-bold rounded-pill bg-warning-bg text-warning-fg border border-warning-fg/20">
                                                        {{ similarityPercent(match.score) }} match
                                                    </span>
                                                    <span class="text-xs text-content-muted">
                                                        {{ similarityPercent(match.coverage) }} of this submission overlaps
                                                    </span>
                                                </div>
                                                <div v-if="match.pairs?.length" class="mt-3 space-y-2">
                                                    <div
                                                        v-for="(pair, pairIndex) in match.pairs"
                                                        :key="pairIndex"
                                                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
                                                    >
                                                        <div class="rounded-control border border-line bg-surface p-2.5">
                                                            <p class="font-semibold text-content-muted mb-1">This submission</p>
                                                            <p class="text-content whitespace-pre-wrap">{{ pair.excerpt }}</p>
                                                        </div>
                                                        <div class="rounded-control border border-line bg-surface p-2.5">
                                                            <p class="font-semibold text-content-muted mb-1">{{ match.student || 'Matched submission' }} ({{ similarityPercent(pair.score) }})</p>
                                                            <p class="text-content whitespace-pre-wrap">{{ pair.matchedExcerpt }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-content-faint">
                                                Automated signal based on text similarity — always review before acting.
                                            </p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column - Grading -->
                                <div>
                                    <!-- Grade Input -->
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between">
                                            <label class="ui-label">
                                                Grade (out of {{ gradingAssignment?.totalPoints }})
                                            </label>
                                            <button
                                                v-if="!gradingAssignment?.rubric?.criteria?.length"
                                                type="button"
                                                @click="draftGrade"
                                                :disabled="isDraftingGrade"
                                                class="ui-btn-secondary !py-1.5 !px-3 text-xs disabled:opacity-50"
                                            >
                                                <SparklesIcon class="w-4 h-4 text-primary" />
                                                {{ isDraftingGrade ? 'Drafting…' : 'Draft grade with AI' }}
                                            </button>
                                        </div>
                                        <input
                                            v-model="currentGrade"
                                            type="number"
                                            :max="gradingAssignment?.totalPoints"
                                            min="0"
                                            step="0.5"
                                            class="ui-input text-lg"
                                            placeholder="Enter grade..."
                                        />
                                        <div v-if="currentGrade" class="mt-1 text-sm text-content-muted">
                                            Percentage: {{ ((currentGrade / gradingAssignment?.totalPoints) * 100).toFixed(1) }}%
                                        </div>
                                    </div>

                                    <!-- Quick Grade Buttons -->
                                    <div class="mb-6">
                                        <label class="ui-label">Quick Grades</label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button
                                                @click="currentGrade = gradingAssignment?.totalPoints"
                                                class="py-2 bg-success-bg text-success-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                A (100%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(gradingAssignment?.totalPoints * 0.9)"
                                                class="py-2 bg-primary-soft text-primary rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                A- (90%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(gradingAssignment?.totalPoints * 0.85)"
                                                class="py-2 bg-warning-bg text-warning-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                B+ (85%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(gradingAssignment?.totalPoints * 0.8)"
                                                class="py-2 bg-neutral-bg text-neutral-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                B (80%)
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Rubric Grading -->
                                    <div v-if="gradingAssignment?.rubric?.criteria?.length" class="mb-6">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-semibold text-content">Rubric Breakdown</h4>
                                            <button
                                                type="button"
                                                @click="draftGrade"
                                                :disabled="isDraftingGrade"
                                                class="ui-btn-secondary !py-1.5 !px-3 text-xs disabled:opacity-50"
                                            >
                                                <SparklesIcon class="w-4 h-4 text-primary" />
                                                {{ isDraftingGrade ? 'Drafting…' : 'Draft grade with AI' }}
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="criterion in gradingAssignment.rubric.criteria"
                                                :key="criterion.name"
                                                class="p-3 border border-line rounded-card"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-medium text-content">{{ criterion.name }}</span>
                                                    <span class="text-sm text-content-muted">/ {{ criterion.points }}</span>
                                                </div>
                                                <p class="text-xs text-content-muted mb-2">{{ criterion.description }}</p>
                                                <input
                                                    v-model.number="currentRubricScores[criterion.name]"
                                                    type="number"
                                                    :max="criterion.points"
                                                    min="0"
                                                    step="0.5"
                                                    class="ui-input text-sm"
                                                    :placeholder="`0 - ${criterion.points}`"
                                                />
                                                <p v-if="aiJustifications[criterion.name]" class="mt-1.5 text-xs text-primary italic">
                                                    <SparklesIcon class="inline w-3.5 h-3.5 -mt-0.5" />
                                                    {{ aiJustifications[criterion.name] }}
                                                </p>
                                            </div>
                                        </div>
                                        <p v-if="aiDraftSource === 'heuristic'" class="mt-2 text-xs text-warning-fg">
                                            No AI provider responded — these are heuristic draft scores. Verify each one carefully.
                                        </p>
                                        <div class="mt-3 flex items-center justify-between rounded-control border border-line bg-bg px-3 py-2">
                                            <span class="text-sm font-medium text-content">Rubric total: {{ rubricTotal }}</span>
                                            <button
                                                type="button"
                                                @click="applyRubricTotal"
                                                class="ui-btn-secondary !py-1.5 !px-3 text-xs"
                                            >
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                                Apply as grade
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Feedback -->
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="ui-label mb-0">
                                                Feedback & Comments
                                            </label>
                                            <button
                                                type="button"
                                                @click="draftFeedback"
                                                :disabled="isDraftingFeedback"
                                                class="ui-btn-secondary !py-1.5 !px-3 text-xs disabled:opacity-50"
                                            >
                                                <SparklesIcon class="w-4 h-4 text-primary" />
                                                {{ isDraftingFeedback ? 'Drafting…' : 'Draft with AI' }}
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="currentFeedback"
                                            rows="6"
                                            class="ui-input resize-none"
                                            placeholder="Provide detailed feedback for the student…"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-content-faint">AI drafts a starting point from the submission and rubric — review and edit before saving.</p>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="submitGrade"
                                            :disabled="!currentGrade || isGrading"
                                            class="ui-btn-primary flex-1 justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <CheckCircleIcon class="w-5 h-5" />
                                            {{ isGrading ? 'Submitting...' : 'Submit Grade' }}
                                        </button>
                                        <button
                                            @click="closeGradingPanel"
                                            class="ui-btn-secondary"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </Teleport>
        </AppLayout>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
</style>

<script setup>
import { ref, computed } from 'vue';
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
} from '@heroicons/vue/24/outline';

// Component state
const selectedAssignment = ref(null);
const selectedSubmission = ref(null);
const showGradingPanel = ref(false);
const filterStatus = ref('all');
const sortBy = ref('submitted_date');
const searchQuery = ref('');
const currentGrade = ref('');
const currentFeedback = ref('');
const isGrading = ref(false);

// Real data from the backend (GradingController@index → GradingService::overview)
const props = defineProps({
    courseData: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
    assignments: { type: Array, default: () => [] },
    submissions: { type: Array, default: () => [] },
    courseId: { type: Number, default: null },
});

const courseData = computed(() => props.courseData ?? { code: '—', name: 'No course assigned', students: 0 });

// Local working copies seeded from the server props. A successful grade triggers
// a fresh Inertia visit, so these re-initialise with the updated server data.
const assignments = ref([...props.assignments]);
const submissions = ref([...props.submissions]);

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

// Computed properties
const currentAssignment = computed(() => {
    return assignments.value.find(a => a.id === selectedAssignment.value) || assignments.value[0];
});

const filteredSubmissions = computed(() => {
    let filtered = submissions.value.filter(s => s.assignmentId === currentAssignment.value?.id);

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

const gradingStats = computed(() => {
    if (!currentAssignment.value) return {};

    const assignment = currentAssignment.value;
    const graded = assignment.submissions.graded;
    const total = assignment.submissions.total;
    const pending = assignment.submissions.pending;

    return {
        completionRate: total > 0 ? Math.round((graded / total) * 100) : 0,
        pendingCount: pending,
        averageGrade: assignment.averageGrade || 0,
        totalSubmissions: total
    };
});

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
    showGradingPanel.value = true;
};

const closeGradingPanel = () => {
    showGradingPanel.value = false;
    selectedSubmission.value = null;
    currentGrade.value = '';
    currentFeedback.value = '';
};

const toast = useToast();
const isDraftingFeedback = ref(false);

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

    router.patch(route('faculty.submissions.grade', selectedSubmission.value.id), {
        grade: parseFloat(currentGrade.value),
        feedback: currentFeedback.value || null,
    }, {
        preserveScroll: true,
        // A fresh visit re-seeds assignments/submissions from the server.
        onFinish: () => {
            isGrading.value = false;
            closeGradingPanel();
        },
    });
};

// Initialize with first assignment
if (assignments.value.length > 0) {
    selectedAssignment.value = assignments.value[0].id;
}
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
                        <!-- Assignment Selector -->
                        <div class="flex-1">
                            <label class="ui-label">Select Assignment to Grade</label>
                            <select
                                v-model="selectedAssignment"
                                @change="selectAssignment(selectedAssignment)"
                                class="ui-input max-w-md"
                            >
                                <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                    {{ assignment.title }} ({{ assignment.submissions.pending }} pending)
                                </option>
                            </select>
                        </div>

                        <!-- Assignment Stats -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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
                    <div class="mt-6 p-4 bg-bg rounded-card">
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
                </Card>

                <!-- Filters and Controls -->
                <Card>
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
                <Card padding="p-0">
                    <!-- List Header -->
                    <div class="px-5 sm:px-6 py-3 border-b border-line">
                        <span class="text-sm font-medium text-content-muted">
                            {{ filteredSubmissions.length }} Submissions
                        </span>
                    </div>

                    <!-- Submissions -->
                    <div class="divide-y divide-line">
                        <div
                            v-for="submission in filteredSubmissions"
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
                                            </div>

                                            <!-- Existing Grade & Feedback -->
                                            <div v-if="submission.status === 'graded'" class="mt-4 p-3 bg-success-bg border border-success-fg/20 rounded-card">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <CheckCircleIcon class="w-4 h-4 text-success-fg" />
                                                    <span class="text-sm font-medium text-success-fg">
                                                        Graded: {{ submission.grade }}/{{ currentAssignment.totalPoints }}
                                                    </span>
                                                    <span :class="`font-bold ${getGradeColor(submission.grade)}`">
                                                        ({{ ((submission.grade / currentAssignment.totalPoints) * 100).toFixed(1) }}%)
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
                                                / {{ currentAssignment.totalPoints }}
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
                </Card>
            </div>

            <!-- Grading Panel Modal -->
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
                                        {{ selectedSubmission?.student.name }} - {{ currentAssignment.title }}
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

                                </div>

                                <!-- Right Column - Grading -->
                                <div>
                                    <!-- Grade Input -->
                                    <div class="mb-6">
                                        <label class="ui-label">
                                            Grade (out of {{ currentAssignment.totalPoints }})
                                        </label>
                                        <input
                                            v-model="currentGrade"
                                            type="number"
                                            :max="currentAssignment.totalPoints"
                                            min="0"
                                            step="0.5"
                                            class="ui-input text-lg"
                                            placeholder="Enter grade..."
                                        />
                                        <div v-if="currentGrade" class="mt-1 text-sm text-content-muted">
                                            Percentage: {{ ((currentGrade / currentAssignment.totalPoints) * 100).toFixed(1) }}%
                                        </div>
                                    </div>

                                    <!-- Quick Grade Buttons -->
                                    <div class="mb-6">
                                        <label class="ui-label">Quick Grades</label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button
                                                @click="currentGrade = currentAssignment.totalPoints"
                                                class="py-2 bg-success-bg text-success-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                A (100%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.9)"
                                                class="py-2 bg-primary-soft text-primary rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                A- (90%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.85)"
                                                class="py-2 bg-warning-bg text-warning-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                B+ (85%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.8)"
                                                class="py-2 bg-neutral-bg text-neutral-fg rounded-control hover:opacity-80 text-sm font-medium transition-colors"
                                            >
                                                B (80%)
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Rubric Grading -->
                                    <div v-if="currentAssignment.rubric" class="mb-6">
                                        <h4 class="font-semibold text-content mb-3">Rubric Breakdown</h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="criterion in currentAssignment.rubric.criteria"
                                                :key="criterion.name"
                                                class="p-3 border border-line rounded-card"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-medium text-content">{{ criterion.name }}</span>
                                                    <span class="text-sm text-content-muted">/ {{ criterion.points }}</span>
                                                </div>
                                                <p class="text-xs text-content-muted mb-2">{{ criterion.description }}</p>
                                                <input
                                                    type="number"
                                                    :max="criterion.points"
                                                    min="0"
                                                    step="0.5"
                                                    class="ui-input text-sm"
                                                    :placeholder="`0 - ${criterion.points}`"
                                                />
                                            </div>
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

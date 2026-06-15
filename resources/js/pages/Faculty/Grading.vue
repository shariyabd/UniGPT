<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    PencilIcon,
    MagnifyingGlassIcon,
    CalendarIcon
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
    if (isLate) return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';

    const colors = {
        submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        graded: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        missing: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[status] || colors.submitted;
};

const getGradeColor = (grade) => {
    if (grade >= 90) return 'text-green-600 dark:text-green-400';
    if (grade >= 80) return 'text-blue-600 dark:text-blue-400';
    if (grade >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
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
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    📝 Grading Interface
                                </h1>
                                <p class="text-xl text-white/90 mb-2">
                                    {{ courseData.code }} - {{ courseData.name }}
                                </p>
                                <p class="text-white/80">
                                    {{ courseData.students }} Students
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Grading Progress -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white">
                                    <div class="text-sm font-medium">Grading Progress</div>
                                    <div class="text-2xl font-bold">{{ gradingStats.completionRate }}%</div>
                                    <div class="text-xs text-white/80">{{ gradingStats.pendingCount }} pending</div>
                                </div>

                                <Link
                                    href="/faculty/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Assignment Selection & Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <!-- Assignment Selector -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Select Assignment to Grade
                                </label>
                                <select
                                    v-model="selectedAssignment"
                                    @change="selectAssignment(selectedAssignment)"
                                    class="w-full max-w-md px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                        {{ assignment.title }} ({{ assignment.submissions.pending }} pending)
                                    </option>
                                </select>
                            </div>

                            <!-- Assignment Stats -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ currentAssignment.submissions.total }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ currentAssignment.submissions.graded }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Graded</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ currentAssignment.submissions.pending }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ currentAssignment.submissions.late }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Late</div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Details -->
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Type:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ currentAssignment.type }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Due Date:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(currentAssignment.dueDate) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Total Points:</span>
                                    <span class="ml-2 font-bold text-gray-900 dark:text-white">{{ currentAssignment.totalPoints }}</span>
                                </div>
                            </div>
                            <div v-if="currentAssignment.averageGrade" class="mt-2 text-sm">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Average Grade:</span>
                                <span :class="`ml-2 font-bold ${getGradeColor(currentAssignment.averageGrade)}`">
                                    {{ currentAssignment.averageGrade.toFixed(1) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Controls -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Search and Filters -->
                            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                                <!-- Search -->
                                <div class="relative flex-1 max-w-md">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search students..."
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="filterStatus"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>

                                <!-- Sort By -->
                                <select
                                    v-model="sortBy"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option v-for="sort in sortOptions" :key="sort.value" :value="sort.value">
                                        Sort by {{ sort.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions List -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Table Header -->
                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3">
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ filteredSubmissions.length }} Submissions
                                </span>
                            </div>
                        </div>

                        <!-- Submissions -->
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="submission in filteredSubmissions"
                                :key="submission.id"
                                class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Student Avatar -->
                                    <img
                                        :src="submission.student.avatar"
                                        :alt="submission.student.name"
                                        class="w-12 h-12 rounded-full flex-shrink-0"
                                    />

                                    <!-- Submission Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ submission.student.name }}
                                                </h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ submission.student.email }}
                                                </p>

                                                <!-- Submission Info -->
                                                <div class="flex flex-wrap items-center gap-4 mt-2 text-sm">
                                                    <div class="flex items-center gap-1">
                                                        <CalendarIcon class="w-4 h-4 text-gray-500" />
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            {{ formatDateTime(submission.submittedAt) }}
                                                        </span>
                                                    </div>

                                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(submission.status, submission.isLate)}`">
                                                        {{ submission.isLate ? 'Late' : submission.status }}
                                                    </span>
                                                </div>

                                                <!-- Existing Grade & Feedback -->
                                                <div v-if="submission.status === 'graded'" class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <CheckCircleIcon class="w-4 h-4 text-green-600" />
                                                        <span class="text-sm font-medium text-green-800 dark:text-green-400">
                                                            Graded: {{ submission.grade }}/{{ currentAssignment.totalPoints }}
                                                        </span>
                                                        <span :class="`font-bold ${getGradeColor(submission.grade)}`">
                                                            ({{ ((submission.grade / currentAssignment.totalPoints) * 100).toFixed(1) }}%)
                                                        </span>
                                                    </div>
                                                    <p v-if="submission.feedback" class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ submission.feedback }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Grade Display -->
                                            <div class="text-right">
                                                <div v-if="submission.grade !== null" :class="`text-3xl font-bold ${getGradeColor(submission.grade)}`">
                                                    {{ submission.grade }}
                                                </div>
                                                <div v-else class="text-3xl font-bold text-gray-400">
                                                    —
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    / {{ currentAssignment.totalPoints }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-2 mt-4">
                                            <button
                                                @click="openGradingPanel(submission)"
                                                class="flex items-center gap-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
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
                        <div v-if="filteredSubmissions.length === 0" class="text-center py-16">
                            <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No submissions found</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Try adjusting your filters or search terms.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grading Panel Modal -->
            <div v-if="showGradingPanel" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeGradingPanel"></div>

                    <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                        Grade Submission
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ selectedSubmission?.student.name }} - {{ currentAssignment.title }}
                                    </p>
                                </div>
                                <button
                                    @click="closeGradingPanel"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <XCircleIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Left Column - Submission Info -->
                                <div>
                                    <!-- Student Info -->
                                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                        <div class="flex items-center gap-3 mb-3">
                                            <img
                                                :src="selectedSubmission?.student.avatar"
                                                :alt="selectedSubmission?.student.name"
                                                class="w-12 h-12 rounded-full"
                                            />
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ selectedSubmission?.student.name }}
                                                </h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ selectedSubmission?.student.email }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Submitted:</span>
                                                <div class="text-gray-900 dark:text-white">
                                                    {{ formatDateTime(selectedSubmission?.submittedAt) }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                                <span :class="`ml-1 px-2 py-1 text-xs rounded-full ${getStatusColor(selectedSubmission?.status, selectedSubmission?.isLate)}`">
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Grade (out of {{ currentAssignment.totalPoints }})
                                        </label>
                                        <input
                                            v-model="currentGrade"
                                            type="number"
                                            :max="currentAssignment.totalPoints"
                                            min="0"
                                            step="0.5"
                                            class="w-full px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                            placeholder="Enter grade..."
                                        />
                                        <div v-if="currentGrade" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Percentage: {{ ((currentGrade / currentAssignment.totalPoints) * 100).toFixed(1) }}%
                                        </div>
                                    </div>

                                    <!-- Quick Grade Buttons -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Quick Grades
                                        </label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button
                                                @click="currentGrade = currentAssignment.totalPoints"
                                                class="py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 text-sm font-medium"
                                            >
                                                A (100%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.9)"
                                                class="py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 text-sm font-medium"
                                            >
                                                A- (90%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.85)"
                                                class="py-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 text-sm font-medium"
                                            >
                                                B+ (85%)
                                            </button>
                                            <button
                                                @click="currentGrade = Math.round(currentAssignment.totalPoints * 0.8)"
                                                class="py-2 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded-lg hover:bg-orange-200 dark:hover:bg-orange-900/50 text-sm font-medium"
                                            >
                                                B (80%)
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Rubric Grading -->
                                    <div v-if="currentAssignment.rubric" class="mb-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Rubric Breakdown</h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="criterion in currentAssignment.rubric.criteria"
                                                :key="criterion.name"
                                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ criterion.name }}</span>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">/ {{ criterion.points }}</span>
                                                </div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ criterion.description }}</p>
                                                <input
                                                    type="number"
                                                    :max="criterion.points"
                                                    min="0"
                                                    step="0.5"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                                    :placeholder="`0 - ${criterion.points}`"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Feedback -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Feedback & Comments
                                        </label>
                                        <textarea
                                            v-model="currentFeedback"
                                            rows="6"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none"
                                            placeholder="Provide detailed feedback for the student..."
                                        ></textarea>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="submitGrade"
                                            :disabled="!currentGrade || isGrading"
                                            class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <CheckCircleIcon class="w-5 h-5" />
                                            {{ isGrading ? 'Submitting...' : 'Submit Grade' }}
                                        </button>
                                        <button
                                            @click="closeGradingPanel"
                                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
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

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
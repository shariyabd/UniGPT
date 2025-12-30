<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    DocumentTextIcon,
    UserGroupIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    PencilIcon,
    StarIcon,
    ChartBarIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ArrowDownTrayIcon,
    ChatBubbleLeftEllipsisIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    AdjustmentsHorizontalIcon,
    BookOpenIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

// Component state
const selectedAssignment = ref(null);
const selectedSubmission = ref(null);
const showGradingPanel = ref(false);
const filterStatus = ref('all');
const sortBy = ref('submitted_date');
const searchQuery = ref('');
const bulkSelectedSubmissions = ref(new Set());
const currentGrade = ref('');
const currentFeedback = ref('');
const isGrading = ref(false);

// Mock course data
const courseData = ref({
    id: 1,
    code: 'CSE301',
    name: 'Machine Learning Fundamentals',
    semester: 'Spring 2024',
    instructor: 'Dr. Sarah Smith',
    students: 85,
    description: 'Introduction to machine learning concepts, algorithms, and applications'
});

// Mock assignments data
const assignments = ref([
    {
        id: 1,
        title: 'Assignment 3: Neural Networks Implementation',
        type: 'Programming Assignment',
        dueDate: '2024-01-15T23:59:00',
        totalPoints: 100,
        submissions: {
            total: 85,
            graded: 62,
            pending: 23,
            late: 8,
            missing: 0
        },
        averageGrade: 82.5,
        status: 'active',
        rubric: {
            criteria: [
                { name: 'Code Quality', points: 25, description: 'Clean, readable, well-commented code' },
                { name: 'Algorithm Implementation', points: 40, description: 'Correct neural network implementation' },
                { name: 'Testing & Validation', points: 20, description: 'Proper testing with validation data' },
                { name: 'Documentation', points: 15, description: 'Clear documentation and report' }
            ]
        }
    },
    {
        id: 2,
        title: 'Midterm Project: Supervised Learning Analysis',
        type: 'Project',
        dueDate: '2024-01-20T23:59:00',
        totalPoints: 150,
        submissions: {
            total: 85,
            graded: 15,
            pending: 70,
            late: 3,
            missing: 0
        },
        averageGrade: 0,
        status: 'pending',
        rubric: {
            criteria: [
                { name: 'Data Analysis', points: 50, description: 'Thorough data exploration and preprocessing' },
                { name: 'Model Selection', points: 40, description: 'Appropriate algorithm selection and tuning' },
                { name: 'Evaluation', points: 30, description: 'Comprehensive model evaluation' },
                { name: 'Presentation', points: 30, description: 'Clear presentation of results' }
            ]
        }
    },
    {
        id: 3,
        title: 'Quiz 2: Decision Trees & Random Forest',
        type: 'Quiz',
        dueDate: '2024-01-10T23:59:00',
        totalPoints: 50,
        submissions: {
            total: 85,
            graded: 85,
            pending: 0,
            late: 2,
            missing: 0
        },
        averageGrade: 76.3,
        status: 'completed',
        rubric: {
            criteria: [
                { name: 'Conceptual Understanding', points: 25, description: 'Understanding of key concepts' },
                { name: 'Problem Solving', points: 25, description: 'Application to practical problems' }
            ]
        }
    }
]);

// Mock student submissions
const submissions = ref([
    {
        id: 1,
        assignmentId: 1,
        student: {
            id: 101,
            name: 'Alice Johnson',
            email: 'alice.johnson@student.edu',
            avatar: 'https://ui-avatars.com/api/?name=Alice+Johnson&background=3b82f6&color=fff'
        },
        submittedAt: '2024-01-15T20:30:00',
        status: 'submitted',
        isLate: false,
        grade: null,
        feedback: '',
        files: [
            { name: 'neural_network.py', size: '15.2 KB', type: 'python' },
            { name: 'report.pdf', size: '2.1 MB', type: 'pdf' },
            { name: 'results.csv', size: '45.3 KB', type: 'csv' }
        ],
        attemptCount: 1,
        plagiarismScore: 5.2, // percentage
        rubricScores: {}
    },
    {
        id: 2,
        assignmentId: 1,
        student: {
            id: 102,
            name: 'Bob Smith',
            email: 'bob.smith@student.edu',
            avatar: 'https://ui-avatars.com/api/?name=Bob+Smith&background=10b981&color=fff'
        },
        submittedAt: '2024-01-15T23:45:00',
        status: 'graded',
        isLate: false,
        grade: 88,
        feedback: 'Excellent implementation! Clean code and thorough testing. Consider adding more comments for complex algorithms.',
        files: [
            { name: 'nn_implementation.py', size: '18.7 KB', type: 'python' },
            { name: 'analysis_report.pdf', size: '3.2 MB', type: 'pdf' }
        ],
        attemptCount: 1,
        plagiarismScore: 3.1,
        rubricScores: {
            'Code Quality': 22,
            'Algorithm Implementation': 38,
            'Testing & Validation': 18,
            'Documentation': 10
        },
        gradedAt: '2024-01-16T14:20:00',
        gradedBy: 'Dr. Sarah Smith'
    },
    {
        id: 3,
        assignmentId: 1,
        student: {
            id: 103,
            name: 'Carol Davis',
            email: 'carol.davis@student.edu',
            avatar: 'https://ui-avatars.com/api/?name=Carol+Davis&background=8b5cf6&color=fff'
        },
        submittedAt: '2024-01-16T08:15:00',
        status: 'submitted',
        isLate: true,
        grade: null,
        feedback: '',
        files: [
            { name: 'assignment3.py', size: '12.8 KB', type: 'python' }
        ],
        attemptCount: 2,
        plagiarismScore: 12.7,
        rubricScores: {}
    },
    {
        id: 4,
        assignmentId: 1,
        student: {
            id: 104,
            name: 'David Wilson',
            email: 'david.wilson@student.edu',
            avatar: 'https://ui-avatars.com/api/?name=David+Wilson&background=f59e0b&color=fff'
        },
        submittedAt: '2024-01-15T19:22:00',
        status: 'graded',
        isLate: false,
        grade: 76,
        feedback: 'Good effort on the implementation. Algorithm is correct but code could be more efficient. Consider optimizing the training loop.',
        files: [
            { name: 'neural_net.py', size: '14.5 KB', type: 'python' },
            { name: 'documentation.md', size: '3.2 KB', type: 'markdown' }
        ],
        attemptCount: 1,
        plagiarismScore: 4.8,
        rubricScores: {
            'Code Quality': 18,
            'Algorithm Implementation': 32,
            'Testing & Validation': 15,
            'Documentation': 11
        },
        gradedAt: '2024-01-16T16:45:00',
        gradedBy: 'Dr. Sarah Smith'
    }
]);

// Filter and sort options
const statusOptions = [
    { value: 'all', label: 'All Submissions' },
    { value: 'submitted', label: 'Pending Grading' },
    { value: 'graded', label: 'Graded' },
    { value: 'late', label: 'Late Submissions' },
    { value: 'flagged', label: 'Flagged for Review' }
];

const sortOptions = [
    { value: 'submitted_date', label: 'Submission Date' },
    { value: 'student_name', label: 'Student Name' },
    { value: 'grade', label: 'Grade' },
    { value: 'plagiarism', label: 'Plagiarism Score' }
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
            case 'flagged':
                filtered = filtered.filter(s => s.plagiarismScore > 10);
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
        case 'plagiarism':
            filtered.sort((a, b) => b.plagiarismScore - a.plagiarismScore);
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

const getPlagiarismColor = (score) => {
    if (score > 15) return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
    if (score > 10) return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
};

const getFileIcon = (type) => {
    // Return appropriate icon based on file type
    return DocumentTextIcon; // Fallback
};

// Actions
const selectAssignment = (assignmentId) => {
    selectedAssignment.value = assignmentId;
    bulkSelectedSubmissions.value.clear();
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

const submitGrade = async () => {
    if (!selectedSubmission.value || !currentGrade.value) return;

    isGrading.value = true;

    // Simulate API call
    setTimeout(() => {
        // Update the submission
        const submission = submissions.value.find(s => s.id === selectedSubmission.value.id);
        if (submission) {
            submission.grade = parseFloat(currentGrade.value);
            submission.feedback = currentFeedback.value;
            submission.status = 'graded';
            submission.gradedAt = new Date().toISOString();
            submission.gradedBy = 'Dr. Sarah Smith';

            // Update assignment stats
            const assignment = currentAssignment.value;
            if (assignment) {
                assignment.submissions.graded++;
                assignment.submissions.pending--;

                // Recalculate average grade
                const gradedSubmissions = submissions.value.filter(s =>
                    s.assignmentId === assignment.id && s.status === 'graded'
                );
                const totalGrades = gradedSubmissions.reduce((sum, s) => sum + s.grade, 0);
                assignment.averageGrade = totalGrades / gradedSubmissions.length;
            }
        }

        isGrading.value = false;
        closeGradingPanel();
    }, 1500);
};

const toggleBulkSelection = (submissionId) => {
    if (bulkSelectedSubmissions.value.has(submissionId)) {
        bulkSelectedSubmissions.value.delete(submissionId);
    } else {
        bulkSelectedSubmissions.value.add(submissionId);
    }
};

const selectAllSubmissions = () => {
    if (bulkSelectedSubmissions.value.size === filteredSubmissions.value.length) {
        bulkSelectedSubmissions.value.clear();
    } else {
        bulkSelectedSubmissions.value = new Set(filteredSubmissions.value.map(s => s.id));
    }
};

const bulkGrade = (grade) => {
    if (bulkSelectedSubmissions.value.size === 0) return;

    bulkSelectedSubmissions.value.forEach(submissionId => {
        const submission = submissions.value.find(s => s.id === submissionId);
        if (submission && submission.status === 'submitted') {
            submission.grade = grade;
            submission.status = 'graded';
            submission.gradedAt = new Date().toISOString();
            submission.gradedBy = 'Dr. Sarah Smith';
        }
    });

    // Update assignment stats
    const assignment = currentAssignment.value;
    if (assignment) {
        assignment.submissions.graded += bulkSelectedSubmissions.value.size;
        assignment.submissions.pending -= bulkSelectedSubmissions.value.size;
    }

    bulkSelectedSubmissions.value.clear();
    alert(`Graded ${bulkSelectedSubmissions.value.size} submissions`);
};

const downloadSubmission = (submission) => {
    alert(`Downloading submission files for ${submission.student.name}`);
};

const flagForReview = (submission) => {
    alert(`Flagged ${submission.student.name}'s submission for review`);
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
                                    {{ courseData.semester }} • {{ courseData.students }} Students
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

                            <!-- Bulk Actions -->
                            <div v-if="bulkSelectedSubmissions.size > 0" class="flex items-center gap-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ bulkSelectedSubmissions.size }} selected
                                </span>
                                <button
                                    @click="bulkGrade(100)"
                                    class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                                >
                                    Grade A
                                </button>
                                <button
                                    @click="bulkGrade(85)"
                                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Grade B
                                </button>
                                <button
                                    @click="bulkGrade(75)"
                                    class="px-3 py-1 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700 transition-colors"
                                >
                                    Grade C
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions List -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Table Header -->
                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3">
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    :checked="bulkSelectedSubmissions.size === filteredSubmissions.length && filteredSubmissions.length > 0"
                                    @change="selectAllSubmissions"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 mr-4"
                                />
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
                                    <!-- Checkbox -->
                                    <input
                                        type="checkbox"
                                        :checked="bulkSelectedSubmissions.has(submission.id)"
                                        @change="toggleBulkSelection(submission.id)"
                                        class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    />

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

                                                    <span v-if="submission.attemptCount > 1" class="text-xs text-orange-600 dark:text-orange-400">
                                                        Attempt {{ submission.attemptCount }}
                                                    </span>

                                                    <!-- Plagiarism Score -->
                                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getPlagiarismColor(submission.plagiarismScore)}`">
                                                        {{ submission.plagiarismScore }}% similarity
                                                    </span>
                                                </div>

                                                <!-- Files -->
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span
                                                        v-for="file in submission.files"
                                                        :key="file.name"
                                                        class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-lg"
                                                    >
                                                        <component :is="getFileIcon(file.type)" class="w-3 h-3" />
                                                        {{ file.name }} ({{ file.size }})
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
                                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        Graded by {{ submission.gradedBy }} on {{ formatDateTime(submission.gradedAt) }}
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

                                            <button
                                                @click="downloadSubmission(submission)"
                                                class="flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                <ArrowDownTrayIcon class="w-4 h-4" />
                                                Download
                                            </button>

                                            <button
                                                v-if="submission.plagiarismScore > 10"
                                                @click="flagForReview(submission)"
                                                class="flex items-center gap-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                            >
                                                <ExclamationTriangleIcon class="w-4 h-4" />
                                                Flag
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

                                    <!-- Submission Files -->
                                    <div class="mb-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Submitted Files</h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="file in selectedSubmission?.files"
                                                :key="file.name"
                                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                                            >
                                                <component :is="getFileIcon(file.type)" class="w-5 h-5 text-gray-500" />
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ file.size }}</p>
                                                </div>
                                                <button class="p-2 text-blue-600 hover:text-blue-700">
                                                    <EyeIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Plagiarism Check -->
                                    <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Plagiarism Check</h4>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Similarity Score</span>
                                            <span :class="`font-bold ${
                                                selectedSubmission?.plagiarismScore > 15 ? 'text-red-600' :
                                                selectedSubmission?.plagiarismScore > 10 ? 'text-orange-600' :
                                                'text-green-600'
                                            }`">
                                                {{ selectedSubmission?.plagiarismScore }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                                            <div
                                                :class="`h-2 rounded-full ${
                                                    selectedSubmission?.plagiarismScore > 15 ? 'bg-red-500' :
                                                    selectedSubmission?.plagiarismScore > 10 ? 'bg-orange-500' :
                                                    'bg-green-500'
                                                }`"
                                                :style="{ width: selectedSubmission?.plagiarismScore + '%' }"
                                            ></div>
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
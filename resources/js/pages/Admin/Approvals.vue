<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    DocumentTextIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    ChatBubbleLeftEllipsisIcon,
    ExclamationTriangleIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    ArrowDownTrayIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ChevronUpIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pendingDocuments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

// Component state
const selectedStatus = ref('all');
const selectedPriority = ref('all');
const searchQuery = ref('');
const selectedDocument = ref(null);
const showPreviewModal = ref(false);
const showCommentModal = ref(false);
const commentMode = ref('comment'); // 'comment' | 'changes'
const newComment = ref('');
const expandedItems = ref(new Set());

// Pending queue supplied by the server.
const pendingDocuments = computed(() => props.pendingDocuments);

// Filter options
const statusOptions = computed(() => [
    { value: 'all', label: 'All Status', count: props.stats.pending ?? 0 },
    { value: 'pending', label: 'Pending Review', count: props.stats.pending ?? 0 },
    { value: 'processing', label: 'Processing', count: props.stats.processing ?? 0 },
    { value: 'approved', label: 'Approved', count: props.stats.approved ?? 0 },
    { value: 'rejected', label: 'Rejected', count: props.stats.rejected ?? 0 }
]);

const priorityOptions = [
    { value: 'all', label: 'All Priorities' },
    { value: 'urgent', label: 'Urgent', color: 'red' },
    { value: 'high', label: 'High Priority', color: 'orange' },
    { value: 'normal', label: 'Normal', color: 'blue' },
    { value: 'low', label: 'Low Priority', color: 'gray' }
];

// Computed properties
const filteredDocuments = computed(() => {
    let filtered = pendingDocuments.value;

    // Filter by status
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(doc => doc.status === selectedStatus.value);
    }

    // Filter by priority
    if (selectedPriority.value !== 'all') {
        filtered = filtered.filter(doc => doc.priority === selectedPriority.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(doc =>
            doc.title.toLowerCase().includes(query) ||
            doc.description.toLowerCase().includes(query) ||
            doc.uploadedBy.name.toLowerCase().includes(query) ||
            doc.department.toLowerCase().includes(query) ||
            doc.tags.some(tag => tag.toLowerCase().includes(query))
        );
    }

    return filtered;
});

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTimeAgo = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
};

const getPriorityColor = (priority) => {
    const colors = {
        urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        normal: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        low: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
    };
    return colors[priority] || colors.normal;
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        under_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[status] || colors.pending;
};

const getStatusIcon = (status) => {
    const icons = {
        pending: ClockIcon,
        under_review: EyeIcon,
        approved: CheckCircleIcon,
        rejected: XCircleIcon
    };
    return icons[status] || ClockIcon;
};

// Actions
const toggleExpanded = (docId) => {
    if (expandedItems.value.has(docId)) {
        expandedItems.value.delete(docId);
    } else {
        expandedItems.value.add(docId);
    }
};

const previewDocument = (document) => {
    selectedDocument.value = document;
    showPreviewModal.value = true;
};

const openCommentModal = (document, mode = 'comment') => {
    selectedDocument.value = document;
    commentMode.value = mode;
    showCommentModal.value = true;
};

const addComment = () => {
    if (!newComment.value.trim() || !selectedDocument.value) return;

    const id = selectedDocument.value.id;
    const routeName = commentMode.value === 'changes'
        ? 'admin.documents.request-changes'
        : 'admin.documents.comment';

    router.post(route(routeName, id), { comment: newComment.value }, {
        preserveScroll: true,
        onFinish: () => {
            newComment.value = '';
            showCommentModal.value = false;
        },
    });
};

const approveDocument = (document) => {
    router.post(route('admin.documents.approve', document.id), {}, { preserveScroll: true });
};

const rejectDocument = (document) => {
    const reason = window.prompt('Reason for rejection:', '');
    if (reason === null || reason.trim() === '') return;

    router.post(route('admin.documents.reject', document.id), { reason }, { preserveScroll: true });
};

const requestChanges = (document) => {
    openCommentModal(document, 'changes');
};

const downloadDocument = (document) => {
    if (document.downloadUrl) {
        window.open(document.downloadUrl, '_blank');
    }
};
</script>

<template>
    <div>
        <Head title="Approval Workflow" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 dark:from-orange-900 dark:via-red-900 dark:to-pink-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    🔄 Approval Workflow
                                </h1>
                                <p class="text-xl text-white/90">
                                    Review and manage document approvals
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white">
                                    <div class="text-sm font-medium">Pending Review</div>
                                    <div class="text-2xl font-bold">{{ filteredDocuments.length }}</div>
                                </div>
                                <Link
                                    href="/admin/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Filters & Search -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Search -->
                            <div class="relative flex-1 max-w-lg">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search documents, authors, departments..."
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Filters -->
                            <div class="flex items-center gap-4">
                                <!-- Status Filter -->
                                <select
                                    v-model="selectedStatus"
                                    class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                >
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }} ({{ status.count }})
                                    </option>
                                </select>

                                <!-- Priority Filter -->
                                <select
                                    v-model="selectedPriority"
                                    class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                >
                                    <option v-for="priority in priorityOptions" :key="priority.value" :value="priority.value">
                                        {{ priority.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Documents List -->
                    <div class="space-y-6">
                        <div
                            v-for="document in filteredDocuments"
                            :key="document.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300"
                        >
                            <!-- Document Header -->
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-6">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-4 mb-4">
                                            <!-- File Icon -->
                                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                                                <DocumentTextIcon class="w-6 h-6 text-white" />
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-4 mb-2">
                                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white line-clamp-2">
                                                        {{ document.title }}
                                                    </h3>

                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <span :class="`px-3 py-1 rounded-full text-xs font-bold ${getPriorityColor(document.priority)}`">
                                                            {{ document.priority.toUpperCase() }}
                                                        </span>
                                                        <span :class="`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(document.status)}`">
                                                            <component :is="getStatusIcon(document.status)" class="w-3 h-3 inline mr-1" />
                                                            {{ document.status.replace('_', ' ').toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <p class="text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                                    {{ document.description }}
                                                </p>

                                                <!-- Document Meta Info -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                                    <div class="flex items-center gap-2">
                                                        <UserIcon class="w-4 h-4 text-gray-500" />
                                                        <span class="text-gray-600 dark:text-gray-400">By:</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ document.uploadedBy.name }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <CalendarIcon class="w-4 h-4 text-gray-500" />
                                                        <span class="text-gray-600 dark:text-gray-400">Uploaded:</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ getTimeAgo(document.uploadDate) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <TagIcon class="w-4 h-4 text-gray-500" />
                                                        <span class="text-gray-600 dark:text-gray-400">Department:</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ document.department }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <DocumentTextIcon class="w-4 h-4 text-gray-500" />
                                                        <span class="text-gray-600 dark:text-gray-400">Size:</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ document.size }}</span>
                                                    </div>
                                                </div>

                                                <!-- Tags -->
                                                <div class="flex flex-wrap gap-2 mt-3">
                                                    <span
                                                        v-for="tag in document.tags"
                                                        :key="tag"
                                                        class="px-2 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 rounded-full text-xs font-medium"
                                                    >
                                                        #{{ tag }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button
                                                @click="previewDocument(document)"
                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                            >
                                                <EyeIcon class="w-4 h-4 mr-1" />
                                                Preview
                                            </button>

                                            <button
                                                @click="downloadDocument(document)"
                                                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                            >
                                                <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
                                                Download
                                            </button>

                                            <button
                                                @click="openCommentModal(document)"
                                                class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                            >
                                                <ChatBubbleLeftEllipsisIcon class="w-4 h-4 mr-1" />
                                                Comment ({{ document.comments.length }})
                                            </button>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="approveDocument(document)"
                                                    v-if="document.status !== 'approved'"
                                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                                >
                                                    <CheckCircleIcon class="w-4 h-4 mr-1" />
                                                    Approve
                                                </button>

                                                <button
                                                    @click="rejectDocument(document, 'Please provide more details')"
                                                    v-if="document.status !== 'rejected'"
                                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                                >
                                                    <XCircleIcon class="w-4 h-4 mr-1" />
                                                    Reject
                                                </button>

                                                <button
                                                    @click="requestChanges(document)"
                                                    v-if="document.status === 'pending' || document.status === 'under_review'"
                                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transform hover:-translate-y-0.5 transition-all duration-200"
                                                >
                                                    <ExclamationTriangleIcon class="w-4 h-4 mr-1" />
                                                    Request Changes
                                                </button>
                                            </div>

                                            <!-- Expand/Collapse Button -->
                                            <button
                                                @click="toggleExpanded(document.id)"
                                                class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 ml-auto"
                                            >
                                                <component :is="expandedItems.has(document.id) ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Uploader Info -->
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                            <img
                                                :src="document.uploadedBy.avatar"
                                                :alt="document.uploadedBy.name"
                                                class="w-12 h-12 rounded-full"
                                            />
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white text-sm">
                                                    {{ document.uploadedBy.name }}
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ document.uploadedBy.role }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                                    {{ document.uploadedBy.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            <div v-if="expandedItems.has(document.id)" class="p-6 bg-gray-50 dark:bg-gray-900/50">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <!-- Comments Section -->
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <ChatBubbleLeftEllipsisIcon class="w-5 h-5 text-purple-600" />
                                            Comments & Discussion ({{ document.comments.length }})
                                        </h4>

                                        <div class="space-y-4 max-h-64 overflow-y-auto">
                                            <div
                                                v-for="comment in document.comments"
                                                :key="comment.id"
                                                class="p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4"
                                                :class="comment.type === 'question' ? 'border-blue-500' :
                                                       comment.type === 'issue' ? 'border-red-500' :
                                                       comment.type === 'rejection' ? 'border-red-600' :
                                                       'border-gray-300 dark:border-gray-600'"
                                            >
                                                <div class="flex items-start justify-between mb-2">
                                                    <span class="font-semibold text-gray-900 dark:text-white text-sm">
                                                        {{ comment.author }}
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatDate(comment.timestamp) }}
                                                    </span>
                                                </div>
                                                <p class="text-gray-700 dark:text-gray-300 text-sm">
                                                    {{ comment.content }}
                                                </p>
                                            </div>

                                            <div v-if="document.comments.length === 0" class="text-center py-8">
                                                <ChatBubbleLeftEllipsisIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                                <p class="text-gray-500 dark:text-gray-400">No comments yet</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Approval History -->
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <ClockIcon class="w-5 h-5 text-blue-600" />
                                            Approval Timeline
                                        </h4>

                                        <div class="space-y-4">
                                            <div
                                                v-for="(history, index) in document.approvalHistory"
                                                :key="index"
                                                class="flex items-center gap-4"
                                            >
                                                <div :class="`w-3 h-3 rounded-full flex-shrink-0 ${
                                                    history.action === 'approved' ? 'bg-green-500' :
                                                    history.action === 'rejected' ? 'bg-red-500' :
                                                    history.action === 'under_review' ? 'bg-blue-500' :
                                                    'bg-yellow-500'
                                                }`"></div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                                            {{ history.action.replace('_', ' ') }}
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ formatDate(history.date) }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                                        By {{ history.by }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredDocuments.length === 0" class="text-center py-16">
                            <DocumentTextIcon class="w-24 h-24 text-gray-400 mx-auto mb-6" />
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No documents found</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Try adjusting your filters or search query.</p>
                            <button
                                @click="searchQuery = ''; selectedStatus = 'all'; selectedPriority = 'all'"
                                class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment Modal -->
            <div v-if="showCommentModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showCommentModal = false"></div>

                    <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                                Add Comment
                            </h3>
                            <textarea
                                v-model="newComment"
                                rows="4"
                                placeholder="Enter your comment or feedback..."
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none"
                            ></textarea>

                            <div class="flex items-center justify-end gap-3 mt-4">
                                <button
                                    @click="showCommentModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="addComment"
                                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
                                >
                                    Add Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>

                    <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Document Preview: {{ selectedDocument?.title }}
                                </h3>
                                <button
                                    @click="showPreviewModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <XCircleIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="h-96 bg-gray-100 dark:bg-gray-900 rounded-xl flex items-center justify-center">
                                <div class="text-center">
                                    <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-600 dark:text-gray-400">Document preview would appear here</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                                        File: {{ selectedDocument?.title }}
                                    </p>
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
</style>
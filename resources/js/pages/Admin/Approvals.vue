<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    DocumentTextIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
    EyeIcon,
    ChatBubbleLeftEllipsisIcon,
    ExclamationTriangleIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    ArrowDownTrayIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    CheckBadgeIcon
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
        urgent: 'bg-danger-bg text-danger-fg',
        high: 'bg-warning-bg text-warning-fg',
        normal: 'bg-primary-soft text-primary',
        low: 'bg-neutral-bg text-neutral-fg'
    };
    return colors[priority] || colors.normal;
};

// Map document status to Badge variant (pending=warning, approved=success, rejected=danger).
const getStatusVariant = (status) => {
    const variants = {
        pending: 'warning',
        under_review: 'info',
        approved: 'success',
        rejected: 'danger'
    };
    return variants[status] || 'warning';
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
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <!-- Page Header -->
                <PageHeader
                    title="Approvals"
                    subtitle="Review and manage document approvals"
                    eyebrow="Admin"
                    :icon="CheckBadgeIcon"
                >
                    <template #actions>
                        <Badge variant="warning" dot>
                            {{ filteredDocuments.length }} pending
                        </Badge>
                        <Link href="/admin/dashboard" class="ui-btn-secondary">
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters & Search -->
                <Card>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-lg">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-content-faint" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search documents, authors, departments..."
                                class="ui-input pl-10"
                            />
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <!-- Status Filter -->
                            <select
                                v-model="selectedStatus"
                                class="ui-input"
                                aria-label="Filter by status"
                            >
                                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                    {{ status.label }} ({{ status.count }})
                                </option>
                            </select>

                            <!-- Priority Filter -->
                            <select
                                v-model="selectedPriority"
                                class="ui-input"
                                aria-label="Filter by priority"
                            >
                                <option v-for="priority in priorityOptions" :key="priority.value" :value="priority.value">
                                    {{ priority.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </Card>

                <!-- Documents List -->
                <div class="space-y-6">
                    <Card
                        v-for="document in filteredDocuments"
                        :key="document.id"
                        padding="p-0"
                        hover
                    >
                        <!-- Document Header -->
                        <div class="p-5 sm:p-6 border-b border-line">
                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-4 mb-4">
                                        <!-- File Icon -->
                                        <div class="ui-icon-tile flex-shrink-0 w-12 h-12 bg-primary-soft text-primary">
                                            <DocumentTextIcon class="w-6 h-6" />
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-2">
                                                <h3 class="text-lg sm:text-xl font-bold text-content line-clamp-2">
                                                    {{ document.title }}
                                                </h3>

                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <span :class="`px-3 py-1 rounded-pill text-xs font-bold ${getPriorityColor(document.priority)}`">
                                                        {{ document.priority.toUpperCase() }}
                                                    </span>
                                                    <Badge :variant="getStatusVariant(document.status)">
                                                        <component :is="getStatusIcon(document.status)" class="w-3 h-3 mr-1" />
                                                        {{ document.status.replace('_', ' ').toUpperCase() }}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <p class="text-content-muted mb-3 line-clamp-2">
                                                {{ document.description }}
                                            </p>

                                            <!-- Document Meta Info -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <UserIcon class="w-4 h-4 text-content-faint" />
                                                    <span class="text-content-muted">By:</span>
                                                    <span class="font-medium text-content">{{ document.uploadedBy.name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <CalendarIcon class="w-4 h-4 text-content-faint" />
                                                    <span class="text-content-muted">Uploaded:</span>
                                                    <span class="font-medium text-content">{{ getTimeAgo(document.uploadDate) }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <TagIcon class="w-4 h-4 text-content-faint" />
                                                    <span class="text-content-muted">Department:</span>
                                                    <span class="font-medium text-content">{{ document.department }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <DocumentTextIcon class="w-4 h-4 text-content-faint" />
                                                    <span class="text-content-muted">Size:</span>
                                                    <span class="font-medium text-content">{{ document.size }}</span>
                                                </div>
                                            </div>

                                            <!-- Tags -->
                                            <div class="flex flex-wrap gap-2 mt-3">
                                                <span
                                                    v-for="tag in document.tags"
                                                    :key="tag"
                                                    class="px-2 py-1 bg-primary-soft text-primary rounded-pill text-xs font-medium"
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
                                            class="ui-btn-secondary"
                                        >
                                            <EyeIcon class="w-4 h-4 mr-1" />
                                            Preview
                                        </button>

                                        <button
                                            @click="downloadDocument(document)"
                                            class="ui-btn-ghost"
                                        >
                                            <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
                                            Download
                                        </button>

                                        <button
                                            @click="openCommentModal(document)"
                                            class="ui-btn-ghost"
                                        >
                                            <ChatBubbleLeftEllipsisIcon class="w-4 h-4 mr-1" />
                                            Comment ({{ document.comments.length }})
                                        </button>

                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="approveDocument(document)"
                                                v-if="document.status !== 'approved'"
                                                class="ui-btn bg-success-fg text-white hover:opacity-90"
                                            >
                                                <CheckCircleIcon class="w-4 h-4 mr-1" />
                                                Approve
                                            </button>

                                            <button
                                                @click="rejectDocument(document, 'Please provide more details')"
                                                v-if="document.status !== 'rejected'"
                                                class="ui-btn-danger"
                                            >
                                                <XCircleIcon class="w-4 h-4 mr-1" />
                                                Reject
                                            </button>

                                            <button
                                                @click="requestChanges(document)"
                                                v-if="document.status === 'pending' || document.status === 'under_review'"
                                                class="ui-btn bg-warning-fg text-white hover:opacity-90"
                                            >
                                                <ExclamationTriangleIcon class="w-4 h-4 mr-1" />
                                                Request Changes
                                            </button>
                                        </div>

                                        <!-- Expand/Collapse Button -->
                                        <button
                                            @click="toggleExpanded(document.id)"
                                            class="ui-btn-secondary px-3 py-2 ml-auto"
                                            :aria-label="expandedItems.has(document.id) ? 'Collapse details' : 'Expand details'"
                                        >
                                            <component :is="expandedItems.has(document.id) ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Uploader Info -->
                                <div class="flex-shrink-0">
                                    <div class="flex items-center gap-3 p-4 bg-bg rounded-control">
                                        <img
                                            :src="document.uploadedBy.avatar"
                                            :alt="document.uploadedBy.name"
                                            class="w-12 h-12 rounded-full"
                                        />
                                        <div>
                                            <p class="font-semibold text-content text-sm">
                                                {{ document.uploadedBy.name }}
                                            </p>
                                            <p class="text-xs text-content-muted">
                                                {{ document.uploadedBy.role }}
                                            </p>
                                            <p class="text-xs text-content-faint">
                                                {{ document.uploadedBy.email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expanded Details -->
                        <div v-if="expandedItems.has(document.id)" class="p-5 sm:p-6 bg-bg">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Comments Section -->
                                <div>
                                    <h4 class="text-lg font-bold text-content mb-4 flex items-center gap-2">
                                        <ChatBubbleLeftEllipsisIcon class="w-5 h-5 text-primary" />
                                        Comments & Discussion ({{ document.comments.length }})
                                    </h4>

                                    <div class="space-y-4 max-h-64 overflow-y-auto">
                                        <div
                                            v-for="comment in document.comments"
                                            :key="comment.id"
                                            class="p-4 bg-surface rounded-control border-l-4 border border-line"
                                            :class="comment.type === 'question' ? 'border-l-primary' :
                                                   comment.type === 'issue' ? 'border-l-danger-fg' :
                                                   comment.type === 'rejection' ? 'border-l-danger-fg' :
                                                   'border-l-line'"
                                        >
                                            <div class="flex items-start justify-between mb-2">
                                                <span class="font-semibold text-content text-sm">
                                                    {{ comment.author }}
                                                </span>
                                                <span class="text-xs text-content-muted">
                                                    {{ formatDate(comment.timestamp) }}
                                                </span>
                                            </div>
                                            <p class="text-content-muted text-sm">
                                                {{ comment.content }}
                                            </p>
                                        </div>

                                        <div v-if="document.comments.length === 0" class="text-center py-8">
                                            <ChatBubbleLeftEllipsisIcon class="w-12 h-12 text-content-faint mx-auto mb-4" />
                                            <p class="text-content-muted">No comments yet</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval History -->
                                <div>
                                    <h4 class="text-lg font-bold text-content mb-4 flex items-center gap-2">
                                        <ClockIcon class="w-5 h-5 text-primary" />
                                        Approval Timeline
                                    </h4>

                                    <div class="space-y-4">
                                        <div
                                            v-for="(history, index) in document.approvalHistory"
                                            :key="index"
                                            class="flex items-center gap-4"
                                        >
                                            <div :class="`w-3 h-3 rounded-full flex-shrink-0 ${
                                                history.action === 'approved' ? 'bg-success-fg' :
                                                history.action === 'rejected' ? 'bg-danger-fg' :
                                                history.action === 'under_review' ? 'bg-primary' :
                                                'bg-warning-fg'
                                            }`"></div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium text-content capitalize">
                                                        {{ history.action.replace('_', ' ') }}
                                                    </span>
                                                    <span class="text-xs text-content-muted">
                                                        {{ formatDate(history.date) }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-content-muted">
                                                    By {{ history.by }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Empty State -->
                    <EmptyState
                        v-if="filteredDocuments.length === 0"
                        title="No documents found"
                        description="Try adjusting your filters or search query."
                        :icon="DocumentTextIcon"
                    >
                        <button
                            @click="searchQuery = ''; selectedStatus = 'all'; selectedPriority = 'all'"
                            class="ui-btn-primary"
                        >
                            Clear Filters
                        </button>
                    </EmptyState>
                </div>
            </div>

            <!-- Comment Modal -->
            <div v-if="showCommentModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/40 backdrop-blur-sm" @click="showCommentModal = false"></div>

                    <div class="relative w-full max-w-lg bg-surface rounded-card border border-line shadow-card">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-content mb-4 flex items-center gap-2">
                                <ChatBubbleLeftEllipsisIcon class="w-5 h-5 text-primary" />
                                Add Comment
                            </h3>
                            <textarea
                                v-model="newComment"
                                rows="4"
                                placeholder="Enter your comment or feedback..."
                                class="ui-input resize-none"
                            ></textarea>

                            <div class="flex items-center justify-end gap-3 mt-4">
                                <button
                                    @click="showCommentModal = false"
                                    class="ui-btn-ghost"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="addComment"
                                    class="ui-btn-primary"
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
                    <div class="fixed inset-0 bg-content/40 backdrop-blur-sm" @click="showPreviewModal = false"></div>

                    <div class="relative w-full max-w-4xl bg-surface rounded-card border border-line shadow-card">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-content">
                                    Document Preview: {{ selectedDocument?.title }}
                                </h3>
                                <button
                                    @click="showPreviewModal = false"
                                    class="p-2 text-content-faint hover:text-content-muted"
                                    aria-label="Close preview"
                                >
                                    <XMarkIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="h-96 bg-bg rounded-control flex items-center justify-center">
                                <div class="text-center">
                                    <DocumentTextIcon class="w-16 h-16 text-content-faint mx-auto mb-4" />
                                    <p class="text-content-muted">Document preview would appear here</p>
                                    <p class="text-sm text-content-faint mt-2">
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

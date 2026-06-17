<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { useConfirm } from '@/composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    BookmarkIcon,
    ShareIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    ChevronDownIcon,
    Squares2X2Icon,
    ListBulletIcon,
    StarIcon,
    ClockIcon,
    FolderIcon,
    ArrowUpTrayIcon,
    DocumentIcon,
    DocumentChartBarIcon,
    PresentationChartBarIcon,
    XMarkIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documents: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { confirm } = useConfirm();

// Component state
const viewMode = ref('grid');
const selectedCategory = ref('all');
const selectedDepartment = ref('all');
const selectedFileType = ref('all');
const selectedStatus = ref('all');
const sortBy = ref('recent');
const searchQuery = ref('');
const showFilters = ref(false);

// Server documents, mapped to the shape this page's template renders.
const documents = ref((props.documents?.data ?? []).map((d) => ({
    id: d.id,
    title: d.title,
    description: d.description ?? '',
    department: d.department ?? '—',
    category: d.category,
    fileType: (d.type ?? 'file').toUpperCase(),
    size: d.fileSize,
    version: d.version,
    uploadDate: d.uploadedAt,
    lastModified: d.uploadedAt,
    uploadedBy: d.uploadedBy ?? 'Unknown',
    status: d.status,
    views: d.views,
    downloads: d.downloads,
    tags: d.tags ?? [],
    downloadUrl: d.downloadUrl,
    previewUrl: d.previewUrl,
    isBookmarked: false,
    rating: null,
})));

// Server pagination metadata (from the Laravel paginator wrapped by the resource).
const pagination = computed(() => props.documents?.meta ?? null);
const pageLinks = computed(() => props.documents?.links ?? {});

// Filter options derived from server-provided facets.
const categories = computed(() => [
    { value: 'all', label: 'All Categories' },
    ...props.categories.map((c) => ({ value: c.toLowerCase(), label: c })),
]);

const departments = computed(() => [
    { value: 'all', label: 'All Departments' },
    ...props.departments.map((d) => ({ value: d.name, label: d.name })),
]);

const fileTypes = [
    { value: 'all', label: 'All Types' },
    { value: 'pdf', label: 'PDF' },
    { value: 'docx', label: 'Word Document' },
    { value: 'txt', label: 'Text' },
    { value: 'pptx', label: 'PowerPoint' }
];

const statusOptions = [
    { value: 'all', label: 'All Statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'processing', label: 'Processing' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'draft', label: 'Draft' },
];

const sortOptions = [
    { value: 'recent', label: 'Most Recent' },
    { value: 'popular', label: 'Most Popular' },
    { value: 'downloads', label: 'Most Downloads' },
    { value: 'alphabetical', label: 'A-Z' }
];

// Computed properties (in-page filtering of the loaded set)
const filteredDocuments = computed(() => {
    let filtered = [...documents.value];

    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(doc => doc.category.toLowerCase() === selectedCategory.value);
    }

    if (selectedDepartment.value !== 'all') {
        filtered = filtered.filter(doc => doc.department === selectedDepartment.value);
    }

    if (selectedFileType.value !== 'all') {
        filtered = filtered.filter(doc => doc.fileType.toLowerCase() === selectedFileType.value);
    }

    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(doc => (doc.status ?? '').toLowerCase() === selectedStatus.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(doc =>
            doc.title.toLowerCase().includes(query) ||
            doc.description.toLowerCase().includes(query) ||
            doc.tags.some(tag => tag.toLowerCase().includes(query)) ||
            doc.uploadedBy.toLowerCase().includes(query)
        );
    }

    switch (sortBy.value) {
        case 'popular':
            return filtered.sort((a, b) => b.views - a.views);
        case 'downloads':
            return filtered.sort((a, b) => b.downloads - a.downloads);
        case 'alphabetical':
            return filtered.sort((a, b) => a.title.localeCompare(b.title));
        case 'recent':
        default:
            return filtered;
    }
});

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getCategoryColor = (category) => {
    const colors = {
        syllabus: 'bg-primary-soft text-primary',
        handbook: 'bg-success-bg text-success-fg',
        policy: 'bg-primary-soft text-primary',
        schedule: 'bg-warning-bg text-warning-fg',
        guidelines: 'bg-danger-bg text-danger-fg'
    };
    return colors[category.toLowerCase()] || 'bg-neutral-bg text-neutral-fg';
};

const getFileTypeColor = (fileType) => {
    const colors = {
        pdf: 'text-danger-fg',
        docx: 'text-primary',
        pptx: 'text-warning-fg',
        xlsx: 'text-success-fg'
    };
    return colors[fileType.toLowerCase()] || 'text-content-muted';
};

// File-type icon + accent for the modernized surfaces (display-only helpers).
const getFileTypeIcon = (fileType) => {
    const icons = {
        pdf: DocumentTextIcon,
        docx: DocumentIcon,
        txt: DocumentIcon,
        pptx: PresentationChartBarIcon,
        xlsx: DocumentChartBarIcon,
    };
    return icons[fileType.toLowerCase()] || DocumentTextIcon;
};

const getFileTypeBadge = (fileType) => {
    const variants = {
        pdf: 'danger',
        docx: 'info',
        txt: 'slate',
        pptx: 'warning',
        xlsx: 'success',
    };
    return variants[fileType.toLowerCase()] || 'slate';
};

const getStatusBadge = (status) => {
    const variants = {
        approved: 'success',
        published: 'success',
        pending: 'warning',
        rejected: 'danger',
        draft: 'slate',
    };
    return variants[(status ?? '').toLowerCase()] || 'slate';
};

// Actions
const toggleBookmark = (docId) => {
    const doc = documents.value.find(d => d.id === docId);
    if (doc) {
        doc.isBookmarked = !doc.isBookmarked;
    }
};

const downloadDocument = (doc) => {
    if (!doc.downloadUrl) {
        toast.error('This document has no file to download.');
        return;
    }

    // Trigger a real download without opening a blank tab.
    const link = document.createElement('a');
    link.href = doc.downloadUrl;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    doc.downloads++;
};

const previewDocument = (doc) => {
    const url = doc.previewUrl ?? doc.downloadUrl;
    if (!url) {
        toast.error('This document has no file to preview.');
        return;
    }

    // Open inline in a new tab (PDFs/text render in-browser).
    window.open(url, '_blank', 'noopener');
    doc.views++;
};

const shareDocument = async (doc) => {
    const path = doc.previewUrl ?? doc.downloadUrl;
    if (!path) {
        toast.error('This document has no shareable link.');
        return;
    }

    const url = path.startsWith('http') ? path : window.location.origin + path;

    try {
        await navigator.clipboard.writeText(url);
        toast.success('Document link copied to clipboard.');
    } catch {
        toast.error('Could not copy the link. Please copy it manually.');
    }
};

const deleteDocument = async (doc) => {
    const confirmed = await confirm({
        title: 'Delete document',
        message: `This will permanently delete "${doc.title}". This action cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) return;

    router.delete(route('admin.documents.destroy', doc.id), {
        preserveScroll: true,
        onSuccess: () => {
            documents.value = documents.value.filter((d) => d.id !== doc.id);
        },
    });
};

const clearAllFilters = () => {
    selectedCategory.value = 'all';
    selectedDepartment.value = 'all';
    selectedFileType.value = 'all';
    selectedStatus.value = 'all';
    searchQuery.value = '';
    sortBy.value = 'recent';
};
</script>

<template>
    <div>
        <Head title="Document Library" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Document Library"
                    subtitle="Browse, manage and access all academic documents"
                    :icon="DocumentTextIcon"
                >
                    <template #actions>
                        <Badge variant="violet" class="hidden sm:inline-flex">
                            {{ filteredDocuments.length }} documents
                        </Badge>
                        <Link :href="route('admin.documents.upload')" class="ui-btn-primary">
                            <ArrowUpTrayIcon class="h-4 w-4" />
                            Upload
                        </Link>
                    </template>
                </PageHeader>

                <!-- Search and Controls -->
                <Card>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Search -->
                        <div class="relative w-full lg:max-w-lg">
                            <MagnifyingGlassIcon
                                class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search documents, titles, authors, or tags..."
                                class="ui-input pl-10"
                                aria-label="Search documents"
                            />
                        </div>

                        <!-- Controls -->
                        <div class="flex items-center gap-3">
                            <!-- View Toggle -->
                            <div class="flex rounded-control bg-bg p-1">
                                <button
                                    @click="viewMode = 'grid'"
                                    :class="[
                                        'rounded-control p-2 transition-colors',
                                        viewMode === 'grid'
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-content-muted hover:text-content',
                                    ]"
                                    aria-label="Grid view"
                                >
                                    <Squares2X2Icon class="h-4 w-4" />
                                </button>
                                <button
                                    @click="viewMode = 'list'"
                                    :class="[
                                        'rounded-control p-2 transition-colors',
                                        viewMode === 'list'
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-content-muted hover:text-content',
                                    ]"
                                    aria-label="List view"
                                >
                                    <ListBulletIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Filters Toggle -->
                            <button
                                @click="showFilters = !showFilters"
                                class="ui-btn-secondary"
                                :class="showFilters ? 'ring-2 ring-primary/20' : ''"
                            >
                                <FunnelIcon class="h-4 w-4" />
                                Filters
                            </button>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div
                        v-if="showFilters"
                        class="mt-6 border-t border-line pt-6"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            <!-- Status Filter -->
                            <div>
                                <label class="ui-label">Status</label>
                                <select v-model="selectedStatus" class="ui-input">
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Category Filter -->
                            <div>
                                <label class="ui-label">Category</label>
                                <select v-model="selectedCategory" class="ui-input">
                                    <option v-for="category in categories" :key="category.value" :value="category.value">
                                        {{ category.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Department Filter -->
                            <div>
                                <label class="ui-label">Department</label>
                                <select v-model="selectedDepartment" class="ui-input">
                                    <option v-for="department in departments" :key="department.value" :value="department.value">
                                        {{ department.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- File Type Filter -->
                            <div>
                                <label class="ui-label">File Type</label>
                                <select v-model="selectedFileType" class="ui-input">
                                    <option v-for="fileType in fileTypes" :key="fileType.value" :value="fileType.value">
                                        {{ fileType.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Sort Options -->
                            <div>
                                <label class="ui-label">Sort By</label>
                                <select v-model="sortBy" class="ui-input">
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Clear Filters -->
                        <div class="mt-4 flex justify-end">
                            <button @click="clearAllFilters" class="ui-btn-ghost text-primary">
                                <XMarkIcon class="h-4 w-4" />
                                Clear All Filters
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Documents Grid -->
                <div
                    v-if="viewMode === 'grid' && filteredDocuments.length > 0"
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Card
                        v-for="document in filteredDocuments"
                        :key="document.id"
                        hover
                        padding="p-0"
                        class="flex flex-col overflow-hidden"
                    >
                        <!-- Document Header -->
                        <div class="flex-1 p-5">
                            <div class="mb-4 flex items-start justify-between">
                                <div
                                    :class="[
                                        'flex h-12 w-12 items-center justify-center rounded-control bg-bg',
                                        getFileTypeColor(document.fileType),
                                    ]"
                                >
                                    <component :is="getFileTypeIcon(document.fileType)" class="h-6 w-6" />
                                </div>

                                <button
                                    @click="toggleBookmark(document.id)"
                                    :class="[
                                        'rounded-control p-1.5 transition-colors',
                                        document.isBookmarked
                                            ? 'bg-warning-bg text-warning-fg'
                                            : 'text-content-faint hover:bg-warning-bg hover:text-warning-fg',
                                    ]"
                                    :aria-label="document.isBookmarked ? 'Remove bookmark' : 'Add bookmark'"
                                >
                                    <BookmarkIcon class="h-4 w-4" :class="{ 'fill-current': document.isBookmarked }" />
                                </button>
                            </div>

                            <h3 class="mb-2 line-clamp-2 text-base font-semibold text-content">
                                {{ document.title }}
                            </h3>

                            <p class="mb-4 line-clamp-3 text-sm text-content-muted">
                                {{ document.description }}
                            </p>

                            <!-- Tags -->
                            <div class="mb-4 flex flex-wrap gap-1.5">
                                <span :class="['rounded-full px-2 py-1 text-xs font-medium', getCategoryColor(document.category)]">
                                    {{ document.category }}
                                </span>
                                <Badge v-if="document.status" :variant="getStatusBadge(document.status)">
                                    {{ document.status }}
                                </Badge>
                                <span
                                    v-for="tag in document.tags.slice(0, 2)"
                                    :key="tag"
                                    class="inline-flex items-center gap-1 rounded-pill bg-bg px-2 py-1 text-xs text-content-muted"
                                >
                                    <TagIcon class="h-3 w-3" />{{ tag }}
                                </span>
                            </div>

                            <!-- Document Info -->
                            <div class="space-y-2 text-sm text-content-muted">
                                <div class="flex items-center gap-2">
                                    <UserIcon class="h-4 w-4" />
                                    <span>{{ document.uploadedBy }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CalendarIcon class="h-4 w-4" />
                                    <span>{{ formatDate(document.lastModified) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs">{{ document.size }}</span>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="inline-flex items-center gap-1">
                                            <EyeIcon class="h-3.5 w-3.5" />{{ document.views }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <ArrowDownTrayIcon class="h-3.5 w-3.5" />{{ document.downloads }}
                                        </span>
                                        <span v-if="document.rating" class="inline-flex items-center gap-1">
                                            <StarIcon class="h-3.5 w-3.5" />{{ document.rating }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="border-t border-line bg-bg p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="previewDocument(document)"
                                        class="inline-flex items-center gap-1 rounded-control bg-primary px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-primary-hover"
                                    >
                                        <EyeIcon class="h-3.5 w-3.5" />
                                        Preview
                                    </button>
                                    <button
                                        @click="downloadDocument(document)"
                                        class="inline-flex items-center gap-1 rounded-control bg-success-bg px-3 py-1.5 text-xs font-medium text-success-fg transition-colors hover:bg-success-bg/70"
                                    >
                                        <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                                        Download
                                    </button>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        @click="shareDocument(document)"
                                        class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-primary-soft hover:text-primary"
                                        aria-label="Share document"
                                    >
                                        <ShareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteDocument(document)"
                                        class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                        aria-label="Delete document"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- List View -->
                <Card v-else-if="viewMode === 'list' && filteredDocuments.length > 0" padding="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Document
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Category
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Author
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Modified
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Size
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="document in filteredDocuments"
                                    :key="document.id"
                                    class="border-b border-line transition-colors hover:bg-bg"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                :class="[
                                                    'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-control bg-bg',
                                                    getFileTypeColor(document.fileType),
                                                ]"
                                            >
                                                <component :is="getFileTypeIcon(document.fileType)" class="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="line-clamp-1 font-medium text-content">
                                                    {{ document.title }}
                                                </div>
                                                <div class="line-clamp-1 text-content-muted">
                                                    {{ document.description }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['rounded-pill px-2 py-1 text-xs font-medium', getCategoryColor(document.category)]">
                                            {{ document.category }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-content">
                                        {{ document.uploadedBy }}
                                    </td>
                                    <td class="px-4 py-3 text-content-muted">
                                        {{ formatDate(document.lastModified) }}
                                    </td>
                                    <td class="px-4 py-3 text-content-muted">
                                        {{ document.size }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="previewDocument(document)"
                                                class="rounded-control p-1.5 text-primary transition-colors hover:bg-primary-soft"
                                                aria-label="Preview document"
                                            >
                                                <EyeIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="downloadDocument(document)"
                                                class="rounded-control p-1.5 text-success-fg transition-colors hover:bg-success-bg"
                                                aria-label="Download document"
                                            >
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="toggleBookmark(document.id)"
                                                :class="[
                                                    'rounded-control p-1.5 transition-colors',
                                                    document.isBookmarked
                                                        ? 'text-warning-fg'
                                                        : 'text-content-faint hover:text-warning-fg',
                                                ]"
                                                :aria-label="document.isBookmarked ? 'Remove bookmark' : 'Add bookmark'"
                                            >
                                                <BookmarkIcon class="h-4 w-4" :class="{ 'fill-current': document.isBookmarked }" />
                                            </button>
                                            <button
                                                @click="shareDocument(document)"
                                                class="rounded-control p-1.5 text-content-faint transition-colors hover:text-primary"
                                                aria-label="Share document"
                                            >
                                                <ShareIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="deleteDocument(document)"
                                                class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                                aria-label="Delete document"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <!-- Empty State -->
                <Card v-if="filteredDocuments.length === 0" padding="p-0">
                    <EmptyState
                        title="No documents found"
                        description="Try adjusting your search terms or filters to find what you're looking for."
                        :icon="FolderIcon"
                    >
                        <button @click="clearAllFilters" class="ui-btn-secondary">
                            <XMarkIcon class="h-4 w-4" />
                            Clear All Filters
                        </button>
                    </EmptyState>
                </Card>

                <!-- Pagination (real server paging; only shown when there is more than one page) -->
                <div
                    v-if="pagination && pagination.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 sm:flex-row"
                >
                    <p class="text-sm text-content-muted">
                        Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} documents
                    </p>
                    <nav class="flex items-center gap-2">
                        <Link
                            v-if="pageLinks.prev"
                            :href="pageLinks.prev"
                            preserve-scroll
                            class="ui-btn-secondary"
                        >
                            Previous
                        </Link>
                        <span v-else class="ui-btn-secondary cursor-not-allowed opacity-50">Previous</span>

                        <span class="px-2 text-sm text-content-muted">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }}
                        </span>

                        <Link
                            v-if="pageLinks.next"
                            :href="pageLinks.next"
                            preserve-scroll
                            class="ui-btn-secondary"
                        >
                            Next
                        </Link>
                        <span v-else class="ui-btn-secondary cursor-not-allowed opacity-50">Next</span>
                    </nav>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import {
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    BookmarkIcon,
    FolderIcon,
    Squares2X2Icon,
    ListBulletIcon,
    ChatBubbleLeftRightIcon,
    DocumentIcon,
    PresentationChartBarIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    CalendarIcon,
    PencilSquareIcon,
    BeakerIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

// Server props
const props = defineProps({
    documents: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ category: '', search: '' })
    },
    categories: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

// Component state
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || 'all');
const selectedDepartment = ref('all');
const selectedType = ref('all');
const sortBy = ref('date_desc');
const viewMode = ref('grid');
const showFilters = ref(false);
const selectedDocuments = ref(new Set());

// Student context (derived from authenticated user)
const studentContext = computed(() => {
    const authUser = page.props?.auth?.user || {};
    return {
        name: authUser.name || 'Student',
        department: authUser.department?.name || authUser.department || '',
        semester: authUser.semester ? `${authUser.semester} Semester` : '',
        currentCourses: []
    };
});

// Sort options
const sortOptions = ref([
    { value: 'date_desc', label: 'Newest First' },
    { value: 'date_asc', label: 'Oldest First' },
    { value: 'name_asc', label: 'Name A-Z' },
    { value: 'downloads_desc', label: 'Most Downloaded' },
    { value: 'relevance', label: 'Most Relevant' }
]);

// Category icon/color palette cycled for derived categories
const categoryPalette = [
    { icon: BookOpenIcon, color: 'green' },
    { icon: ClipboardDocumentListIcon, color: 'purple' },
    { icon: DocumentIcon, color: 'red' },
    { icon: CalendarIcon, color: 'yellow' },
    { icon: PencilSquareIcon, color: 'indigo' },
    { icon: DocumentTextIcon, color: 'cyan' },
    { icon: BeakerIcon, color: 'pink' }
];

// Document categories derived from server props (with "all" option preserved)
const categories = computed(() => {
    const serverCategories = (props.categories || []).map((name, index) => {
        const palette = categoryPalette[index % categoryPalette.length];
        return {
            id: name,
            name,
            count: documents.value.filter(doc => doc.category === name).length,
            icon: palette.icon,
            color: palette.color
        };
    });

    return [
        { id: 'all', name: 'All Documents', count: documents.value.length, icon: FolderIcon, color: 'blue' },
        ...serverCategories
    ];
});

// Local bookmark state (keyed by document id)
const bookmarkedIds = ref(new Set());

// Documents derived from server props (mapped into template field names)
const documents = computed(() => {
    return (props.documents?.data || []).map(doc => ({
        id: doc.id,
        title: doc.title,
        description: doc.description,
        category: doc.category,
        department: doc.department,
        type: doc.type ? String(doc.type).toUpperCase() : '',
        fileSize: doc.fileSize,
        pages: doc.pages,
        uploadedBy: doc.uploadedBy,
        uploadedAt: doc.uploadedAt,
        lastUpdated: doc.uploadedAt,
        version: doc.version,
        downloads: doc.downloads,
        views: doc.views,
        tags: doc.tags || [],
        isBookmarked: bookmarkedIds.value.has(doc.id),
        url: doc.downloadUrl,
        downloadUrl: doc.downloadUrl,
        thumbnail: `https://via.placeholder.com/200x280/3B82F6/FFFFFF?text=${encodeURIComponent(doc.type ? String(doc.type).toUpperCase() : 'DOC')}`,
        relevanceScore: 0
    }));
});

// Computed properties
const filteredDocuments = computed(() => {
    let filtered = [...documents.value];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(doc =>
            doc.title.toLowerCase().includes(query) ||
            doc.description.toLowerCase().includes(query) ||
            doc.tags.some(tag => tag.toLowerCase().includes(query))
        );
    }

    // Category filter
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(doc => doc.category === selectedCategory.value);
    }

    // Department filter
    if (selectedDepartment.value !== 'all') {
        filtered = filtered.filter(doc => doc.department === selectedDepartment.value);
    }

    // Type filter
    if (selectedType.value !== 'all') {
        filtered = filtered.filter(doc => doc.type === selectedType.value);
    }

    // Sorting
    switch (sortBy.value) {
        case 'date_asc':
            filtered.sort((a, b) => new Date(a.uploadedAt) - new Date(b.uploadedAt));
            break;
        case 'name_asc':
            filtered.sort((a, b) => a.title.localeCompare(b.title));
            break;
        case 'downloads_desc':
            filtered.sort((a, b) => b.downloads - a.downloads);
            break;
        case 'relevance':
            filtered.sort((a, b) => b.relevanceScore - a.relevanceScore);
            break;
        case 'date_desc':
        default:
            filtered.sort((a, b) => new Date(b.uploadedAt) - new Date(a.uploadedAt));
    }

    return filtered;
});

// Utility functions
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

// Resolve a file-type icon component from the document type
const fileTypeIcon = (type) => {
    const normalized = String(type || '').toUpperCase();
    if (normalized === 'PPT' || normalized === 'PPTX') return PresentationChartBarIcon;
    if (normalized === 'PDF' || normalized === 'DOC' || normalized === 'DOCX') return DocumentTextIcon;
    return DocumentIcon;
};

// Actions
const toggleBookmark = (documentId) => {
    if (bookmarkedIds.value.has(documentId)) {
        bookmarkedIds.value.delete(documentId);
    } else {
        bookmarkedIds.value.add(documentId);
    }
    bookmarkedIds.value = new Set(bookmarkedIds.value);
};

const downloadDocument = (document) => {
    if (document.downloadUrl) {
        window.open(document.downloadUrl, '_blank');
    }
};

const previewDocument = (document) => {
    if (document.downloadUrl) {
        window.open(document.downloadUrl, '_blank');
    }
};

const askAIAboutDocument = (document) => {
    // Navigate to chat with document context
    const query = `Tell me about ${document.title}`;
    window.open(`/chat?q=${encodeURIComponent(query)}&doc=${document.id}`, '_blank');
};
</script>

<template>
    <div>
        <Head title="University Documents" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Documents"
                    :subtitle="`${filteredDocuments.length} documents available${studentContext.department ? ' • ' + studentContext.department : ''}`"
                    :icon="DocumentTextIcon"
                    eyebrow="Library"
                >
                    <template #actions>
                        <!-- View Mode Toggle -->
                        <div class="flex bg-neutral-bg rounded-control p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="`p-2 rounded-control transition-colors ${viewMode === 'grid' ? 'bg-surface shadow-card text-primary' : 'text-content-muted'}`"
                                aria-label="Grid view"
                            >
                                <Squares2X2Icon class="w-5 h-5" />
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="`p-2 rounded-control transition-colors ${viewMode === 'list' ? 'bg-surface shadow-card text-primary' : 'text-content-muted'}`"
                                aria-label="List view"
                            >
                                <ListBulletIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <Link href="/dashboard" class="ui-btn-secondary">
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Sidebar - Categories -->
                    <div class="lg:col-span-1">
                        <Card title="Categories" :icon="FolderIcon" class="lg:sticky lg:top-8">
                            <div class="space-y-2">
                                <button
                                    v-for="category in categories"
                                    :key="category.id"
                                    @click="selectedCategory = category.id"
                                    :class="`w-full text-left p-3 rounded-control border transition-colors ${
                                        selectedCategory === category.id
                                            ? 'bg-primary-soft text-primary border-primary'
                                            : 'hover:bg-primary-soft hover:text-primary border-transparent text-content-muted'
                                    }`"
                                >
                                    <div class="flex items-center gap-3">
                                        <component :is="category.icon" v-if="category.icon" class="h-5 w-5 flex-shrink-0" />
                                        <FolderIcon v-else class="h-5 w-5 flex-shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-sm truncate">{{ category.name }}</div>
                                            <div class="text-xs text-content-faint">{{ category.count }} files</div>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <!-- Department Filter -->
                            <div class="mt-6 pt-6 border-t border-line">
                                <label class="ui-label">Department</label>
                                <select
                                    v-model="selectedDepartment"
                                    class="ui-input mt-1"
                                >
                                    <option value="all">All Departments</option>
                                    <option value="Computer Science Engineering">Computer Science</option>
                                    <option value="Electrical Engineering">Electrical Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                        </Card>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Search & Controls -->
                        <Card>
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                <!-- Search -->
                                <div class="flex-1 relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon class="h-5 w-5 text-content-faint" />
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search documents, handbooks, policies..."
                                        class="ui-input pl-10"
                                    />
                                </div>

                                <!-- Filters Toggle -->
                                <button
                                    @click="showFilters = !showFilters"
                                    :class="`flex items-center gap-2 px-4 py-2 rounded-control border transition-colors ${showFilters ? 'bg-primary-soft text-primary border-primary' : 'bg-surface text-content-muted border-line hover:bg-primary-soft hover:text-primary'}`"
                                >
                                    <FunnelIcon class="w-5 h-5" />
                                    Filters
                                </button>
                            </div>

                            <!-- Expanded Filters -->
                            <div v-if="showFilters" class="mt-4 pt-4 border-t border-line">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- File Type Filter -->
                                    <div>
                                        <label class="ui-label">File Type</label>
                                        <select
                                            v-model="selectedType"
                                            class="ui-input mt-1"
                                        >
                                            <option value="all">All Types</option>
                                            <option value="PDF">PDF</option>
                                            <option value="DOC">Word Document</option>
                                            <option value="PPT">Presentation</option>
                                        </select>
                                    </div>

                                    <!-- Sort By -->
                                    <div>
                                        <label class="ui-label">Sort By</label>
                                        <select
                                            v-model="sortBy"
                                            class="ui-input mt-1"
                                        >
                                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Clear Filters -->
                                    <div class="flex items-end">
                                        <button
                                            @click="selectedCategory = 'all'; selectedDepartment = 'all'; selectedType = 'all'; searchQuery = ''"
                                            class="ui-btn-secondary w-full justify-center"
                                        >
                                            <XMarkIcon class="w-4 h-4" />
                                            Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Documents Grid/List -->
                        <div v-if="filteredDocuments.length > 0" :class="`${viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6' : 'space-y-4'}`">
                            <div
                                v-for="document in filteredDocuments"
                                :key="document.id"
                                :class="`ui-card overflow-hidden transition-all duration-200 hover:shadow-lg ${viewMode === 'grid' ? 'hover:-translate-y-0.5 flex flex-col' : 'flex items-center gap-5 p-5'}`"
                            >
                                <!-- Grid View -->
                                <div v-if="viewMode === 'grid'" class="p-5 flex flex-col flex-1">
                                    <!-- Document Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="ui-icon-tile h-14 w-14 bg-primary-soft text-primary flex-shrink-0">
                                            <component :is="fileTypeIcon(document.type)" class="w-7 h-7" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Badge variant="brand">{{ document.type }}</Badge>
                                            <button
                                                @click="toggleBookmark(document.id)"
                                                :class="`p-2 rounded-control transition-colors ${
                                                    document.isBookmarked
                                                        ? 'text-warning-fg bg-warning-bg'
                                                        : 'text-content-faint hover:text-warning-fg hover:bg-warning-bg'
                                                }`"
                                                :aria-label="document.isBookmarked ? 'Remove bookmark' : 'Add bookmark'"
                                            >
                                                <BookmarkIcon class="w-5 h-5" :class="{ 'fill-current': document.isBookmarked }" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Document Info -->
                                    <div class="space-y-3 flex flex-col flex-1">
                                        <h3 class="font-semibold text-content text-lg line-clamp-2">
                                            {{ document.title }}
                                        </h3>

                                        <p class="text-sm text-content-muted line-clamp-2">
                                            {{ document.description }}
                                        </p>

                                        <!-- Metadata -->
                                        <div class="flex items-center justify-between text-xs text-content-faint">
                                            <span>{{ document.fileSize }} • {{ document.pages }} pages</span>
                                            <span>{{ document.downloads }} downloads</span>
                                        </div>

                                        <div class="text-xs text-content-faint">
                                            Updated {{ formatDate(document.lastUpdated) }}
                                        </div>

                                        <!-- Tags -->
                                        <div class="flex flex-wrap gap-1.5">
                                            <Badge
                                                v-for="tag in document.tags.slice(0, 3)"
                                                :key="tag"
                                                variant="slate"
                                            >
                                                #{{ tag }}
                                            </Badge>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 pt-3 mt-auto border-t border-line">
                                            <button
                                                @click="previewDocument(document)"
                                                class="flex-1 ui-btn-secondary justify-center text-sm"
                                            >
                                                <EyeIcon class="w-4 h-4" />
                                                Preview
                                            </button>
                                            <button
                                                @click="downloadDocument(document)"
                                                class="flex-1 ui-btn-primary justify-center text-sm"
                                            >
                                                <ArrowDownTrayIcon class="w-4 h-4" />
                                                Download
                                            </button>
                                            <button
                                                @click="askAIAboutDocument(document)"
                                                class="p-2 rounded-control border border-line text-primary hover:bg-primary-soft transition-colors"
                                                aria-label="Ask AI about this document"
                                            >
                                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- List View -->
                                <div v-else class="flex items-center gap-5 w-full">
                                    <!-- Document Icon -->
                                    <div class="ui-icon-tile w-14 h-16 bg-primary-soft text-primary flex-shrink-0">
                                        <component :is="fileTypeIcon(document.type)" class="w-7 h-7" />
                                    </div>

                                    <!-- Document Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0 mr-4">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h3 class="font-semibold text-content text-lg truncate">
                                                        {{ document.title }}
                                                    </h3>
                                                    <Badge variant="brand">{{ document.type }}</Badge>
                                                </div>
                                                <p class="text-sm text-content-muted mb-2 line-clamp-1">
                                                    {{ document.description }}
                                                </p>
                                                <div class="flex items-center gap-4 text-xs text-content-faint">
                                                    <span>{{ document.fileSize }}</span>
                                                    <span>{{ document.downloads }} downloads</span>
                                                    <span>Updated {{ formatDate(document.lastUpdated) }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                <button
                                                    @click="toggleBookmark(document.id)"
                                                    :class="`p-2 rounded-control transition-colors ${
                                                        document.isBookmarked
                                                            ? 'text-warning-fg bg-warning-bg'
                                                            : 'text-content-faint hover:text-warning-fg hover:bg-warning-bg'
                                                    }`"
                                                    :aria-label="document.isBookmarked ? 'Remove bookmark' : 'Add bookmark'"
                                                >
                                                    <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': document.isBookmarked }" />
                                                </button>
                                                <button
                                                    @click="previewDocument(document)"
                                                    class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                                    aria-label="Preview document"
                                                >
                                                    <EyeIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    @click="downloadDocument(document)"
                                                    class="p-2 text-content-muted hover:text-success-fg hover:bg-success-bg rounded-control transition-colors"
                                                    aria-label="Download document"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    @click="askAIAboutDocument(document)"
                                                    class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                                    aria-label="Ask AI about this document"
                                                >
                                                    <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <EmptyState
                            v-else
                            title="No documents found"
                            description="Try adjusting your search terms or filters to find the documents you're looking for."
                            :icon="DocumentTextIcon"
                        >
                            <button
                                @click="selectedCategory = 'all'; selectedDepartment = 'all'; selectedType = 'all'; searchQuery = ''"
                                class="ui-btn-primary"
                            >
                                Clear All Filters
                            </button>
                        </EmptyState>

                        <Pagination :paginator="documents" label="documents" />
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Line clamp utilities */
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
</style>

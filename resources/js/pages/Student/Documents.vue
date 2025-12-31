<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    BookmarkIcon,
    CalendarIcon,
    TagIcon,
    AcademicCapIcon,
    FolderIcon,
    StarIcon,
    ClockIcon,
    UserIcon,
    ChevronDownIcon,
    Squares2X2Icon,
    ListBulletIcon,
    ShareIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

// Component state
const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedDepartment = ref('all');
const selectedType = ref('all');
const sortBy = ref('date_desc');
const viewMode = ref('grid');
const showFilters = ref(false);
const selectedDocuments = ref(new Set());

// Student context
const studentContext = ref({
    name: 'John Doe',
    department: 'Computer Science Engineering',
    semester: '5th Semester',
    currentCourses: ['Database Systems', 'Machine Learning', 'Software Engineering', 'Computer Networks']
});

// Sort options
const sortOptions = ref([
    { value: 'date_desc', label: 'Newest First' },
    { value: 'date_asc', label: 'Oldest First' },
    { value: 'name_asc', label: 'Name A-Z' },
    { value: 'downloads_desc', label: 'Most Downloaded' },
    { value: 'relevance', label: 'Most Relevant' }
]);

// Document categories
const categories = ref([
    { id: 'all', name: 'All Documents', count: 245, icon: '📁', color: 'blue' },
    { id: 'handbook', name: 'Academic Handbook', count: 15, icon: '📖', color: 'green' },
    { id: 'syllabus', name: 'Course Syllabus', count: 48, icon: '📋', color: 'purple' },
    { id: 'policies', name: 'University Policies', count: 23, icon: '📄', color: 'red' },
    { id: 'schedules', name: 'Exam Schedules', count: 12, icon: '📅', color: 'yellow' },
    { id: 'forms', name: 'Application Forms', count: 34, icon: '📝', color: 'indigo' },
    { id: 'guidelines', name: 'Guidelines', count: 67, icon: '📚', color: 'cyan' },
    { id: 'research', name: 'Research Papers', count: 89, icon: '🔬', color: 'pink' }
]);

// Mock documents data
const documents = ref([
    {
        id: 1,
        title: 'Computer Science Engineering Handbook 2024',
        description: 'Complete academic handbook covering course structure, policies, and guidelines for CSE department.',
        category: 'handbook',
        department: 'Computer Science Engineering',
        type: 'PDF',
        fileSize: '2.4 MB',
        pages: 156,
        uploadedBy: 'Academic Office',
        uploadedAt: '2024-01-15T10:30:00',
        lastUpdated: '2024-01-10T14:20:00',
        version: '2024.1',
        downloads: 1247,
        views: 3456,
        tags: ['Academic', 'CSE', 'Policies', 'Guidelines'],
        isBookmarked: false,
        url: '/documents/cse-handbook-2024.pdf',
        thumbnail: 'https://via.placeholder.com/200x280/3B82F6/FFFFFF?text=CSE+Handbook',
        relevanceScore: 95
    },
    {
        id: 2,
        title: 'Database Systems Course Syllabus',
        description: 'Detailed syllabus for Database Management Systems covering all units, assignments, and examination pattern.',
        category: 'syllabus',
        department: 'Computer Science Engineering',
        type: 'PDF',
        fileSize: '890 KB',
        pages: 24,
        uploadedBy: 'Dr. Sarah Smith',
        uploadedAt: '2024-01-12T09:15:00',
        lastUpdated: '2024-01-05T16:45:00',
        version: '2024.1',
        downloads: 892,
        views: 2134,
        tags: ['DBMS', 'Syllabus', 'Database', 'Course'],
        isBookmarked: true,
        url: '/documents/dbms-syllabus-2024.pdf',
        thumbnail: 'https://via.placeholder.com/200x280/10B981/FFFFFF?text=DBMS+Syllabus',
        relevanceScore: 88
    }
    // Add more mock documents...
]);

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

const getCategoryColor = (categoryId) => {
    const category = categories.value.find(c => c.id === categoryId);
    return category?.color || 'gray';
};

// Actions
const toggleBookmark = (documentId) => {
    const doc = documents.value.find(d => d.id === documentId);
    if (doc) {
        doc.isBookmarked = !doc.isBookmarked;
    }
};

const downloadDocument = (document) => {
    // Simulate download
    document.downloads++;
    alert(`Downloading: ${document.title}`);
};

const previewDocument = (document) => {
    // Simulate document preview
    document.views++;
    alert(`Opening preview for: ${document.title}`);
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
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                                    <DocumentTextIcon class="w-10 h-10" />
                                    University Documents
                                </h1>
                                <p class="text-xl text-white/90 mb-2">
                                    Access official documents, handbooks, and academic resources
                                </p>
                                <p class="text-white/80">
                                    {{ filteredDocuments.length }} documents available • {{ studentContext.department }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Quick Stats -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center">
                                    <div class="text-2xl font-bold">{{ filteredDocuments.length }}</div>
                                    <div class="text-xs text-white/80">Available</div>
                                </div>

                                <Link
                                    href="/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <!-- Left Sidebar - Categories -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-8">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <FolderIcon class="w-5 h-5 text-blue-600" />
                                    Categories
                                </h3>

                                <div class="space-y-2">
                                    <button
                                        v-for="category in categories"
                                        :key="category.id"
                                        @click="selectedCategory = category.id"
                                        :class="`w-full text-left p-3 rounded-xl transition-all ${
                                            selectedCategory === category.id
                                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-2 border-blue-300 dark:border-blue-600'
                                                : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-2 border-transparent'
                                        }`"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg">{{ category.icon }}</span>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm">{{ category.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ category.count }} files</div>
                                            </div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Department Filter -->
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">Department</h4>
                                    <select
                                        v-model="selectedDepartment"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="all">All Departments</option>
                                        <option value="Computer Science Engineering">Computer Science</option>
                                        <option value="Electrical Engineering">Electrical Engineering</option>
                                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="lg:col-span-3">
                            <!-- Search & Controls -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                    <!-- Search -->
                                    <div class="flex-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search documents, handbooks, policies..."
                                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- View Mode Toggle -->
                                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                        <button
                                            @click="viewMode = 'grid'"
                                            :class="`p-2 rounded-lg transition-colors ${viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''}`"
                                        >
                                            <Squares2X2Icon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                        </button>
                                        <button
                                            @click="viewMode = 'list'"
                                            :class="`p-2 rounded-lg transition-colors ${viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''}`"
                                        >
                                            <ListBulletIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                        </button>
                                    </div>

                                    <!-- Filters Toggle -->
                                    <button
                                        @click="showFilters = !showFilters"
                                        :class="`flex items-center gap-2 px-4 py-2 rounded-xl transition-colors ${showFilters ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'}`"
                                    >
                                        <FunnelIcon class="w-5 h-5" />
                                        Filters
                                    </button>
                                </div>

                                <!-- Expanded Filters -->
                                <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- File Type Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File Type</label>
                                            <select
                                                v-model="selectedType"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="all">All Types</option>
                                                <option value="PDF">PDF</option>
                                                <option value="DOC">Word Document</option>
                                                <option value="PPT">Presentation</option>
                                            </select>
                                        </div>

                                        <!-- Sort By -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                                            <select
                                                v-model="sortBy"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                                                class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm"
                                            >
                                                Clear Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Grid/List -->
                            <div v-if="filteredDocuments.length > 0" :class="`${viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' : 'space-y-4'}`">
                                <div
                                    v-for="document in filteredDocuments"
                                    :key="document.id"
                                    :class="`bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 ${viewMode === 'grid' ? 'transform hover:-translate-y-1' : 'flex items-center gap-6 p-6'}`"
                                >
                                    <!-- Grid View -->
                                    <div v-if="viewMode === 'grid'" class="p-6">
                                        <!-- Document Thumbnail -->
                                        <div class="relative mb-4">
                                            <img
                                                :src="document.thumbnail"
                                                :alt="document.title"
                                                class="w-full h-48 object-cover rounded-xl bg-gray-100 dark:bg-gray-700"
                                            />
                                            <div class="absolute top-2 right-2 flex gap-2">
                                                <button
                                                    @click="toggleBookmark(document.id)"
                                                    :class="`p-2 rounded-lg backdrop-blur-sm transition-colors ${
                                                        document.isBookmarked
                                                            ? 'bg-yellow-500 text-white'
                                                            : 'bg-white/80 text-gray-700 hover:bg-yellow-100'
                                                    }`"
                                                >
                                                    <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': document.isBookmarked }" />
                                                </button>
                                            </div>
                                            <div class="absolute bottom-2 left-2">
                                                <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded-lg font-medium">
                                                    {{ document.type }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Document Info -->
                                        <div class="space-y-3">
                                            <h3 class="font-bold text-gray-900 dark:text-white text-lg line-clamp-2">
                                                {{ document.title }}
                                            </h3>

                                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                {{ document.description }}
                                            </p>

                                            <!-- Metadata -->
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ document.fileSize }} • {{ document.pages }} pages</span>
                                                <span>{{ document.downloads }} downloads</span>
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Updated {{ formatDate(document.lastUpdated) }}
                                            </div>

                                            <!-- Tags -->
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="tag in document.tags.slice(0, 3)"
                                                    :key="tag"
                                                    class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-full"
                                                >
                                                    #{{ tag }}
                                                </span>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                <button
                                                    @click="previewDocument(document)"
                                                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
                                                >
                                                    <EyeIcon class="w-4 h-4" />
                                                    Preview
                                                </button>
                                                <button
                                                    @click="downloadDocument(document)"
                                                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                    Download
                                                </button>
                                                <button
                                                    @click="askAIAboutDocument(document)"
                                                    class="p-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                                    title="Ask AI"
                                                >
                                                    <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- List View -->
                                    <div v-else class="flex items-center gap-6 w-full">
                                        <!-- Document Icon/Thumbnail -->
                                        <div class="w-16 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <DocumentTextIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                                        </div>

                                        <!-- Document Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0 mr-4">
                                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg mb-1">
                                                        {{ document.title }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-1">
                                                        {{ document.description }}
                                                    </p>
                                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                        <span>{{ document.type }} • {{ document.fileSize }}</span>
                                                        <span>{{ document.downloads }} downloads</span>
                                                        <span>Updated {{ formatDate(document.lastUpdated) }}</span>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <button
                                                        @click="toggleBookmark(document.id)"
                                                        :class="`p-2 rounded-lg transition-colors ${
                                                            document.isBookmarked
                                                                ? 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                                : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                                                        }`"
                                                    >
                                                        <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': document.isBookmarked }" />
                                                    </button>
                                                    <button
                                                        @click="previewDocument(document)"
                                                        class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                    >
                                                        <EyeIcon class="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        @click="downloadDocument(document)"
                                                        class="p-2 text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                                    >
                                                        <ArrowDownTrayIcon class="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        @click="askAIAboutDocument(document)"
                                                        class="p-2 text-purple-600 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors"
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
                            <div v-else class="text-center py-16">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <DocumentTextIcon class="w-12 h-12 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No documents found</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                    Try adjusting your search terms or filters to find the documents you're looking for.
                                </p>
                                <button
                                    @click="selectedCategory = 'all'; selectedDepartment = 'all'; selectedType = 'all'; searchQuery = ''"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
                                >
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
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

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover effects */
.hover\:-translate-y-1:hover {
    transform: translateY(-0.25rem);
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
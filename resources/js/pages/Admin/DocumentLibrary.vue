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
    ShareIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    ChevronDownIcon,
    Squares2X2Icon,
    ListBulletIcon,
    StarIcon,
    ClockIcon,
    FolderIcon
} from '@heroicons/vue/24/outline';

// Component state
const viewMode = ref('grid');
const selectedCategory = ref('all');
const selectedDepartment = ref('all');
const selectedFileType = ref('all');
const sortBy = ref('recent');
const searchQuery = ref('');
const showFilters = ref(false);
const selectedDocuments = ref(new Set());

// Mock documents data
const documents = ref([
    {
        id: 1,
        title: 'Computer Science Engineering Syllabus 2024-25',
        description: 'Complete syllabus for CSE program including all courses, prerequisites, and learning outcomes for the academic year 2024-25.',
        department: 'Computer Science Engineering',
        category: 'Syllabus',
        fileType: 'PDF',
        size: '2.4 MB',
        version: '2.1',
        uploadDate: '2024-01-15T10:30:00',
        lastModified: '2024-01-20T14:45:00',
        uploadedBy: 'Dr. Sarah Smith',
        status: 'approved',
        views: 1234,
        downloads: 456,
        tags: ['syllabus', 'cse', 'curriculum', '2024'],
        thumbnail: '/images/documents/pdf-thumbnail.png',
        previewUrl: '/documents/preview/cse-syllabus-2024.pdf',
        downloadUrl: '/documents/download/cse-syllabus-2024.pdf',
        isBookmarked: true,
        rating: 4.8
    },
    {
        id: 2,
        title: 'Academic Handbook 2024',
        description: 'Comprehensive handbook covering all academic policies, procedures, and regulations for students and faculty.',
        department: 'Administration',
        category: 'Handbook',
        fileType: 'PDF',
        size: '5.2 MB',
        version: '1.3',
        uploadDate: '2024-01-10T09:15:00',
        lastModified: '2024-01-18T16:20:00',
        uploadedBy: 'Academic Office',
        status: 'approved',
        views: 2105,
        downloads: 789,
        tags: ['handbook', 'policies', 'academic', 'regulations'],
        thumbnail: '/images/documents/handbook-thumbnail.png',
        previewUrl: '/documents/preview/academic-handbook-2024.pdf',
        downloadUrl: '/documents/download/academic-handbook-2024.pdf',
        isBookmarked: false,
        rating: 4.9
    },
    {
        id: 3,
        title: 'Final Exam Schedule Spring 2024',
        description: 'Complete examination timetable for all undergraduate and postgraduate courses.',
        department: 'Examination Board',
        category: 'Schedule',
        fileType: 'PDF',
        size: '1.2 MB',
        version: '1.0',
        uploadDate: '2024-01-13T16:45:00',
        lastModified: '2024-01-19T11:30:00',
        uploadedBy: 'Dr. Emily Chen',
        status: 'approved',
        views: 892,
        downloads: 234,
        tags: ['exam', 'schedule', 'spring2024', 'timetable'],
        thumbnail: '/images/documents/schedule-thumbnail.png',
        previewUrl: '/documents/preview/exam-schedule-spring-2024.pdf',
        downloadUrl: '/documents/download/exam-schedule-spring-2024.pdf',
        isBookmarked: true,
        rating: 4.7
    },
    {
        id: 4,
        title: 'Attendance Policy Update',
        description: 'Revised attendance policy with new guidelines for hybrid learning and medical leave procedures.',
        department: 'Administration',
        category: 'Policy',
        fileType: 'DOCX',
        size: '856 KB',
        version: '2.0',
        uploadDate: '2024-01-14T09:15:00',
        lastModified: '2024-01-17T15:20:00',
        uploadedBy: 'Prof. Michael Johnson',
        status: 'approved',
        views: 567,
        downloads: 123,
        tags: ['policy', 'attendance', 'hybrid', 'guidelines'],
        thumbnail: '/images/documents/docx-thumbnail.png',
        previewUrl: '/documents/preview/attendance-policy-2024.docx',
        downloadUrl: '/documents/download/attendance-policy-2024.docx',
        isBookmarked: false,
        rating: 4.5
    }
]);

// Filter options
const categories = [
    { value: 'all', label: 'All Categories' },
    { value: 'syllabus', label: 'Syllabus' },
    { value: 'handbook', label: 'Handbook' },
    { value: 'policy', label: 'Policy' },
    { value: 'schedule', label: 'Schedule' },
    { value: 'guidelines', label: 'Guidelines' }
];

const departments = [
    { value: 'all', label: 'All Departments' },
    { value: 'cse', label: 'Computer Science' },
    { value: 'ee', label: 'Electrical Engineering' },
    { value: 'me', label: 'Mechanical Engineering' },
    { value: 'administration', label: 'Administration' },
    { value: 'examination', label: 'Examination Board' }
];

const fileTypes = [
    { value: 'all', label: 'All Types' },
    { value: 'pdf', label: 'PDF' },
    { value: 'docx', label: 'Word Document' },
    { value: 'pptx', label: 'PowerPoint' },
    { value: 'xlsx', label: 'Excel' }
];

const sortOptions = [
    { value: 'recent', label: 'Most Recent' },
    { value: 'popular', label: 'Most Popular' },
    { value: 'downloads', label: 'Most Downloads' },
    { value: 'alphabetical', label: 'A-Z' },
    { value: 'rating', label: 'Highest Rated' }
];

// Computed properties
const filteredDocuments = computed(() => {
    let filtered = documents.value;

    // Apply filters
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(doc => doc.category.toLowerCase() === selectedCategory.value);
    }

    if (selectedDepartment.value !== 'all') {
        filtered = filtered.filter(doc => {
            const deptMatch = selectedDepartment.value;
            return doc.department.toLowerCase().includes(deptMatch) ||
                   deptMatch === 'cse' && doc.department.toLowerCase().includes('computer') ||
                   deptMatch === 'ee' && doc.department.toLowerCase().includes('electrical') ||
                   deptMatch === 'me' && doc.department.toLowerCase().includes('mechanical') ||
                   deptMatch === 'examination' && doc.department.toLowerCase().includes('examination');
        });
    }

    if (selectedFileType.value !== 'all') {
        filtered = filtered.filter(doc => doc.fileType.toLowerCase() === selectedFileType.value);
    }

    // Apply search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(doc =>
            doc.title.toLowerCase().includes(query) ||
            doc.description.toLowerCase().includes(query) ||
            doc.tags.some(tag => tag.toLowerCase().includes(query)) ||
            doc.uploadedBy.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    switch (sortBy.value) {
        case 'popular':
            return filtered.sort((a, b) => b.views - a.views);
        case 'downloads':
            return filtered.sort((a, b) => b.downloads - a.downloads);
        case 'alphabetical':
            return filtered.sort((a, b) => a.title.localeCompare(b.title));
        case 'rating':
            return filtered.sort((a, b) => (b.rating || 0) - (a.rating || 0));
        case 'recent':
        default:
            return filtered.sort((a, b) => new Date(b.lastModified) - new Date(a.lastModified));
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
        syllabus: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        handbook: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        policy: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        schedule: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        guidelines: 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400'
    };
    return colors[category.toLowerCase()] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

const getFileTypeColor = (fileType) => {
    const colors = {
        pdf: 'text-red-600',
        docx: 'text-blue-600',
        pptx: 'text-orange-600',
        xlsx: 'text-green-600'
    };
    return colors[fileType.toLowerCase()] || 'text-gray-600';
};

// Actions
const toggleBookmark = (docId) => {
    const doc = documents.value.find(d => d.id === docId);
    if (doc) {
        doc.isBookmarked = !doc.isBookmarked;
    }
};

const downloadDocument = (document) => {
    document.downloads++;
    alert(`Downloading: ${document.title}`);
};

const previewDocument = (document) => {
    document.views++;
    alert(`Opening preview: ${document.title}`);
};

const shareDocument = (document) => {
    alert(`Sharing: ${document.title}`);
};

const clearAllFilters = () => {
    selectedCategory.value = 'all';
    selectedDepartment.value = 'all';
    selectedFileType.value = 'all';
    searchQuery.value = '';
    sortBy.value = 'recent';
};
</script>

<template>
    <div>
        <Head title="Document Library" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    📚 Document Library
                                </h1>
                                <p class="text-xl text-white/90">
                                    Browse and access all academic documents
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-3 text-white text-center">
                                    <div class="text-2xl font-bold">{{ filteredDocuments.length }}</div>
                                    <div class="text-xs text-white/80">Documents</div>
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
                    <!-- Search and Controls -->
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
                                    placeholder="Search documents, titles, authors, or tags..."
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Controls -->
                            <div class="flex items-center gap-4">
                                <!-- View Toggle -->
                                <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                                    <button
                                        @click="viewMode = 'grid'"
                                        :class="`p-2 rounded-md transition-colors ${
                                            viewMode === 'grid'
                                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                        }`"
                                    >
                                        <Squares2X2Icon class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        :class="`p-2 rounded-md transition-colors ${
                                            viewMode === 'list'
                                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                        }`"
                                    >
                                        <ListBulletIcon class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Filters Toggle -->
                                <button
                                    @click="showFilters = !showFilters"
                                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    <FunnelIcon class="w-4 h-4" />
                                    Filters
                                </button>
                            </div>
                        </div>

                        <!-- Filters Panel -->
                        <div v-if="showFilters" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Category Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Category
                                    </label>
                                    <select
                                        v-model="selectedCategory"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option v-for="category in categories" :key="category.value" :value="category.value">
                                            {{ category.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Department Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Department
                                    </label>
                                    <select
                                        v-model="selectedDepartment"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option v-for="department in departments" :key="department.value" :value="department.value">
                                            {{ department.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- File Type Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        File Type
                                    </label>
                                    <select
                                        v-model="selectedFileType"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option v-for="fileType in fileTypes" :key="fileType.value" :value="fileType.value">
                                            {{ fileType.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Sort Options -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Sort By
                                    </label>
                                    <select
                                        v-model="sortBy"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Clear Filters -->
                            <div class="mt-4 flex justify-end">
                                <button
                                    @click="clearAllFilters"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                                >
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Grid/List -->
                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div
                            v-for="document in filteredDocuments"
                            :key="document.id"
                            class="document-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden"
                        >
                            <!-- Document Header -->
                            <div class="relative p-6 pb-4">
                                <div class="flex items-start justify-between mb-4">
                                    <div :class="`w-12 h-12 rounded-xl flex items-center justify-center ${getFileTypeColor(document.fileType)} bg-gray-100 dark:bg-gray-700`">
                                        <DocumentTextIcon class="w-6 h-6" />
                                    </div>

                                    <button
                                        @click="toggleBookmark(document.id)"
                                        :class="`p-1.5 rounded-lg transition-colors ${
                                            document.isBookmarked
                                                ? 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                                        }`"
                                    >
                                        <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': document.isBookmarked }" />
                                    </button>
                                </div>

                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                    {{ document.title }}
                                </h3>

                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                                    {{ document.description }}
                                </p>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1 mb-4">
                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getCategoryColor(document.category)}`">
                                        {{ document.category }}
                                    </span>
                                    <span
                                        v-for="tag in document.tags.slice(0, 2)"
                                        :key="tag"
                                        class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-full"
                                    >
                                        #{{ tag }}
                                    </span>
                                </div>

                                <!-- Document Info -->
                                <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <UserIcon class="w-4 h-4" />
                                        <span>{{ document.uploadedBy }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CalendarIcon class="w-4 h-4" />
                                        <span>{{ formatDate(document.lastModified) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs">{{ document.size }}</span>
                                        <div class="flex items-center gap-3 text-xs">
                                            <span>👁️ {{ document.views }}</span>
                                            <span>⬇️ {{ document.downloads }}</span>
                                            <span v-if="document.rating">⭐ {{ document.rating }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="previewDocument(document)"
                                            class="flex items-center gap-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            <EyeIcon class="w-3 h-3" />
                                            Preview
                                        </button>
                                        <button
                                            @click="downloadDocument(document)"
                                            class="flex items-center gap-1 px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                        >
                                            <ArrowDownTrayIcon class="w-3 h-3" />
                                            Download
                                        </button>
                                    </div>

                                    <button
                                        @click="shareDocument(document)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    >
                                        <ShareIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Document
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Category
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Author
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Modified
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Size
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="document in filteredDocuments"
                                        :key="document.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div :class="`w-10 h-10 rounded-lg flex items-center justify-center mr-4 ${getFileTypeColor(document.fileType)} bg-gray-100 dark:bg-gray-700`">
                                                    <DocumentTextIcon class="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">
                                                        {{ document.title }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                                                        {{ document.description }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getCategoryColor(document.category)}`">
                                                {{ document.category }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ document.uploadedBy }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(document.lastModified) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ document.size }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="previewDocument(document)"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    <EyeIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    @click="downloadDocument(document)"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                >
                                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    @click="toggleBookmark(document.id)"
                                                    :class="`${document.isBookmarked ? 'text-yellow-600' : 'text-gray-400 hover:text-yellow-600'}`"
                                                >
                                                    <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': document.isBookmarked }" />
                                                </button>
                                                <button
                                                    @click="shareDocument(document)"
                                                    class="text-gray-400 hover:text-blue-600"
                                                >
                                                    <ShareIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredDocuments.length === 0" class="text-center py-16">
                        <FolderIcon class="w-24 h-24 text-gray-400 mx-auto mb-6" />
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No documents found</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Try adjusting your search terms or filters to find what you're looking for.
                        </p>
                        <button
                            @click="clearAllFilters"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Clear All Filters
                        </button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="filteredDocuments.length > 0" class="flex items-center justify-center mt-8">
                        <nav class="flex items-center gap-2">
                            <button class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Previous
                            </button>
                            <button class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg">
                                1
                            </button>
                            <button class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                2
                            </button>
                            <button class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                3
                            </button>
                            <button class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Next
                            </button>
                        </nav>
                    </div>
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

/* Custom scrollbar for filters */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Hover animations */
.document-card {
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-4px);
}
</style>
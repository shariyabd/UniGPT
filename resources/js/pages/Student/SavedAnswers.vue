<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    savedAnswers: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const slugify = (s) => (s || 'general').toString().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
import {
    BookmarkIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    TagIcon,
    FolderIcon,
    DocumentArrowDownIcon,
    ShareIcon,
    TrashIcon,
    PencilIcon,
    EyeIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    StarIcon,
    ClockIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    PlusIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ArrowTopRightOnSquareIcon,
    Squares2X2Icon,
    ListBulletIcon,
    CalendarIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

// Component state
const viewMode = ref('grid'); // grid, list
const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedCourse = ref('all');
const selectedTags = ref([]);
const sortBy = ref('date_desc'); // date_desc, date_asc, title_asc, confidence_desc
const showFilters = ref(false);
const selectedItems = ref(new Set());
const showCreateFolder = ref(false);
const newFolderName = ref('');
const editingItem = ref(null);
const showExportModal = ref(false);
const expandedFolders = ref(new Set(['favorites', 'recent']));

// Student context (from shared auth user)
const authUser = computed(() => usePage().props.auth?.user ?? {});
const studentContext = ref({
    name: authUser.value.name ?? 'Student',
    department: authUser.value.department?.name ?? '',
    semester: authUser.value.semester ?? '',
    currentCourses: []
});

// Saved answers from the server, mapped to this page's shape.
const savedAnswers = ref(props.savedAnswers.map((a) => ({
    id: a.id,
    title: a.title,
    question: a.question ?? '',
    answer: a.answer ?? '',
    course: a.category ?? 'General',
    category: a.category ?? 'General',
    tags: a.tags ?? [],
    confidence: a.confidence ?? 0,
    sources: a.sources ?? [],
    savedAt: a.savedAt,
    lastViewed: a.lastViewed ?? a.savedAt,
    viewCount: a.viewCount ?? 0,
    notes: a.notes ?? '',
    folder: slugify(a.folder),
    starred: a.starred ?? false,
    chatId: a.chatId,
})));

// Folders: special (favorites/recent) + server-provided collections.
const folders = ref([
    { id: 'favorites', name: 'Favorites', icon: '⭐', color: 'yellow', count: 0, description: 'Starred important answers' },
    { id: 'recent', name: 'Recently Viewed', icon: '🕒', color: 'blue', count: 0, description: 'Last 10 viewed answers' },
    ...props.folders.map((f) => ({
        id: slugify(f.name),
        name: f.name,
        icon: '📁',
        color: 'purple',
        count: f.count,
        description: '',
    })),
]);

// Filter options
const categories = ref(props.categories.length ? props.categories : ['General']);
const sortOptions = ref([
    { value: 'date_desc', label: 'Newest First' },
    { value: 'date_asc', label: 'Oldest First' },
    { value: 'title_asc', label: 'Title A-Z' },
    { value: 'confidence_desc', label: 'Highest Confidence' },
    { value: 'views_desc', label: 'Most Viewed' }
]);

// Computed properties
const filteredAnswers = computed(() => {
    let filtered = [...savedAnswers.value];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(item =>
            item.title.toLowerCase().includes(query) ||
            item.question.toLowerCase().includes(query) ||
            item.answer.toLowerCase().includes(query) ||
            item.tags.some(tag => tag.toLowerCase().includes(query)) ||
            item.notes.toLowerCase().includes(query)
        );
    }

    // Category filter
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(item => item.category === selectedCategory.value);
    }

    // Course filter
    if (selectedCourse.value !== 'all') {
        filtered = filtered.filter(item => item.course === selectedCourse.value);
    }

    // Tags filter
    if (selectedTags.value.length > 0) {
        filtered = filtered.filter(item =>
            selectedTags.value.every(tag => item.tags.includes(tag))
        );
    }

    // Sorting
    switch (sortBy.value) {
        case 'date_asc':
            filtered.sort((a, b) => new Date(a.savedAt) - new Date(b.savedAt));
            break;
        case 'title_asc':
            filtered.sort((a, b) => a.title.localeCompare(b.title));
            break;
        case 'confidence_desc':
            filtered.sort((a, b) => b.confidence - a.confidence);
            break;
        case 'views_desc':
            filtered.sort((a, b) => b.viewCount - a.viewCount);
            break;
        case 'date_desc':
        default:
            filtered.sort((a, b) => new Date(b.savedAt) - new Date(a.savedAt));
    }

    return filtered;
});

const folderItems = computed(() => {
    const folderMap = {};
    folders.value.forEach(folder => {
        folderMap[folder.id] = {
            ...folder,
            items: []
        };
    });

    // Add special folders
    folderMap.favorites.items = savedAnswers.value.filter(item => item.starred);
    folderMap.recent.items = savedAnswers.value
        .sort((a, b) => new Date(b.lastViewed) - new Date(a.lastViewed))
        .slice(0, 10);

    // Regular folders
    savedAnswers.value.forEach(item => {
        if (item.folder && folderMap[item.folder]) {
            folderMap[item.folder].items.push(item);
        }
    });

    // Update counts
    Object.keys(folderMap).forEach(folderId => {
        folderMap[folderId].count = folderMap[folderId].items.length;
    });

    return folderMap;
});

const allTags = computed(() => {
    const tagSet = new Set();
    savedAnswers.value.forEach(item => {
        item.tags.forEach(tag => tagSet.add(tag));
    });
    return Array.from(tagSet).sort();
});

const selectedItemsCount = computed(() => selectedItems.value.size);

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const formatRelativeTime = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffInDays === 0) return 'Today';
    if (diffInDays === 1) return 'Yesterday';
    if (diffInDays < 7) return `${diffInDays} days ago`;
    if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
    return `${Math.floor(diffInDays / 30)} months ago`;
};

const getConfidenceColor = (confidence) => {
    if (confidence >= 90) return 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-400';
    if (confidence >= 75) return 'text-blue-700 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400';
    if (confidence >= 60) return 'text-yellow-700 bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400';
    return 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-400';
};

const getFolderColor = (color) => {
    const colors = {
        yellow: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
        blue: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
        red: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        green: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        purple: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400',
        indigo: 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400',
        cyan: 'text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30 dark:text-cyan-400',
        pink: 'text-pink-600 bg-pink-100 dark:bg-pink-900/30 dark:text-pink-400'
    };
    return colors[color] || colors.blue;
};

const getSourceIcon = (type) => {
    const icons = {
        textbook: '📚',
        notes: '📝',
        slides: '📊',
        handbook: '📖',
        guide: '📋',
        document: '📄',
        documentation: '📘'
    };
    return icons[type] || '📄';
};

// Actions
const toggleItemSelection = (itemId) => {
    if (selectedItems.value.has(itemId)) {
        selectedItems.value.delete(itemId);
    } else {
        selectedItems.value.add(itemId);
    }
};

const selectAllItems = () => {
    if (selectedItems.value.size === filteredAnswers.value.length) {
        selectedItems.value.clear();
    } else {
        selectedItems.value = new Set(filteredAnswers.value.map(item => item.id));
    }
};

const toggleStar = (itemId) => {
    const item = savedAnswers.value.find(a => a.id === itemId);
    if (!item) return;
    item.starred = !item.starred;
    router.patch(route('saved.update', itemId), { starred: item.starred }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteItem = (itemId) => {
    if (!confirm('Are you sure you want to remove this saved answer?')) return;
    router.delete(route('saved.destroy', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            const index = savedAnswers.value.findIndex(a => a.id === itemId);
            if (index !== -1) savedAnswers.value.splice(index, 1);
            selectedItems.value.delete(itemId);
        },
    });
};

const bulkDelete = () => {
    if (!confirm(`Are you sure you want to remove ${selectedItems.value.size} saved answers?`)) return;
    const ids = [...selectedItems.value];
    ids.forEach((itemId) => {
        router.delete(route('saved.destroy', itemId), { preserveScroll: true, preserveState: true });
    });
    savedAnswers.value = savedAnswers.value.filter((a) => !selectedItems.value.has(a.id));
    selectedItems.value.clear();
};

const bulkMoveToFolder = (folderId) => {
    const target = folders.value.find((f) => f.id === folderId);
    const folderName = target ? target.name : folderId;
    selectedItems.value.forEach(itemId => {
        const item = savedAnswers.value.find(a => a.id === itemId);
        if (item) {
            item.folder = folderId;
            router.patch(route('saved.update', itemId), { folder: folderName }, {
                preserveScroll: true,
                preserveState: true,
            });
        }
    });
    selectedItems.value.clear();
};

const toggleFolder = (folderId) => {
    if (expandedFolders.value.has(folderId)) {
        expandedFolders.value.delete(folderId);
    } else {
        expandedFolders.value.add(folderId);
    }
};

const addTagFilter = (tag) => {
    if (!selectedTags.value.includes(tag)) {
        selectedTags.value.push(tag);
    }
};

const removeTagFilter = (tag) => {
    const index = selectedTags.value.indexOf(tag);
    if (index !== -1) {
        selectedTags.value.splice(index, 1);
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    selectedCourse.value = 'all';
    selectedTags.value = [];
};

const createFolder = () => {
    if (newFolderName.value.trim()) {
        const newFolder = {
            id: newFolderName.value.toLowerCase().replace(/\s+/g, '-'),
            name: newFolderName.value,
            icon: '📁',
            color: 'gray',
            count: 0,
            description: 'Custom folder'
        };
        folders.value.push(newFolder);
        newFolderName.value = '';
        showCreateFolder.value = false;
    }
};

const exportToPDF = () => {
    showExportModal.value = true;
    // Mock PDF export
    setTimeout(() => {
        alert('PDF export feature - would generate comprehensive study notes with citations');
        showExportModal.value = false;
    }, 2000);
};

const goToOriginalChat = (chatId, messageId) => {
    // Navigate to the original chat message
    window.open(`/chat?chatId=${chatId}&messageId=${messageId}`, '_blank');
};

const editNotes = (itemId) => {
    editingItem.value = itemId;
};

const saveNotes = (itemId, notes) => {
    const item = savedAnswers.value.find(a => a.id === itemId);
    if (item) {
        item.notes = notes;
    }
    editingItem.value = null;
};

// Lifecycle
onMounted(() => {
    // Update view count for demo
    savedAnswers.value.forEach((item, index) => {
        if (index < 3) {
            item.lastViewed = new Date().toISOString();
        }
    });
});
</script>

<template>
    <div>
        <Head title="Saved Answers & Bookmarks" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-yellow-500 via-orange-500 to-red-500 dark:from-yellow-800 dark:via-orange-800 dark:to-red-800">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                                    <BookmarkIcon class="w-10 h-10" />
                                    Saved Answers & Bookmarks
                                </h1>
                                <p class="text-xl text-white/90 mb-2">
                                    Your personal knowledge library organized for success
                                </p>
                                <p class="text-white/80">
                                    {{ filteredAnswers.length }} saved answers • Organized in {{ folders.length }} collections
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Student Context -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white">
                                    <div class="text-sm font-medium">{{ studentContext.name }}</div>
                                    <div class="text-xs text-white/80">{{ studentContext.department }}</div>
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
                        <!-- Left Sidebar - Folders & Organization -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-8">
                                <!-- Folders Header -->
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <FolderIcon class="w-5 h-5 text-blue-600" />
                                        Collections
                                    </h3>
                                    <button
                                        @click="showCreateFolder = true"
                                        class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                        title="Create Folder"
                                    >
                                        <PlusIcon class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Create Folder Form -->
                                <div v-if="showCreateFolder" class="mb-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <div class="flex gap-2">
                                        <input
                                            v-model="newFolderName"
                                            type="text"
                                            placeholder="Folder name"
                                            class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            @keydown.enter="createFolder"
                                        />
                                        <button
                                            @click="createFolder"
                                            class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                        >
                                            <CheckIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="showCreateFolder = false"
                                            class="p-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                                        >
                                            <XMarkIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Folders List -->
                                <div class="space-y-2">
                                    <div
                                        v-for="folder in folders"
                                        :key="folder.id"
                                        class="group"
                                    >
                                        <button
                                            @click="toggleFolder(folder.id)"
                                            class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <ChevronRightIcon
                                                :class="`w-4 h-4 text-gray-400 transition-transform ${expandedFolders.has(folder.id) ? 'rotate-90' : ''}`"
                                            />
                                            <span class="text-lg">{{ folder.icon }}</span>
                                            <div class="flex-1 text-left">
                                                <div class="font-medium text-gray-900 dark:text-white text-sm">{{ folder.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ folder.count }} items</div>
                                            </div>
                                        </button>

                                        <!-- Folder Items -->
                                        <div v-if="expandedFolders.has(folder.id)" class="ml-6 mt-2 space-y-1">
                                            <div
                                                v-for="item in folderItems[folder.id]?.items.slice(0, 3)"
                                                :key="item.id"
                                                class="flex items-center gap-2 p-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer"
                                            >
                                                <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></span>
                                                <span class="truncate">{{ item.title }}</span>
                                            </div>
                                            <div v-if="folderItems[folder.id]?.items.length > 3" class="p-2 text-xs text-gray-500 dark:text-gray-400">
                                                +{{ folderItems[folder.id].items.length - 3 }} more...
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Course Filter -->
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">Filter by Course</h4>
                                    <select
                                        v-model="selectedCourse"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="all">All Courses</option>
                                        <option v-for="course in studentContext.currentCourses" :key="course" :value="course">
                                            {{ course }}
                                        </option>
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
                                            placeholder="Search saved answers, questions, or tags..."
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

                                    <!-- Export Button -->
                                    <button
                                        @click="exportToPDF"
                                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors"
                                    >
                                        <DocumentArrowDownIcon class="w-5 h-5" />
                                        Export PDF
                                    </button>
                                </div>

                                <!-- Expanded Filters -->
                                <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Category Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                            <select
                                                v-model="selectedCategory"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="all">All Categories</option>
                                                <option v-for="category in categories" :key="category" :value="category">
                                                    {{ category }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Sort By -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                                            <select
                                                v-model="sortBy"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Clear Filters -->
                                        <div class="flex items-end">
                                            <button
                                                @click="clearFilters"
                                                class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm"
                                            >
                                                Clear Filters
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tag Filters -->
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="tag in allTags.slice(0, 12)"
                                                :key="tag"
                                                @click="addTagFilter(tag)"
                                                :class="`px-3 py-1 text-xs rounded-full border transition-colors ${
                                                    selectedTags.includes(tag)
                                                        ? 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-600'
                                                        : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'
                                                }`"
                                            >
                                                #{{ tag }}
                                            </button>
                                        </div>

                                        <!-- Selected Tags -->
                                        <div v-if="selectedTags.length > 0" class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                v-for="tag in selectedTags"
                                                :key="tag"
                                                class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white text-xs rounded-full"
                                            >
                                                #{{ tag }}
                                                <button @click="removeTagFilter(tag)" class="hover:bg-blue-700 rounded-full p-0.5">
                                                    <XMarkIcon class="w-3 h-3" />
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bulk Actions -->
                                <div v-if="selectedItemsCount > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ selectedItemsCount }} items selected
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="bulkMoveToFolder('exam-prep')"
                                                class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700"
                                            >
                                                Move to Exam Prep
                                            </button>
                                            <button
                                                @click="bulkDelete"
                                                class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700"
                                            >
                                                Delete Selected
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Results Counter -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Showing {{ filteredAnswers.length }} of {{ savedAnswers.length }} saved answers
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="selectAllItems"
                                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            {{ selectedItemsCount === filteredAnswers.length ? 'Deselect All' : 'Select All' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Saved Answers Grid/List -->
                            <div v-if="filteredAnswers.length > 0" :class="`space-y-6 ${viewMode === 'grid' ? 'grid grid-cols-1 lg:grid-cols-2 gap-6 space-y-0' : ''}`">
                                <div
                                    v-for="answer in filteredAnswers"
                                    :key="answer.id"
                                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                >
                                    <!-- Answer Header -->
                                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-start gap-4">
                                            <!-- Selection Checkbox -->
                                            <input
                                                type="checkbox"
                                                :checked="selectedItems.has(answer.id)"
                                                @change="toggleItemSelection(answer.id)"
                                                class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />

                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-4 mb-3">
                                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-2">
                                                        {{ answer.title }}
                                                    </h3>
                                                    <button
                                                        @click="toggleStar(answer.id)"
                                                        :class="`p-2 rounded-lg transition-colors ${
                                                            answer.starred
                                                                ? 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                                : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                                                        }`"
                                                    >
                                                        <StarIcon class="w-5 h-5" :class="{ 'fill-current': answer.starred }" />
                                                    </button>
                                                </div>

                                                <!-- Question -->
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 italic">
                                                    "{{ answer.question }}"
                                                </p>

                                                <!-- Metadata -->
                                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-lg">
                                                        {{ answer.course }}
                                                    </span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-lg">
                                                        {{ answer.category }}
                                                    </span>
                                                    <span :class="`text-xs font-medium px-2 py-1 rounded-full border ${getConfidenceColor(answer.confidence)}`">
                                                        {{ answer.confidence }}% confident
                                                    </span>
                                                </div>

                                                <!-- Tags -->
                                                <div class="flex flex-wrap gap-1 mb-4">
                                                    <span
                                                        v-for="tag in answer.tags.slice(0, 4)"
                                                        :key="tag"
                                                        class="px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs rounded-full"
                                                    >
                                                        #{{ tag }}
                                                    </span>
                                                    <span v-if="answer.tags.length > 4" class="text-xs text-gray-500 dark:text-gray-400 px-2 py-1">
                                                        +{{ answer.tags.length - 4 }} more
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Answer Preview -->
                                    <div class="p-6">
                                        <div class="prose prose-sm dark:prose-invert max-w-none mb-4">
                                            <p class="text-gray-700 dark:text-gray-300 line-clamp-3">
                                                {{ answer.answer }}
                                            </p>
                                        </div>

                                        <!-- Sources Preview -->
                                        <div v-if="answer.sources && answer.sources.length > 0" class="mb-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1">
                                                <DocumentTextIcon class="w-3 h-3" />
                                                Sources ({{ answer.sources.length }})
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="source in answer.sources.slice(0, 2)"
                                                    :key="source.title"
                                                    class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400"
                                                >
                                                    <span>{{ getSourceIcon(source.type) }}</span>
                                                    {{ source.title }} (p.{{ source.page }})
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div v-if="answer.notes || editingItem === answer.id" class="mb-4">
                                            <div v-if="editingItem !== answer.id" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded-r-xl">
                                                <p class="text-sm text-yellow-800 dark:text-yellow-200 italic">
                                                    📝 {{ answer.notes }}
                                                </p>
                                            </div>
                                            <div v-else class="space-y-2">
                                                <textarea
                                                    :value="answer.notes"
                                                    @blur="saveNotes(answer.id, $event.target.value)"
                                                    class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                                    rows="2"
                                                    placeholder="Add your notes..."
                                                ></textarea>
                                            </div>
                                        </div>

                                        <!-- Metadata Footer -->
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                                            <div class="flex items-center gap-4">
                                                <span class="flex items-center gap-1">
                                                    <ClockIcon class="w-3 h-3" />
                                                    Saved {{ formatRelativeTime(answer.savedAt) }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <EyeIcon class="w-3 h-3" />
                                                    Viewed {{ answer.viewCount }} times
                                                </span>
                                            </div>
                                            <span>
                                                Last viewed {{ formatRelativeTime(answer.lastViewed) }}
                                            </span>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="goToOriginalChat(answer.chatId, answer.messageId)"
                                                    class="flex items-center gap-1 px-3 py-1.5 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                >
                                                    <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                                    View in Chat
                                                </button>
                                                <button
                                                    @click="editNotes(answer.id)"
                                                    class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                >
                                                    <PencilIcon class="w-4 h-4" />
                                                    {{ answer.notes ? 'Edit Notes' : 'Add Notes' }}
                                                </button>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                    title="Share"
                                                >
                                                    <ShareIcon class="w-4 h-4" />
                                                </button>
                                                <button
                                                    @click="deleteItem(answer.id)"
                                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                    title="Delete"
                                                >
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-16">
                                <div class="w-24 h-24 bg-gradient-to-br from-yellow-100 to-orange-100 dark:from-yellow-900/30 dark:to-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <BookmarkIcon class="w-12 h-12 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No saved answers found</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                    {{ searchQuery || selectedCategory !== 'all' || selectedCourse !== 'all' || selectedTags.length > 0
                                        ? 'Try adjusting your filters or search terms.'
                                        : 'Start saving helpful AI responses from your chats to build your personal knowledge library.' }}
                                </p>
                                <div class="flex justify-center gap-4">
                                    <button
                                        v-if="searchQuery || selectedCategory !== 'all' || selectedCourse !== 'all' || selectedTags.length > 0"
                                        @click="clearFilters"
                                        class="px-6 py-3 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition-colors"
                                    >
                                        Clear Filters
                                    </button>
                                    <Link
                                        href="/chat"
                                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:shadow-lg transition-all"
                                    >
                                        Start Chatting
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Modal -->
                <div v-if="showExportModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

                        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <DocumentArrowDownIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    Generating Study Notes
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">
                                    Creating comprehensive PDF with citations and sources...
                                </p>
                                <div class="animate-spin rounded-full h-8 w-8 border-2 border-green-600 border-t-transparent mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Enhanced scrollbar styling */
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

/* Line clamp utilities */
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

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover effects */
.hover\:-translate-y-1:hover {
    transform: translateY(-0.25rem);
}

/* Enhanced prose styling */
.prose p {
    color: inherit;
}

/* Custom animations */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
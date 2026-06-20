<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
    savedAnswers: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const toast = useToast();
const { confirm } = useConfirm();

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
const selectedTags = ref([]);
const sortBy = ref('date_desc'); // date_desc, date_asc, title_asc, confidence_desc
const showFilters = ref(false);
const selectedItems = ref(new Set());
const showCreateFolder = ref(false);
const newFolderName = ref('');
const editingItem = ref(null);
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
    sessionId: a.sessionId ?? null,
    messageId: a.messageId ?? null,
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

    // Add special folders (copy before sorting so the source order isn't mutated)
    folderMap.favorites.items = savedAnswers.value.filter(item => item.starred);
    folderMap.recent.items = [...savedAnswers.value]
        .filter(item => item.lastViewed)
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

// Real, user-defined collections (excludes the special Favorites/Recent views).
const movableFolders = computed(() => folders.value.filter((f) => f.id !== 'favorites' && f.id !== 'recent'));

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

const getConfidenceVariant = (confidence) => {
    if (confidence >= 90) return 'success';
    if (confidence >= 75) return 'info';
    if (confidence >= 60) return 'warning';
    return 'danger';
};

const getFolderColor = (color) => {
    const colors = {
        yellow: 'text-warning-fg bg-warning-bg',
        blue: 'text-primary bg-primary-soft',
        red: 'text-danger-fg bg-danger-bg',
        green: 'text-success-fg bg-success-bg',
        purple: 'text-primary bg-primary-soft',
        indigo: 'text-primary bg-primary-soft',
        cyan: 'text-primary bg-primary-soft',
        pink: 'text-warning-fg bg-warning-bg'
    };
    return colors[color] || colors.blue;
};

const getSourceIcon = (type) => {
    return DocumentTextIcon;
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

const deleteItem = async (itemId) => {
    const ok = await confirm({
        title: 'Remove saved answer',
        message: 'Are you sure you want to remove this saved answer?',
        confirmLabel: 'Remove',
        variant: 'danger',
    });
    if (!ok) return;
    router.delete(route('saved.destroy', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            const index = savedAnswers.value.findIndex(a => a.id === itemId);
            if (index !== -1) savedAnswers.value.splice(index, 1);
            selectedItems.value.delete(itemId);
        },
    });
};

const bulkDelete = async () => {
    const ok = await confirm({
        title: 'Remove saved answers',
        message: `Are you sure you want to remove ${selectedItems.value.size} saved answers?`,
        confirmLabel: 'Remove',
        variant: 'danger',
    });
    if (!ok) return;
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

// Build a printable HTML document of the (filtered) answers and hand it to the
// browser's print dialog, where the user can save it as a real PDF.
const exportToPDF = () => {
    const answers = filteredAnswers.value;
    if (answers.length === 0) {
        toast.info('Nothing to export with the current filters.');
        return;
    }

    const esc = (s) => String(s ?? '').replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
    const body = answers.map((a) => `
        <section style="margin:0 0 28px;page-break-inside:avoid;">
            <h2 style="font-size:16px;margin:0 0 4px;">${esc(a.title)}</h2>
            <div style="color:#666;font-size:12px;margin-bottom:8px;">${esc(a.category)} • ${a.confidence}% confidence • saved ${esc(formatDate(a.savedAt))}</div>
            ${a.question ? `<p style="font-style:italic;color:#444;">Q: ${esc(a.question)}</p>` : ''}
            <div style="white-space:pre-wrap;line-height:1.5;">${esc(a.answer)}</div>
            ${a.notes ? `<p style="margin-top:8px;border-left:3px solid #f59e0b;padding-left:8px;color:#92400e;">Notes: ${esc(a.notes)}</p>` : ''}
        </section>`).join('');

    const html = `<!doctype html><html><head><meta charset="utf-8"><title>UniGPT — Saved Answers</title></head>
        <body style="font-family:Inter,Arial,sans-serif;max-width:760px;margin:24px auto;padding:0 16px;color:#111;">
            <h1 style="font-size:20px;">UniGPT — Saved Answers (${answers.length})</h1>
            ${body}
        </body></html>`;

    const win = window.open('', '_blank');
    if (!win) {
        toast.error('Please allow pop-ups to export.');
        return;
    }
    win.document.write(html);
    win.document.close();
    win.focus();
    win.onload = () => win.print();
    // Fallback if onload doesn't fire (already-loaded blank document).
    setTimeout(() => { try { win.print(); } catch (e) { /* ignore */ } }, 400);
};

const goToOriginalChat = (item) => {
    if (!item.sessionId) {
        toast.info('The original conversation is no longer available.');
        return;
    }

    // Record the view (count + recency), then open the conversation deep-linked
    // to the saved message.
    axios.post(route('saved.view', item.id)).then(({ data }) => {
        item.viewCount = data.viewCount;
        item.lastViewed = data.lastViewed;
    }).catch(() => { /* non-critical */ });

    window.open(`/chat?session=${item.sessionId}&message=${item.messageId}`, '_blank');
};

const editNotes = (itemId) => {
    editingItem.value = itemId;
};

const saveNotes = (itemId, notes) => {
    const item = savedAnswers.value.find(a => a.id === itemId);
    if (item) item.notes = notes;
    editingItem.value = null;

    router.patch(route('saved.update', itemId), { notes }, {
        preserveScroll: true,
        preserveState: true,
        // Success toast comes from the server flash (centralized pipeline).
        onError: () => toast.error('Could not save notes.'),
    });
};

const shareAnswer = (item) => {
    const text = `${item.title}\n\n${item.question ? 'Q: ' + item.question + '\n\n' : ''}${item.answer}`;
    navigator.clipboard.writeText(text).then(() => toast.success('Answer copied to clipboard.'));
};
</script>

<template>
    <div>
        <Head title="Saved Answers & Bookmarks" />

        <AppLayout>
            <div class="page-container space-y-6 py-8 sm:space-y-8">
                <!-- Page Header -->
                <PageHeader
                    title="Saved Answers"
                    subtitle="Your personal knowledge library, organized for success"
                    :icon="BookmarkIcon"
                >
                    <template #actions>
                        <button
                            type="button"
                            @click="exportToPDF"
                            class="ui-btn-secondary"
                        >
                            <DocumentArrowDownIcon class="h-5 w-5" />
                            Export PDF
                        </button>
                        <Link href="/chat" class="ui-btn-primary">
                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                            Start Chatting
                        </Link>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <!-- Left Sidebar - Folders & Organization -->
                    <div class="lg:col-span-1">
                        <div class="lg:sticky lg:top-8 space-y-6">
                            <!-- Collections -->
                            <Card padding="p-5 sm:p-6">
                                <template #header>
                                    <div class="flex items-center gap-2.5">
                                        <span class="ui-icon-tile bg-primary-soft text-primary">
                                            <FolderIcon class="h-4 w-4" />
                                        </span>
                                        <h2 class="ui-card-title">Collections</h2>
                                    </div>
                                </template>
                                <template #actions>
                                    <button
                                        @click="showCreateFolder = true"
                                        class="ui-btn-ghost p-1.5"
                                        aria-label="Create folder"
                                        title="Create Folder"
                                    >
                                        <PlusIcon class="h-4 w-4" />
                                    </button>
                                </template>

                                <!-- Create Folder Form -->
                                <div v-if="showCreateFolder" class="mb-4 rounded-control bg-neutral-bg p-3">
                                    <div class="flex gap-2">
                                        <input
                                            v-model="newFolderName"
                                            type="text"
                                            placeholder="Folder name"
                                            class="ui-input flex-1"
                                            @keydown.enter="createFolder"
                                        />
                                        <button
                                            @click="createFolder"
                                            class="inline-flex items-center justify-center rounded-control bg-primary p-2 text-white transition-colors hover:bg-primary-hover"
                                            aria-label="Confirm new folder"
                                        >
                                            <CheckIcon class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="showCreateFolder = false"
                                            class="inline-flex items-center justify-center rounded-control bg-neutral-bg p-2 text-content-muted transition-colors hover:bg-line"
                                            aria-label="Cancel new folder"
                                        >
                                            <XMarkIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Folders List -->
                                <div class="space-y-1.5">
                                    <div
                                        v-for="folder in folders"
                                        :key="folder.id"
                                        class="group"
                                    >
                                        <button
                                            @click="toggleFolder(folder.id)"
                                            class="flex w-full items-center gap-3 rounded-control p-3 text-left transition-colors hover:bg-primary-soft"
                                        >
                                            <ChevronRightIcon
                                                :class="`h-4 w-4 flex-shrink-0 text-content-faint transition-transform ${expandedFolders.has(folder.id) ? 'rotate-90' : ''}`"
                                            />
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg"
                                                :class="getFolderColor(folder.color)"
                                            >
                                                <StarIcon v-if="folder.id === 'favorites'" class="h-4 w-4" />
                                                <ClockIcon v-else-if="folder.id === 'recent'" class="h-4 w-4" />
                                                <FolderIcon v-else class="h-4 w-4" />
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-medium text-content">{{ folder.name }}</div>
                                                <div class="text-xs text-content-muted">{{ folderItems[folder.id] ? folderItems[folder.id].count : 0 }} items</div>
                                            </div>
                                        </button>

                                        <!-- Folder Items -->
                                        <div v-if="expandedFolders.has(folder.id)" class="ml-6 mt-1 space-y-1">
                                            <div
                                                v-for="item in folderItems[folder.id]?.items.slice(0, 3)"
                                                :key="item.id"
                                                class="flex cursor-pointer items-center gap-2 rounded-control p-2 text-xs text-content-muted transition-colors hover:bg-primary-soft"
                                            >
                                                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-content-faint"></span>
                                                <span class="truncate">{{ item.title }}</span>
                                            </div>
                                            <div v-if="folderItems[folder.id]?.items.length > 3" class="p-2 text-xs text-content-muted">
                                                +{{ folderItems[folder.id].items.length - 3 }} more...
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </Card>
                        </div>
                    </div>

                    <!-- Main Content Area -->
                    <div class="space-y-6 lg:col-span-3">
                        <!-- Search & Controls -->
                        <Card padding="p-5 sm:p-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                <!-- Search -->
                                <div class="relative flex-1">
                                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search saved answers, questions, or tags..."
                                        class="ui-input pl-10"
                                    />
                                </div>

                                <!-- View Mode Toggle -->
                                <div class="flex flex-shrink-0 rounded-control bg-neutral-bg p-1">
                                    <button
                                        @click="viewMode = 'grid'"
                                        :class="`rounded-control p-2 transition-colors ${viewMode === 'grid' ? 'bg-surface text-content shadow-sm' : 'text-content-muted'}`"
                                        aria-label="Grid view"
                                    >
                                        <Squares2X2Icon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        :class="`rounded-control p-2 transition-colors ${viewMode === 'list' ? 'bg-surface text-content shadow-sm' : 'text-content-muted'}`"
                                        aria-label="List view"
                                    >
                                        <ListBulletIcon class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Filters Toggle -->
                                <button
                                    @click="showFilters = !showFilters"
                                    :class="`inline-flex flex-shrink-0 items-center gap-2 rounded-control px-4 py-2.5 text-sm font-medium transition-colors ${showFilters ? 'bg-primary text-white' : 'bg-neutral-bg text-content-muted'}`"
                                >
                                    <FunnelIcon class="h-5 w-5" />
                                    Filters
                                </button>
                            </div>

                            <!-- Expanded Filters -->
                            <div v-if="showFilters" class="mt-4 border-t border-line pt-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <!-- Category Filter -->
                                    <div>
                                        <label class="ui-label mb-2">Category</label>
                                        <select v-model="selectedCategory" class="ui-input">
                                            <option value="all">All Categories</option>
                                            <option v-for="category in categories" :key="category" :value="category">
                                                {{ category }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Sort By -->
                                    <div>
                                        <label class="ui-label mb-2">Sort By</label>
                                        <select v-model="sortBy" class="ui-input">
                                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Clear Filters -->
                                    <div class="flex items-end">
                                        <button @click="clearFilters" class="ui-btn-secondary w-full justify-center">
                                            Clear Filters
                                        </button>
                                    </div>
                                </div>

                                <!-- Tag Filters -->
                                <div class="mt-4">
                                    <label class="ui-label mb-2 flex items-center gap-1.5">
                                        <TagIcon class="h-4 w-4" />
                                        Tags
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="tag in allTags.slice(0, 12)"
                                            :key="tag"
                                            @click="addTagFilter(tag)"
                                            :class="`rounded-pill border px-3 py-1 text-xs font-medium transition-colors ${
                                                selectedTags.includes(tag)
                                                    ? 'border-transparent bg-primary text-white'
                                                    : 'border-line bg-neutral-bg text-content-muted hover:bg-primary-soft hover:text-primary'
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
                                            class="inline-flex items-center gap-1 rounded-pill bg-primary px-3 py-1 text-xs font-medium text-white"
                                        >
                                            #{{ tag }}
                                            <button @click="removeTagFilter(tag)" class="rounded-full p-0.5 hover:bg-white/20" aria-label="Remove tag filter">
                                                <XMarkIcon class="h-3 w-3" />
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bulk Actions -->
                            <div v-if="selectedItemsCount > 0" class="mt-4 border-t border-line pt-4">
                                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                                    <span class="text-sm text-content-muted">
                                        {{ selectedItemsCount }} items selected
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <select
                                            :value="''"
                                            @change="bulkMoveToFolder($event.target.value); $event.target.value = ''"
                                            class="ui-input w-auto text-sm"
                                            aria-label="Move selected to folder"
                                        >
                                            <option value="" disabled>Move to folder…</option>
                                            <option v-for="folder in movableFolders" :key="folder.id" :value="folder.id">
                                                {{ folder.name }}
                                            </option>
                                        </select>
                                        <button @click="bulkDelete" class="ui-btn-danger text-sm">
                                            <TrashIcon class="h-4 w-4" />
                                            Delete Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Results Counter -->
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-content-muted">
                                Showing {{ filteredAnswers.length }} of {{ savedAnswers.length }} saved answers
                            </p>
                            <button
                                @click="selectAllItems"
                                class="text-sm font-semibold text-content-muted transition-colors hover:text-content"
                            >
                                {{ selectedItemsCount === filteredAnswers.length ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>

                        <!-- Saved Answers Grid/List -->
                        <div v-if="filteredAnswers.length > 0" :class="`space-y-6 ${viewMode === 'grid' ? 'grid grid-cols-1 gap-6 space-y-0 xl:grid-cols-2' : ''}`">
                            <Card
                                v-for="answer in filteredAnswers"
                                :key="answer.id"
                                hover
                                padding="p-0"
                                class="overflow-hidden"
                            >
                                <!-- Answer Header -->
                                <div class="border-b border-line p-5 sm:p-6">
                                    <div class="flex items-start gap-4">
                                        <!-- Selection Checkbox -->
                                        <input
                                            type="checkbox"
                                            :checked="selectedItems.has(answer.id)"
                                            @change="toggleItemSelection(answer.id)"
                                            class="mt-1 rounded border-line text-primary focus:ring-primary/20"
                                            aria-label="Select answer"
                                        />

                                        <!-- Content -->
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-3 flex items-start justify-between gap-4">
                                                <h3 class="line-clamp-2 text-base font-bold text-content">
                                                    {{ answer.title }}
                                                </h3>
                                                <button
                                                    @click="toggleStar(answer.id)"
                                                    :class="`flex-shrink-0 rounded-control p-2 transition-colors ${
                                                        answer.starred
                                                            ? 'bg-warning-bg text-warning-fg'
                                                            : 'text-content-faint hover:bg-warning-bg hover:text-warning-fg'
                                                    }`"
                                                    :aria-label="answer.starred ? 'Unstar answer' : 'Star answer'"
                                                >
                                                    <StarIcon class="h-5 w-5" :class="{ 'fill-current': answer.starred }" />
                                                </button>
                                            </div>

                                            <!-- Question -->
                                            <p class="mb-4 text-sm italic text-content-muted">
                                                "{{ answer.question }}"
                                            </p>

                                            <!-- Metadata -->
                                            <div class="mb-4 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center rounded-pill bg-primary-soft px-2.5 py-1 text-xs font-medium text-primary">{{ answer.category }}</span>
                                                <Badge :variant="getConfidenceVariant(answer.confidence)">
                                                    {{ answer.confidence }}% confident
                                                </Badge>
                                            </div>

                                            <!-- Tags -->
                                            <div class="flex flex-wrap gap-1.5">
                                                <span
                                                    v-for="tag in answer.tags.slice(0, 4)"
                                                    :key="tag"
                                                    class="rounded-pill bg-neutral-bg px-2 py-1 text-xs text-content-muted"
                                                >
                                                    #{{ tag }}
                                                </span>
                                                <span v-if="answer.tags.length > 4" class="px-2 py-1 text-xs text-content-muted">
                                                    +{{ answer.tags.length - 4 }} more
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Answer Preview -->
                                <div class="p-5 sm:p-6">
                                    <p class="mb-4 line-clamp-3 text-sm text-content">
                                        {{ answer.answer }}
                                    </p>

                                    <!-- Sources Preview -->
                                    <div v-if="answer.sources && answer.sources.length > 0" class="mb-4 rounded-control bg-neutral-bg p-3">
                                        <p class="mb-2 flex items-center gap-1.5 text-xs font-medium text-content">
                                            <DocumentTextIcon class="h-3.5 w-3.5" />
                                            Sources ({{ answer.sources.length }})
                                        </p>
                                        <div class="flex flex-wrap gap-x-3 gap-y-1.5">
                                            <span
                                                v-for="source in answer.sources.slice(0, 2)"
                                                :key="source.title"
                                                class="inline-flex items-center gap-1 text-xs text-content-muted"
                                            >
                                                <component :is="getSourceIcon(source.type)" class="h-3.5 w-3.5" />
                                                {{ source.title }} (p.{{ source.page }})
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div v-if="answer.notes || editingItem === answer.id" class="mb-4">
                                        <div v-if="editingItem !== answer.id" class="flex items-start gap-2 rounded-r-control border-l-4 border-warning-fg bg-warning-bg p-3">
                                            <PencilIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-warning-fg" />
                                            <p class="text-sm italic text-warning-fg">
                                                {{ answer.notes }}
                                            </p>
                                        </div>
                                        <div v-else>
                                            <textarea
                                                :value="answer.notes"
                                                @blur="saveNotes(answer.id, $event.target.value)"
                                                class="ui-input resize-none"
                                                rows="2"
                                                placeholder="Add your notes..."
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Metadata Footer -->
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 text-xs text-content-muted">
                                        <div class="flex items-center gap-4">
                                            <span class="flex items-center gap-1">
                                                <ClockIcon class="h-3.5 w-3.5" />
                                                Saved {{ formatRelativeTime(answer.savedAt) }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <EyeIcon class="h-3.5 w-3.5" />
                                                Viewed {{ answer.viewCount }} times
                                            </span>
                                        </div>
                                        <span>
                                            Last viewed {{ formatRelativeTime(answer.lastViewed) }}
                                        </span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between border-t border-line pt-4">
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="goToOriginalChat(answer)"
                                                :disabled="!answer.sessionId"
                                                :title="answer.sessionId ? 'Open the original conversation' : 'Original conversation no longer available'"
                                                class="inline-flex items-center gap-1.5 rounded-control px-3 py-1.5 text-sm font-semibold text-content-muted transition-colors hover:bg-primary-soft hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-content-muted"
                                            >
                                                <ChatBubbleLeftRightIcon class="h-4 w-4" />
                                                View in Chat
                                            </button>
                                            <button
                                                @click="editNotes(answer.id)"
                                                class="inline-flex items-center gap-1.5 rounded-control px-3 py-1.5 text-sm font-medium text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                                            >
                                                <PencilIcon class="h-4 w-4" />
                                                {{ answer.notes ? 'Edit Notes' : 'Add Notes' }}
                                            </button>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="shareAnswer(answer)"
                                                class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-primary-soft hover:text-primary"
                                                aria-label="Copy answer"
                                                title="Copy answer to clipboard"
                                            >
                                                <ShareIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="deleteItem(answer.id)"
                                                class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                                aria-label="Delete"
                                                title="Delete"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        </div>

                        <!-- Empty State -->
                        <Card v-else padding="p-0">
                            <EmptyState
                                title="No saved answers found"
                                :icon="BookmarkIcon"
                                :description="searchQuery || selectedCategory !== 'all' || selectedTags.length > 0
                                    ? 'Try adjusting your filters or search terms.'
                                    : 'Start saving helpful AI responses from your chats to build your personal knowledge library.'"
                            >
                                <div class="flex flex-wrap justify-center gap-3">
                                    <button
                                        v-if="searchQuery || selectedCategory !== 'all' || selectedTags.length > 0"
                                        @click="clearFilters"
                                        class="ui-btn-secondary"
                                    >
                                        Clear Filters
                                    </button>
                                    <Link href="/chat" class="ui-btn-primary">
                                        <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                        Start Chatting
                                    </Link>
                                </div>
                            </EmptyState>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
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
</style>

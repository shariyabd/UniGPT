<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CloudArrowUpIcon,
    DocumentTextIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    FolderOpenIcon,
    TagIcon,
    UserIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    departments: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    recentUploads: { type: Object, default: () => ({ data: [] }) },
});

// Upload state management
const dragActive = ref(false);
const uploadedFiles = ref([]);
const isUploading = ref(false);

// Form data
const uploadForm = ref({
    department_id: '',
    category: '',
    description: '',
    tags: [],
    visibility: 'students'
});

const departments = computed(() => props.departments);
const categories = computed(() => props.categories);

// Recent uploads from the server, normalized to the shape the sidebar renders.
const recentUploads = computed(() => {
    const rows = props.recentUploads?.data ?? props.recentUploads ?? [];
    return rows.map((d) => ({
        id: d.id,
        name: d.title,
        size: d.fileSize,
        uploadedAt: d.uploadedAt,
        status: d.status,
    }));
});

// File handling functions
const handleDrop = (e) => {
    dragActive.value = false;
    const files = Array.from(e.dataTransfer.files);
    processFiles(files);
};

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    processFiles(files);
};

const processFiles = (files) => {
    files.forEach(file => {
        if (validateFile(file)) {
            uploadedFiles.value.push({
                id: Date.now() + Math.random(),
                file: file,
                name: file.name,
                size: formatFileSize(file.size),
                type: getFileType(file.name),
                status: 'ready',
                progress: 0
            });
        }
    });
};

const validateFile = (file) => {
    const allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx'];
    const maxSize = 50 * 1024 * 1024; // 50MB

    const fileExtension = file.name.split('.').pop().toLowerCase();

    if (!allowedTypes.includes(fileExtension)) {
        alert('File type not allowed. Please upload PDF, DOC, DOCX, TXT, PPT, or PPTX files.');
        return false;
    }

    if (file.size > maxSize) {
        alert('File size too large. Maximum size is 50MB.');
        return false;
    }

    return true;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileType = (filename) => {
    return filename.split('.').pop().toUpperCase();
};

const removeFile = (fileId) => {
    uploadedFiles.value = uploadedFiles.value.filter(f => f.id !== fileId);
};

const priorities = [
    { value: 'low', label: 'Low Priority', color: 'blue' },
    { value: 'normal', label: 'Normal', color: 'green' },
    { value: 'high', label: 'High Priority', color: 'orange' },
    { value: 'urgent', label: 'Urgent', color: 'red' }
];

const startUpload = async () => {
    if (uploadedFiles.value.length === 0 || !uploadForm.value.category) {
        return;
    }

    isUploading.value = true;

    // Upload each selected file as its own document (one file per request).
    for (const item of uploadedFiles.value) {
        await new Promise((resolve) => {
            router.post(route('admin.documents.store'), {
                title: item.name.replace(/\.[^.]+$/, ''),
                description: uploadForm.value.description,
                department_id: uploadForm.value.department_id || null,
                category: uploadForm.value.category,
                visibility: uploadForm.value.visibility,
                tags: uploadForm.value.tags,
                file: item.file,
            }, {
                forceFormData: true,
                preserveScroll: true,
                onProgress: (event) => {
                    item.status = 'uploading';
                    item.progress = event?.percentage ?? 0;
                },
                onSuccess: () => { item.status = 'completed'; },
                onError: () => { item.status = 'error'; },
                onFinish: () => resolve(),
            });
        });
    }

    isUploading.value = false;
    uploadedFiles.value = [];
    uploadForm.value = {
        department_id: '',
        category: '',
        description: '',
        tags: [],
        visibility: 'students'
    };
};

const addTag = (tag) => {
    if (tag && !uploadForm.value.tags.includes(tag)) {
        uploadForm.value.tags.push(tag);
    }
};

const removeTag = (tagToRemove) => {
    uploadForm.value.tags = uploadForm.value.tags.filter(tag => tag !== tagToRemove);
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        normal: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[priority] || colors.normal;
};

const getStatusColor = (status) => {
    const colors = {
        approved: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        pending: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
        rejected: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[status] || colors.pending;
};
</script>

<template>
    <div>
        <Head title="Document Upload" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    📤 Document Upload
                                </h1>
                                <p class="text-xl text-white/90">
                                    Upload course materials, policies, and academic documents
                                </p>
                            </div>
                            <Link
                                href="/admin/dashboard"
                                class="bg-white/20 backdrop-blur-lg text-white px-6 py-3 rounded-xl font-medium hover:bg-white/30 transition-all"
                            >
                                ← Back to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Upload Section -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Drag & Drop Area -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <CloudArrowUpIcon class="w-7 h-7 text-blue-600" />
                                    Upload Files
                                </h2>

                                <div
                                    @drop.prevent="handleDrop"
                                    @dragover.prevent="dragActive = true"
                                    @dragenter.prevent="dragActive = true"
                                    @dragleave.prevent="dragActive = false"
                                    :class="`relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-200 ${
                                        dragActive
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 scale-105'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'
                                    }`"
                                >
                                    <div class="space-y-4">
                                        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center">
                                            <CloudArrowUpIcon class="w-8 h-8 text-white" />
                                        </div>
                                        <div>
                                            <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                                Drop files here or click to browse
                                            </p>
                                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                                Support PDF, DOC, DOCX, TXT, PPT, PPTX up to 50MB
                                            </p>
                                        </div>
                                        <input
                                            type="file"
                                            multiple
                                            accept=".pdf,.doc,.docx,.txt,.ppt,.pptx"
                                            @change="handleFileSelect"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        />
                                        <button class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium hover:shadow-lg transform hover:-translate-y-0.5 transition-all">
                                            Choose Files
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- File List -->
                            <div v-if="uploadedFiles.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                                    Selected Files ({{ uploadedFiles.length }})
                                </h3>

                                <div class="space-y-3">
                                    <div
                                        v-for="file in uploadedFiles"
                                        :key="file.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                    >
                                        <div class="flex items-center space-x-4 flex-1">
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <DocumentTextIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</p>
                                                <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                                    <span>{{ file.size }}</span>
                                                    <span>{{ file.type }}</span>
                                                    <span :class="`font-medium ${
                                                        file.status === 'ready' ? 'text-gray-600' :
                                                        file.status === 'uploading' ? 'text-blue-600' :
                                                        file.status === 'completed' ? 'text-green-600' :
                                                        'text-red-600'
                                                    }`">
                                                        {{ file.status === 'ready' ? 'Ready' :
                                                           file.status === 'uploading' ? `Uploading ${file.progress}%` :
                                                           file.status === 'completed' ? 'Completed' : 'Error' }}
                                                    </span>
                                                </div>
                                                <div v-if="file.status === 'uploading'" class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div
                                                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                        :style="{ width: file.progress + '%' }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            @click="removeFile(file.id)"
                                            v-if="file.status !== 'uploading'"
                                            class="ml-4 p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        >
                                            <XMarkIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <div v-if="uploadedFiles.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Document Information</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Department -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Department *
                                        </label>
                                        <select
                                            v-model="uploadForm.department_id"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="">Select Department</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Category *
                                        </label>
                                        <select
                                            v-model="uploadForm.category"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="">Select Category</option>
                                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                        </select>
                                    </div>

                                    <!-- Priority -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Priority
                                        </label>
                                        <select
                                            v-model="uploadForm.priority"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
                                                {{ priority.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Visibility -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Visibility
                                        </label>
                                        <select
                                            v-model="uploadForm.visibility"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="public">Public (All Users)</option>
                                            <option value="students">Students</option>
                                            <option value="faculty">Faculty</option>
                                            <option value="admins">Admin Only</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="uploadForm.description"
                                        rows="3"
                                        placeholder="Provide a brief description of the document(s)..."
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none"
                                    ></textarea>
                                </div>

                                <!-- Tags -->
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Tags
                                    </label>
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <span
                                            v-for="tag in uploadForm.tags"
                                            :key="tag"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                        >
                                            {{ tag }}
                                            <button @click="removeTag(tag)" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                                <XMarkIcon class="w-4 h-4" />
                                            </button>
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        placeholder="Add tags (press Enter)"
                                        @keydown.enter.prevent="(e) => { addTag(e.target.value); e.target.value = ''; }"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                    />
                                </div>

                                <!-- Upload Button -->
                                <div class="mt-8 flex items-center justify-between">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        * Required fields
                                    </div>
                                    <button
                                        @click="startUpload"
                                        :disabled="isUploading || uploadedFiles.length === 0"
                                        :class="`px-8 py-4 rounded-xl font-bold text-white transform transition-all duration-200 ${
                                            isUploading || uploadedFiles.length === 0
                                                ? 'bg-gray-400 cursor-not-allowed'
                                                : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:shadow-lg hover:-translate-y-0.5'
                                        }`"
                                    >
                                        {{ isUploading ? 'Uploading...' : `Upload ${uploadedFiles.length} File${uploadedFiles.length !== 1 ? 's' : ''}` }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-8">
                            <!-- Upload Guidelines -->
                            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                    <CheckCircleIcon class="w-5 h-5" />
                                    Upload Guidelines
                                </h3>
                                <ul class="space-y-2 text-sm text-white/90">
                                    <li class="flex items-start gap-2">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full flex-shrink-0 mt-2"></span>
                                        Maximum file size: 50MB
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full flex-shrink-0 mt-2"></span>
                                        Supported formats: PDF, DOC, DOCX, TXT, PPT, PPTX
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full flex-shrink-0 mt-2"></span>
                                        All documents require approval
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full flex-shrink-0 mt-2"></span>
                                        Use descriptive filenames
                                    </li>
                                </ul>
                            </div>

                            <!-- Recent Uploads -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <FolderOpenIcon class="w-5 h-5 text-gray-600" />
                                    Recent Uploads
                                </h3>

                                <div class="space-y-3">
                                    <div
                                        v-for="upload in recentUploads.slice(0, 5)"
                                        :key="upload.id"
                                        class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ upload.name }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    <span>{{ upload.size }}</span>
                                                    <span>•</span>
                                                    <span>{{ upload.uploadedAt }}</span>
                                                </div>
                                            </div>
                                            <span :class="`text-xs px-2 py-1 rounded-full font-medium ${getStatusColor(upload.status)}`">
                                                {{ upload.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href="/admin/documents"
                                    class="block mt-4 text-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium"
                                >
                                    View All Documents →
                                </Link>
                            </div>

                            <!-- Quick Stats -->
                            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                                <h3 class="text-lg font-bold mb-4">Upload Statistics</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-white/80">Today</span>
                                        <span class="font-bold">12 files</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/80">This Week</span>
                                        <span class="font-bold">89 files</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/80">Pending Review</span>
                                        <span class="font-bold">23 files</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/80">Storage Used</span>
                                        <span class="font-bold">2.4 GB</span>
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
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
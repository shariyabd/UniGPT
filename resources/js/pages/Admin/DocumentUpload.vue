<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ArrowUpTrayIcon,
    CloudArrowUpIcon,
    DocumentTextIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    FolderOpenIcon,
    TagIcon,
    UserIcon,
    CalendarIcon,
    ChartBarIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    departments: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    recentUploads: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
});

// Upload state management
const { notify } = useConfirm();

const dragActive = ref(false);
const uploadedFiles = ref([]);
const isUploading = ref(false);

// Form data — department_ids/visibility are multi-select. An empty
// department_ids array means "All Departments" (university-wide).
const uploadForm = ref({
    department_ids: [],
    category: '',
    description: '',
    tags: [],
    visibility: ['students'],
});

const departments = computed(() => props.departments);
const categories = computed(() => props.categories);

const visibilityOptions = [
    { value: 'students', label: 'Students' },
    { value: 'faculty', label: 'Faculty' },
    { value: 'admins', label: 'Admin Only' },
];

// "All Departments" = no specific department selected.
const allDepartmentsSelected = computed(() => uploadForm.value.department_ids.length === 0);

const toggleAllDepartments = () => {
    uploadForm.value.department_ids = [];
};

const toggleDepartment = (id) => {
    const ids = uploadForm.value.department_ids;
    const index = ids.indexOf(id);
    if (index === -1) {
        ids.push(id);
    } else {
        ids.splice(index, 1);
    }
};

const allVisibilitySelected = computed(
    () => uploadForm.value.visibility.length === visibilityOptions.length
);

const toggleAllVisibility = () => {
    uploadForm.value.visibility = allVisibilitySelected.value
        ? []
        : visibilityOptions.map((option) => option.value);
};

const toggleVisibility = (value) => {
    const selected = uploadForm.value.visibility;
    const index = selected.indexOf(value);
    if (index === -1) {
        selected.push(value);
    } else {
        selected.splice(index, 1);
    }
};

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
        notify({
            title: 'File type not allowed',
            message: 'Please upload a PDF, DOC, DOCX, TXT, PPT, or PPTX file.'
        });
        return false;
    }

    if (file.size > maxSize) {
        notify({
            title: 'File too large',
            message: 'The maximum file size is 50MB.'
        });
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
    if (
        uploadedFiles.value.length === 0 ||
        !uploadForm.value.category ||
        uploadForm.value.visibility.length === 0
    ) {
        return;
    }

    isUploading.value = true;

    // Upload each selected file as its own document (one file per request).
    for (const item of uploadedFiles.value) {
        await new Promise((resolve) => {
            router.post(route('admin.documents.store'), {
                title: item.name.replace(/\.[^.]+$/, ''),
                description: uploadForm.value.description,
                department_ids: uploadForm.value.department_ids,
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
        department_ids: [],
        category: '',
        description: '',
        tags: [],
        visibility: ['students'],
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
        low: 'bg-primary-soft text-primary',
        normal: 'bg-success-bg text-success-fg',
        high: 'bg-warning-bg text-warning-fg',
        urgent: 'bg-danger-bg text-danger-fg'
    };
    return colors[priority] || colors.normal;
};

const getStatusColor = (status) => {
    const colors = {
        approved: 'text-success-fg bg-success-bg',
        pending: 'text-warning-fg bg-warning-bg',
        rejected: 'text-danger-fg bg-danger-bg'
    };
    return colors[status] || colors.pending;
};
</script>

<template>
    <div>
        <Head title="Document Upload" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Upload Document"
                    subtitle="Upload course materials, policies, and academic documents"
                    :icon="ArrowUpTrayIcon"
                >
                    <template #actions>
                        <Link href="/admin/dashboard" class="ui-btn-secondary">
                            Back to Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Upload Section -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Drag & Drop Area -->
                        <Card title="Upload Files" :icon="CloudArrowUpIcon">
                            <div
                                @drop.prevent="handleDrop"
                                @dragover.prevent="dragActive = true"
                                @dragenter.prevent="dragActive = true"
                                @dragleave.prevent="dragActive = false"
                                :class="`relative border-2 border-dashed rounded-card p-10 sm:p-12 text-center transition-all duration-200 ${
                                    dragActive
                                        ? 'border-primary bg-primary-soft'
                                        : 'border-line hover:border-primary hover:bg-bg'
                                }`"
                            >
                                <div class="space-y-4">
                                    <div class="mx-auto w-16 h-16 bg-primary-soft rounded-card flex items-center justify-center">
                                        <CloudArrowUpIcon class="w-8 h-8 text-primary" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-content mb-1">
                                            Drop files here or click to browse
                                        </p>
                                        <p class="text-content-muted text-sm">
                                            Support PDF, DOC, DOCX, TXT, PPT, PPTX up to 50MB
                                        </p>
                                    </div>
                                    <input
                                        type="file"
                                        multiple
                                        accept=".pdf,.doc,.docx,.txt,.ppt,.pptx"
                                        @change="handleFileSelect"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        aria-label="Choose files to upload"
                                    />
                                    <button type="button" class="ui-btn-primary">
                                        Choose Files
                                    </button>
                                </div>
                            </div>
                        </Card>

                        <!-- File List -->
                        <Card v-if="uploadedFiles.length > 0" :title="`Selected Files (${uploadedFiles.length})`" :icon="DocumentTextIcon">
                            <div class="space-y-3">
                                <div
                                    v-for="file in uploadedFiles"
                                    :key="file.id"
                                    class="flex items-center justify-between p-4 bg-bg rounded-card border border-line"
                                >
                                    <div class="flex items-center space-x-4 flex-1 min-w-0">
                                        <div class="w-10 h-10 bg-primary-soft rounded-control flex items-center justify-center flex-shrink-0">
                                            <DocumentTextIcon class="w-5 h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-content truncate">{{ file.name }}</p>
                                            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-content-muted">
                                                <span>{{ file.size }}</span>
                                                <span>{{ file.type }}</span>
                                                <span :class="`font-medium ${
                                                    file.status === 'ready' ? 'text-content-muted' :
                                                    file.status === 'uploading' ? 'text-primary' :
                                                    file.status === 'completed' ? 'text-success-fg' :
                                                    'text-danger-fg'
                                                }`">
                                                    {{ file.status === 'ready' ? 'Ready' :
                                                       file.status === 'uploading' ? `Uploading ${file.progress}%` :
                                                       file.status === 'completed' ? 'Completed' : 'Error' }}
                                                </span>
                                            </div>
                                            <div v-if="file.status === 'uploading'" class="mt-2 w-full bg-bg rounded-pill h-2 overflow-hidden">
                                                <div
                                                    class="bg-primary h-2 rounded-pill transition-all duration-300"
                                                    :style="{ width: file.progress + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click="removeFile(file.id)"
                                        v-if="file.status !== 'uploading'"
                                        class="ml-4 p-2 text-content-faint hover:text-danger-fg transition-colors"
                                        aria-label="Remove file"
                                    >
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </Card>

                        <!-- Upload Form -->
                        <Card title="Document Information" :icon="TagIcon">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Category -->
                                <div>
                                    <label class="ui-label">Category *</label>
                                    <select
                                        v-model="uploadForm.category"
                                        class="ui-input"
                                    >
                                        <option value="">Select Category</option>
                                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                    </select>
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="ui-label">Priority</label>
                                    <select
                                        v-model="uploadForm.priority"
                                        class="ui-input"
                                    >
                                        <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
                                            {{ priority.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Departments (multi-select) -->
                                <div class="md:col-span-2">
                                    <label class="ui-label">Departments</label>
                                    <p class="text-xs text-content-muted mb-2">
                                        Select one or more departments, or choose "All Departments" to make it available university-wide.
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="toggleAllDepartments"
                                            :class="[
                                                'px-3 py-1.5 rounded-pill text-sm font-medium border transition-colors',
                                                allDepartmentsSelected
                                                    ? 'bg-primary text-white border-primary'
                                                    : 'bg-bg text-content-muted border-line hover:border-primary',
                                            ]"
                                        >
                                            All Departments
                                        </button>
                                        <button
                                            v-for="dept in departments"
                                            :key="dept.id"
                                            type="button"
                                            @click="toggleDepartment(dept.id)"
                                            :class="[
                                                'px-3 py-1.5 rounded-pill text-sm font-medium border transition-colors',
                                                uploadForm.department_ids.includes(dept.id)
                                                    ? 'bg-primary text-white border-primary'
                                                    : 'bg-bg text-content-muted border-line hover:border-primary',
                                            ]"
                                        >
                                            {{ dept.name }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Visibility (multi-select) -->
                                <div class="md:col-span-2">
                                    <label class="ui-label">Visibility *</label>
                                    <p class="text-xs text-content-muted mb-2">
                                        Choose which audiences can see this document. Select at least one.
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="toggleAllVisibility"
                                            :class="[
                                                'px-3 py-1.5 rounded-pill text-sm font-medium border transition-colors',
                                                allVisibilitySelected
                                                    ? 'bg-primary text-white border-primary'
                                                    : 'bg-bg text-content-muted border-line hover:border-primary',
                                            ]"
                                        >
                                            All
                                        </button>
                                        <button
                                            v-for="option in visibilityOptions"
                                            :key="option.value"
                                            type="button"
                                            @click="toggleVisibility(option.value)"
                                            :class="[
                                                'px-3 py-1.5 rounded-pill text-sm font-medium border transition-colors',
                                                uploadForm.visibility.includes(option.value)
                                                    ? 'bg-primary text-white border-primary'
                                                    : 'bg-bg text-content-muted border-line hover:border-primary',
                                            ]"
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mt-6">
                                <label class="ui-label">Description</label>
                                <textarea
                                    v-model="uploadForm.description"
                                    rows="3"
                                    placeholder="Provide a brief description of the document(s)..."
                                    class="ui-input resize-none"
                                ></textarea>
                            </div>

                            <!-- Tags -->
                            <div class="mt-6">
                                <label class="ui-label">Tags</label>
                                <div v-if="uploadForm.tags.length" class="flex flex-wrap gap-2 mb-3">
                                    <span
                                        v-for="tag in uploadForm.tags"
                                        :key="tag"
                                        class="inline-flex items-center px-3 py-1 rounded-pill text-sm bg-primary-soft text-primary"
                                    >
                                        {{ tag }}
                                        <button @click="removeTag(tag)" class="ml-2 text-primary hover:text-primary-hover" aria-label="Remove tag">
                                            <XMarkIcon class="w-4 h-4" />
                                        </button>
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    placeholder="Add tags (press Enter)"
                                    @keydown.enter.prevent="(e) => { addTag(e.target.value); e.target.value = ''; }"
                                    class="ui-input"
                                />
                            </div>

                            <!-- Upload Button -->
                            <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="text-sm text-content-muted">
                                    * Required fields
                                </div>
                                <button
                                    @click="startUpload"
                                    :disabled="isUploading || uploadedFiles.length === 0 || !uploadForm.category || uploadForm.visibility.length === 0"
                                    class="ui-btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {{ isUploading ? 'Uploading...' : uploadedFiles.length === 0 ? 'Select a file to upload' : `Upload ${uploadedFiles.length} File${uploadedFiles.length !== 1 ? 's' : ''}` }}
                                </button>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Upload Guidelines -->
                        <Card>
                            <h3 class="text-base font-semibold text-content mb-4 flex items-center gap-2">
                                <CheckCircleIcon class="w-5 h-5 text-primary" />
                                Upload Guidelines
                            </h3>
                            <ul class="space-y-3 text-sm text-content-muted">
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    Maximum file size: 50MB
                                </li>
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    Supported formats: PDF, DOC, DOCX, TXT, PPT, PPTX
                                </li>
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    All documents require approval
                                </li>
                                <li class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    Use descriptive filenames
                                </li>
                            </ul>
                        </Card>

                        <!-- Recent Uploads -->
                        <Card>
                            <h3 class="text-base font-semibold text-content mb-4 flex items-center gap-2">
                                <FolderOpenIcon class="w-5 h-5 text-primary" />
                                Recent Uploads
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="upload in recentUploads.slice(0, 5)"
                                    :key="upload.id"
                                    class="p-3 bg-bg rounded-control border border-line"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-content truncate">
                                                {{ upload.name }}
                                            </p>
                                            <div class="flex items-center gap-2 text-xs text-content-muted mt-1">
                                                <span>{{ upload.size }}</span>
                                                <span>•</span>
                                                <span>{{ upload.uploadedAt }}</span>
                                            </div>
                                        </div>
                                        <span :class="`text-xs px-2 py-1 rounded-pill font-medium ${getStatusColor(upload.status)}`">
                                            {{ upload.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/admin/documents"
                                class="mt-4 inline-flex items-center gap-1 text-sm text-primary hover:text-primary-hover font-medium"
                            >
                                View All Documents
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </Card>

                        <!-- Quick Stats -->
                        <Card>
                            <h3 class="text-base font-semibold text-content mb-4 flex items-center gap-2">
                                <ChartBarIcon class="w-5 h-5 text-primary" />
                                Upload Statistics
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-content-muted">Today</span>
                                    <span class="font-semibold text-content">{{ stats.today || 0 }} files</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-content-muted">This Week</span>
                                    <span class="font-semibold text-content">{{ stats.this_week || 0 }} files</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-content-muted">Pending Review</span>
                                    <span class="font-semibold text-content">{{ stats.pending_review || 0 }} files</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-content-muted">Storage Used</span>
                                    <span class="font-semibold text-content">{{ stats.storage_used || '0 B' }}</span>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

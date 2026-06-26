<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    PlusIcon,
    XMarkIcon,
    DocumentArrowUpIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    EyeIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    routeNames: { type: Object, default: () => ({}) },
});

const { confirm } = useConfirm();

const showModal = ref(false);
const editingId = ref(null);
const isEditing = computed(() => editingId.value !== null);
const fileName = ref('');

// Tags are edited as a comma-separated string and normalised to an array on
// submit, matching the array the backend validates.
const tagsText = ref('');

const form = useForm({
    title: '',
    description: '',
    category: 'General',
    department_id: '',
    tags: [],
    file: null,
});

const categoryOptions = computed(() => props.categories.map((c) => ({ value: c, label: c })));
const departmentOptions = computed(() => props.departments.map((d) => ({ value: d.id, label: d.name })));

// Map the document status colour token to a Badge variant.
const statusVariant = (color) => ({ yellow: 'warning', blue: 'info', green: 'success', red: 'danger' }[color] ?? 'neutral');

const onFileChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.file = file;
    fileName.value = file?.name ?? '';
};

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    tagsText.value = '';
    fileName.value = '';
    showModal.value = true;
};

const openEdit = (doc) => {
    editingId.value = doc.id;
    form.clearErrors();
    form.title = doc.title;
    form.description = doc.description ?? '';
    form.category = doc.category ?? 'General';
    form.department_id = doc.departmentIds?.[0] ?? '';
    form.tags = doc.tags ?? [];
    form.file = null;
    tagsText.value = (doc.tags ?? []).join(', ');
    fileName.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            tags: tagsText.value.split(',').map((t) => t.trim()).filter(Boolean),
            department_ids: data.department_id ? [data.department_id] : [],
        }))
        .post(isEditing.value ? route(props.routeNames.update, editingId.value) : route(props.routeNames.store), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: closeModal,
        });
};

const remove = async (doc) => {
    const confirmed = await confirm({
        title: 'Delete submission',
        message: `This will permanently delete "${doc.title}". This action cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) return;

    router.delete(route(props.routeNames.destroy, doc.id), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="My Documents" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="My Documents"
                    subtitle="Submit documents to the knowledge base. An admin reviews each one before it goes live."
                    :icon="DocumentArrowUpIcon"
                    :count="documents.length"
                >
                    <template #actions>
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            Upload document
                        </button>
                    </template>
                </PageHeader>

                <Card v-if="documents.length === 0" padding="p-0">
                    <EmptyState
                        title="No submissions yet"
                        description="Upload your first document. You can track its review status here."
                        :icon="DocumentTextIcon"
                    >
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            Upload document
                        </button>
                    </EmptyState>
                </Card>

                <Card v-else padding="p-0">
                    <ul class="divide-y divide-line">
                        <li
                            v-for="doc in documents"
                            :key="doc.id"
                            class="p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-bg transition-colors"
                        >
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="hidden sm:flex w-10 h-10 rounded-control bg-primary-soft items-center justify-center flex-shrink-0">
                                    <DocumentTextIcon class="w-5 h-5 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <Badge :variant="statusVariant(doc.statusColor)">{{ doc.statusLabel }}</Badge>
                                        <span class="text-xs font-semibold text-content-muted">{{ doc.fileType }}</span>
                                        <span class="text-xs text-content-faint">{{ doc.fileSize }}</span>
                                    </div>
                                    <p class="font-semibold text-content mt-0.5 truncate">{{ doc.title }}</p>
                                    <p v-if="doc.description" class="text-sm text-content-muted mt-0.5 line-clamp-1">{{ doc.description }}</p>
                                    <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-content-muted">
                                        <span>{{ doc.category }}</span>
                                        <span>·</span>
                                        <span>{{ doc.departments }}</span>
                                    </div>

                                    <!-- Rejection reason / reviewer feedback the uploader should act on -->
                                    <p v-if="doc.rejectionReason" class="mt-2 text-xs text-danger-fg bg-danger-bg rounded-control px-2 py-1 inline-block">
                                        Rejected: {{ doc.rejectionReason }}
                                    </p>
                                    <div v-else-if="doc.comments.length" class="mt-2 space-y-1">
                                        <p
                                            v-for="comment in doc.comments"
                                            :key="comment.id"
                                            class="text-xs text-content-muted"
                                        >
                                            <span class="font-medium text-content">{{ comment.author }}:</span> {{ comment.content }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a
                                    :href="doc.previewUrl"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Preview document"
                                    class="p-2 rounded-control text-content-faint hover:text-primary hover:bg-primary-soft transition-colors"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                </a>
                                <a
                                    :href="doc.downloadUrl"
                                    aria-label="Download document"
                                    class="p-2 rounded-control text-content-faint hover:text-primary hover:bg-primary-soft transition-colors"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                </a>
                                <button
                                    type="button"
                                    @click="openEdit(doc)"
                                    aria-label="Edit submission"
                                    class="p-2 rounded-control text-content-faint hover:text-primary hover:bg-primary-soft transition-colors"
                                >
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="remove(doc)"
                                    aria-label="Delete submission"
                                    class="p-2 rounded-control text-content-faint hover:text-danger-fg hover:bg-danger-bg transition-colors"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- Create / Edit modal -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeModal"></div>

                        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                            <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                                <h3 class="ui-card-title">{{ isEditing ? 'Edit submission' : 'Upload document' }}</h3>
                                <button type="button" @click="closeModal" class="ui-btn-ghost p-2" aria-label="Close">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                                <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                    <div>
                                        <label class="ui-label">File {{ isEditing ? '(leave empty to keep current)' : '*' }}</label>
                                        <input
                                            type="file"
                                            accept=".pdf,.doc,.docx,.txt,.md,.ppt,.pptx"
                                            class="ui-input"
                                            @change="onFileChange"
                                        />
                                        <p v-if="fileName" class="text-xs text-content-muted mt-1">Selected: {{ fileName }}</p>
                                        <p v-if="form.errors.file" class="text-xs text-danger-fg mt-1">{{ form.errors.file }}</p>
                                    </div>

                                    <div>
                                        <label class="ui-label">Title *</label>
                                        <input v-model="form.title" type="text" placeholder="Document title" class="ui-input" />
                                        <p v-if="form.errors.title" class="text-xs text-danger-fg mt-1">{{ form.errors.title }}</p>
                                    </div>

                                    <div>
                                        <label class="ui-label">Description</label>
                                        <textarea v-model="form.description" rows="2" class="ui-input resize-none" placeholder="What is this document about?"></textarea>
                                        <p v-if="form.errors.description" class="text-xs text-danger-fg mt-1">{{ form.errors.description }}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="ui-label">Category *</label>
                                            <SearchableSelect v-model="form.category" :options="categoryOptions" />
                                            <p v-if="form.errors.category" class="text-xs text-danger-fg mt-1">{{ form.errors.category }}</p>
                                        </div>
                                        <div>
                                            <label class="ui-label">Department</label>
                                            <SearchableSelect
                                                v-model="form.department_id"
                                                :options="departmentOptions"
                                                placeholder="All Departments"
                                                clearable
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="ui-label">Tags</label>
                                        <input v-model="tagsText" type="text" placeholder="comma, separated, tags" class="ui-input" />
                                        <p class="text-xs text-content-muted mt-1">Separate tags with commas.</p>
                                    </div>
                                </div>

                                <div class="flex flex-shrink-0 items-center justify-end gap-3 border-t border-line px-6 py-4">
                                    <button type="button" @click="closeModal" class="ui-btn-ghost">Cancel</button>
                                    <button type="submit" :disabled="form.processing" class="ui-btn-primary">
                                        {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Submit for review') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>
        </AppLayout>
    </div>
</template>

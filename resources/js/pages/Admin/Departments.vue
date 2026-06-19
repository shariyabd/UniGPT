<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    BuildingOffice2Icon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    UsersIcon,
    BookOpenIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    departments: { type: Object, default: () => ({ data: [] }) },
});

const departmentItems = computed(() => props.departments.data ?? []);

const { confirm, notify } = useConfirm();

const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const isEditing = computed(() => editingId.value !== null);

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (dept) => {
    editingId.value = dept.id;
    form.clearErrors();
    form.name = dept.name;
    form.code = dept.code ?? '';
    form.description = dept.description ?? '';
    form.is_active = dept.isActive;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };

    if (isEditing.value) {
        form.patch(route('admin.departments.update', editingId.value), options);
    } else {
        form.post(route('admin.departments.store'), options);
    }
};

const remove = async (dept) => {
    if (dept.users > 0 || dept.courses > 0) {
        await notify({
            title: 'Cannot delete department',
            message: `"${dept.name}" still has users or courses assigned and cannot be deleted.`
        });
        return;
    }

    const confirmed = await confirm({
        title: 'Delete department',
        message: `This will permanently delete "${dept.name}". This action cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger'
    });

    if (!confirmed) return;

    router.delete(route('admin.departments.destroy', dept.id), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Departments" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Departments"
                    subtitle="Manage academic departments, their codes and status."
                    :icon="BuildingOffice2Icon"
                >
                    <template #actions>
                        <button type="button" @click="openCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            New department
                        </button>
                    </template>
                </PageHeader>

                <!-- Empty state -->
                <EmptyState
                    v-if="departmentItems.length === 0"
                    title="No departments yet"
                    description="Create your first academic department to organise users and courses."
                    :icon="BuildingOffice2Icon"
                >
                    <button type="button" @click="openCreate" class="ui-btn-primary">
                        <PlusIcon class="w-4 h-4" />
                        New department
                    </button>
                </EmptyState>

                <!-- List -->
                <template v-else>
                    <p class="text-sm text-content-muted">
                        {{ departmentItems.length }} department{{ departmentItems.length === 1 ? '' : 's' }} on this page
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                        <Card
                            v-for="dept in departmentItems"
                            :key="dept.id"
                            hover
                            class="flex flex-col"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                        <BuildingOffice2Icon class="w-5 h-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-content truncate">{{ dept.name }}</p>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            <Badge v-if="dept.code" variant="violet">{{ dept.code }}</Badge>
                                            <Badge :variant="dept.isActive ? 'success' : 'danger'" dot>
                                                {{ dept.isActive ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button
                                        type="button"
                                        @click="openEdit(dept)"
                                        aria-label="Edit department"
                                        class="ui-btn-ghost p-2"
                                    >
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="remove(dept)"
                                        aria-label="Delete department"
                                        class="ui-btn-danger p-2 disabled:opacity-30"
                                        :disabled="dept.users > 0 || dept.courses > 0"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <p v-if="dept.description" class="text-sm text-content-muted mt-3 line-clamp-2">
                                {{ dept.description }}
                            </p>

                            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-line text-xs text-content-muted">
                                <span class="inline-flex items-center gap-1.5">
                                    <UsersIcon class="w-4 h-4" />
                                    {{ dept.users }} user(s)
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <BookOpenIcon class="w-4 h-4" />
                                    {{ dept.courses }} course(s)
                                </span>
                            </div>
                        </Card>
                    </div>

                    <Pagination :paginator="departments" label="departments" />
                </template>
            </div>

            <!-- Create / Edit modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeModal"></div>

                    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">
                                {{ isEditing ? 'Edit department' : 'New department' }}
                            </h3>
                            <button
                                type="button"
                                @click="closeModal"
                                class="ui-btn-ghost p-2"
                                aria-label="Close"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                <div>
                                    <label for="dept-name" class="ui-label">Name *</label>
                                    <input
                                        id="dept-name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Computer Science Engineering"
                                        class="ui-input"
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-danger-fg mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="dept-code" class="ui-label">Code</label>
                                    <input
                                        id="dept-code"
                                        v-model="form.code"
                                        type="text"
                                        placeholder="CSE"
                                        class="ui-input"
                                    />
                                    <p v-if="form.errors.code" class="text-xs text-danger-fg mt-1">{{ form.errors.code }}</p>
                                </div>
                                <div>
                                    <label for="dept-description" class="ui-label">Description</label>
                                    <textarea
                                        id="dept-description"
                                        v-model="form.description"
                                        rows="3"
                                        class="ui-input resize-none"
                                    ></textarea>
                                    <p v-if="form.errors.description" class="text-xs text-danger-fg mt-1">{{ form.errors.description }}</p>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-content-muted">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded border-line text-primary focus:ring-primary"
                                    />
                                    Active
                                </label>
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                <button type="button" @click="closeModal" class="ui-btn-ghost">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="form.processing" class="ui-btn-primary">
                                    {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Create department') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

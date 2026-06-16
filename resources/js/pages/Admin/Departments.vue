<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    BuildingOffice2Icon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    UsersIcon,
    BookOpenIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    departments: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const isEditing = computed(() => editingId.value !== null);

const startCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const startEdit = (dept) => {
    editingId.value = dept.id;
    form.clearErrors();
    form.name = dept.name;
    form.code = dept.code ?? '';
    form.description = dept.description ?? '';
    form.is_active = dept.isActive;
};

const submit = () => {
    if (isEditing.value) {
        form.patch(route('admin.departments.update', editingId.value), { preserveScroll: true, onSuccess: startCreate });
    } else {
        form.post(route('admin.departments.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    }
};

const remove = (dept) => {
    if (dept.users > 0 || dept.courses > 0) {
        alert('This department still has users or courses and cannot be deleted.');
        return;
    }
    if (confirm(`Delete department "${dept.name}"?`)) {
        router.delete(route('admin.departments.destroy', dept.id), { preserveScroll: true });
    }
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
                        <button type="button" @click="startCreate" class="ui-btn-primary">
                            <PlusIcon class="w-4 h-4" />
                            New department
                        </button>
                    </template>
                </PageHeader>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form -->
                    <Card class="lg:col-span-1 h-fit">
                        <template #header>
                            <div class="flex items-center justify-between">
                                <h2 class="ui-card-title">{{ isEditing ? 'Edit department' : 'New department' }}</h2>
                                <button
                                    v-if="isEditing"
                                    type="button"
                                    @click="startCreate"
                                    class="ui-btn-ghost text-xs"
                                >
                                    <PlusIcon class="w-3.5 h-3.5" />
                                    New
                                </button>
                            </div>
                        </template>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="dept-name" class="ui-label">Name</label>
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
                            </div>
                            <label class="flex items-center gap-2 text-sm text-content-muted">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-line text-primary focus:ring-primary"
                                />
                                Active
                            </label>
                            <button type="submit" :disabled="form.processing" class="ui-btn-primary w-full justify-center">
                                <PlusIcon v-if="!isEditing" class="w-4 h-4" />
                                {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Create department') }}
                            </button>
                        </form>
                    </Card>

                    <!-- List -->
                    <div class="lg:col-span-2">
                        <EmptyState
                            v-if="departments.length === 0"
                            title="No departments yet"
                            description="Create your first academic department using the form."
                            :icon="BuildingOffice2Icon"
                        />

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <Card
                                v-for="dept in departments"
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
                                            @click="startEdit(dept)"
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
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

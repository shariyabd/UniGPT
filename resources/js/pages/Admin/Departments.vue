<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BuildingOffice2Icon, PencilSquareIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

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
    <Head title="Departments" />

    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                    <BuildingOffice2Icon class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Departments</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage academic departments.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form -->
                <form @submit.prevent="submit" class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-4 h-fit">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ isEditing ? 'Edit department' : 'New department' }}</h2>
                        <button v-if="isEditing" type="button" @click="startCreate" class="text-xs text-gray-400 hover:text-indigo-600">+ New</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="Computer Science Engineering"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                        <input v-model="form.code" type="text" placeholder="CSE"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Active
                    </label>
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <PlusIcon v-if="!isEditing" class="w-4 h-4" />
                        {{ form.processing ? 'Saving…' : (isEditing ? 'Save changes' : 'Create department') }}
                    </button>
                </form>

                <!-- List -->
                <div class="lg:col-span-2">
                    <div v-if="departments.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                        No departments yet.
                    </div>
                    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                        <div v-for="dept in departments" :key="dept.id" class="p-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ dept.name }}</p>
                                    <span v-if="dept.code" class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium">{{ dept.code }}</span>
                                    <span v-if="!dept.isActive" class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300">Inactive</span>
                                </div>
                                <p v-if="dept.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ dept.description }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ dept.users }} user(s) · {{ dept.courses }} course(s)</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button type="button" @click="startEdit(dept)" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button type="button" @click="remove(dept)" class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-30"
                                    :disabled="dept.users > 0 || dept.courses > 0">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

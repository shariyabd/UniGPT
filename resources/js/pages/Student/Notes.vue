<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PencilSquareIcon, TrashIcon, PlusIcon, BookmarkIcon } from '@heroicons/vue/24/outline';
import { BookmarkIcon as BookmarkSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    title: '',
    content: '',
    course_id: null,
    is_pinned: false,
});

const startCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const startEdit = (note) => {
    editingId.value = note.id;
    form.clearErrors();
    form.title = note.title;
    form.content = note.content ?? '';
    form.course_id = note.courseId ?? null;
    form.is_pinned = note.isPinned;
};

const submit = () => {
    if (editingId.value) {
        form.patch(route('notes.update', editingId.value), { preserveScroll: true, onSuccess: startCreate });
    } else {
        form.post(route('notes.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    }
};

const remove = (note) => {
    if (confirm('Delete this note?')) {
        router.delete(route('notes.destroy', note.id), { preserveScroll: true });
    }
};

const togglePin = (note) => {
    router.patch(route('notes.update', note.id), {
        title: note.title,
        content: note.content,
        course_id: note.courseId,
        is_pinned: !note.isPinned,
    }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Notes" />

    <AppLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">My Notes</h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Editor -->
                <form @submit.prevent="submit" class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-4 h-fit">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ editingId ? 'Edit note' : 'New note' }}</h2>
                        <button v-if="editingId" type="button" @click="startCreate" class="text-xs text-gray-400 hover:text-indigo-600">+ New</button>
                    </div>
                    <div>
                        <input v-model="form.title" type="text" placeholder="Title"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>
                    <textarea v-model="form.content" rows="6" placeholder="Write your note…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    <select v-model="form.course_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option :value="null">— No course —</option>
                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="form.is_pinned" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Pin to top
                    </label>
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <PlusIcon v-if="!editingId" class="w-4 h-4" />
                        {{ form.processing ? 'Saving…' : (editingId ? 'Save changes' : 'Add note') }}
                    </button>
                </form>

                <!-- List -->
                <div class="lg:col-span-2">
                    <div v-if="notes.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                        No notes yet. Jot something down!
                    </div>
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="note in notes" :key="note.id" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 flex flex-col">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ note.title }}</h3>
                                <button type="button" @click="togglePin(note)" :title="note.isPinned ? 'Unpin' : 'Pin'">
                                    <component :is="note.isPinned ? BookmarkSolid : BookmarkIcon" class="w-5 h-5" :class="note.isPinned ? 'text-amber-500' : 'text-gray-300 hover:text-amber-500'" />
                                </button>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap flex-1">{{ note.content }}</p>
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-xs text-gray-400">
                                    <span v-if="note.courseCode" class="font-semibold text-indigo-500">{{ note.courseCode }}</span>
                                    <span v-if="note.courseCode"> · </span>{{ note.updatedAt }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="startEdit(note)" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="remove(note)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

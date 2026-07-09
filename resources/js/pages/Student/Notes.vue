<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { useConfirm } from '@/composables/useConfirm';
import { PencilIcon, TrashIcon, PlusIcon, BookmarkIcon, CameraIcon } from '@heroicons/vue/24/outline';
import { BookmarkIcon as BookmarkSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    notes: { type: Object, default: () => ({ data: [] }) },
    courses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const toast = useToast();

const noteItems = computed(() => props.notes.data ?? []);

const editingId = ref(null);

// --- OCR: scan a handwritten note into the editor -------------------------
const fileInput = ref(null);
const scanning = ref(false);

const pickImage = () => fileInput.value?.click();

const scanImage = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    scanning.value = true;
    const data = new FormData();
    data.append('image', file);

    try {
        const { data: result } = await axios.post(route('notes.ocr'), data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        // Append to any existing content so a scan never wipes what's typed.
        form.content = form.content ? `${form.content}\n\n${result.text}` : result.text;
        if (!form.title) form.title = 'Scanned note';
        toast.success('Note transcribed — review and edit before saving.');
    } catch (error) {
        toast.error(error.response?.data?.message ?? 'Could not read that image. Try a clearer photo.');
    } finally {
        scanning.value = false;
        event.target.value = '';
    }
};

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

const remove = async (note) => {
    const ok = await confirm({
        title: 'Delete note',
        message: 'Delete this note?',
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (ok) {
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
    <div>
        <Head title="Notes" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Notes"
                    subtitle="Capture and organize your personal study notes."
                    :icon="PencilIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Editor -->
                    <div class="lg:col-span-1">
                        <Card
                            :title="editingId ? 'Edit note' : 'New note'"
                            :icon="PencilIcon"
                            class="h-fit"
                        >
                            <template #actions>
                                <button
                                    v-if="editingId"
                                    type="button"
                                    @click="startCreate"
                                    class="ui-btn-ghost px-2.5 py-1.5 text-xs"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    New
                                </button>
                            </template>

                            <form @submit.prevent="submit" class="space-y-4">
                                <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="scanImage" />
                                <button
                                    type="button"
                                    :disabled="scanning"
                                    class="ui-btn-secondary w-full"
                                    @click="pickImage"
                                >
                                    <CameraIcon class="h-4 w-4" />
                                    {{ scanning ? 'Transcribing…' : 'Scan handwritten note' }}
                                </button>

                                <div>
                                    <label class="ui-label" for="note-title">Title</label>
                                    <input
                                        id="note-title"
                                        v-model="form.title"
                                        type="text"
                                        placeholder="Title"
                                        class="ui-input"
                                    />
                                    <p v-if="form.errors.title" class="mt-1 text-xs text-danger-fg">{{ form.errors.title }}</p>
                                </div>

                                <div>
                                    <label class="ui-label" for="note-content">Content</label>
                                    <textarea
                                        id="note-content"
                                        v-model="form.content"
                                        rows="6"
                                        placeholder="Write your note…"
                                        class="ui-input resize-none"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="ui-label" for="note-course">Course</label>
                                    <select id="note-course" v-model="form.course_id" class="ui-input">
                                        <option :value="null">— No course —</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                    </select>
                                </div>

                                <label class="flex items-center gap-2 text-sm text-content">
                                    <input
                                        v-model="form.is_pinned"
                                        type="checkbox"
                                        class="rounded border-line text-primary focus:ring-primary/20"
                                    />
                                    Pin to top
                                </label>

                                <button type="submit" :disabled="form.processing" class="ui-btn-primary w-full">
                                    <PlusIcon v-if="!editingId" class="h-4 w-4" />
                                    {{ form.processing ? 'Saving…' : (editingId ? 'Save changes' : 'Add note') }}
                                </button>
                            </form>
                        </Card>
                    </div>

                    <!-- List -->
                    <div class="lg:col-span-2">
                        <Card v-if="noteItems.length === 0" padding="p-0">
                            <EmptyState
                                title="No notes yet"
                                description="Jot something down using the editor to start your collection."
                                :icon="PencilIcon"
                            />
                        </Card>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div
                                v-for="note in noteItems"
                                :key="note.id"
                                class="group ui-card flex flex-col p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                            >
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <h3 class="font-semibold text-content">{{ note.title }}</h3>
                                    <button
                                        type="button"
                                        @click="togglePin(note)"
                                        :title="note.isPinned ? 'Unpin' : 'Pin'"
                                        :aria-label="note.isPinned ? 'Unpin note' : 'Pin note'"
                                        class="shrink-0 rounded-control p-1 transition-colors"
                                    >
                                        <component
                                            :is="note.isPinned ? BookmarkSolid : BookmarkIcon"
                                            class="h-5 w-5"
                                            :class="note.isPinned ? 'text-warning-fg' : 'text-content-faint hover:text-warning-fg'"
                                        />
                                    </button>
                                </div>

                                <p class="flex-1 whitespace-pre-wrap text-sm text-content-muted">{{ note.content }}</p>

                                <div class="mt-3 flex items-center justify-between border-t border-line pt-3">
                                    <span class="text-xs text-content-faint">
                                        <span v-if="note.courseCode" class="font-semibold text-primary">{{ note.courseCode }}</span>
                                        <span v-if="note.courseCode"> · </span>{{ note.updatedAt }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="startEdit(note)"
                                            aria-label="Edit note"
                                            class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-primary-soft hover:text-primary"
                                        >
                                            <PencilIcon class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="remove(note)"
                                            aria-label="Delete note"
                                            class="rounded-control p-1.5 text-content-faint transition-colors hover:bg-danger-bg hover:text-danger-fg"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Pagination :paginator="notes" label="notes" />
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

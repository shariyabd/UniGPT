<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import {
    MegaphoneIcon,
    PaperAirplaneIcon,
    UsersIcon,
    ClockIcon,
    InboxStackIcon,
    PencilIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    audiences: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const form = useForm({
    audience: 'all',
    title: '',
    message: '',
});

const audienceOptions = computed(() =>
    props.audiences.map((a) => ({ value: a.value, label: a.label })),
);

/* ---- Client-side pagination over the recent announcements list ---- */
const PER_PAGE = 12;
const currentPage = ref(1);

const totalPages = computed(() => Math.max(1, Math.ceil(props.recent.length / PER_PAGE)));

const paginatedRecent = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return props.recent.slice(start, start + PER_PAGE);
});

// A fresh list (e.g. after sending an announcement) resets to the first page.
watch(() => props.recent, () => {
    currentPage.value = 1;
});

const goToPage = (page) => {
    currentPage.value = Math.min(Math.max(1, page), totalPages.value);
};

const submit = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'message'),
    });
};

/* ---- Edit a sent announcement (title + message only; audience is fixed) ---- */
const showEdit = ref(false);
const editForm = useForm({
    created_at: '',
    original_title: '',
    title: '',
    message: '',
});

const openEdit = (item) => {
    editForm.clearErrors();
    editForm.created_at = item.createdAt;
    editForm.original_title = item.title;
    editForm.title = item.title;
    editForm.message = item.message;
    showEdit.value = true;
};

const closeEdit = () => {
    showEdit.value = false;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    editForm.patch(route('admin.announcements.update'), {
        preserveScroll: true,
        onSuccess: closeEdit,
    });
};
</script>

<template>
    <div>
        <Head title="Announcements" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Announcements"
                    subtitle="Broadcast a notification to a group of users."
                    eyebrow="Admin"
                    :icon="MegaphoneIcon"
                    :count="recent.length"
                />

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Compose -->
                    <div class="lg:col-span-3">
                        <Card title="Compose announcement" subtitle="Send a new notification" :icon="PaperAirplaneIcon">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label class="ui-label" for="announcement-audience">Audience</label>
                                    <SearchableSelect
                                        v-model="form.audience"
                                        :options="audienceOptions"
                                    />
                                </div>

                                <div>
                                    <label class="ui-label" for="announcement-title">Title</label>
                                    <input
                                        id="announcement-title"
                                        v-model="form.title"
                                        type="text"
                                        maxlength="150"
                                        placeholder="Midterm schedule released"
                                        class="ui-input"
                                    />
                                    <p v-if="form.errors.title" class="text-xs text-danger-fg mt-1">{{ form.errors.title }}</p>
                                </div>

                                <div>
                                    <label class="ui-label" for="announcement-message">Message</label>
                                    <textarea
                                        id="announcement-message"
                                        v-model="form.message"
                                        rows="4"
                                        maxlength="2000"
                                        placeholder="Write your announcement…"
                                        class="ui-input resize-none"
                                    ></textarea>
                                    <p v-if="form.errors.message" class="text-xs text-danger-fg mt-1">{{ form.errors.message }}</p>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" :disabled="form.processing" class="ui-btn-primary">
                                        <PaperAirplaneIcon class="w-4 h-4" />
                                        {{ form.processing ? 'Sending…' : 'Send announcement' }}
                                    </button>
                                </div>
                            </form>
                        </Card>
                    </div>

                    <!-- Recent -->
                    <div class="lg:col-span-2 space-y-4">
                        <h2 class="ui-card-title flex items-center gap-2 px-1">
                            <ClockIcon class="w-5 h-5 text-primary" />
                            Recent announcements
                        </h2>

                        <EmptyState
                            v-if="recent.length === 0"
                            title="No announcements yet"
                            description="Sent announcements will appear here."
                            :icon="InboxStackIcon"
                        />

                        <div v-else class="space-y-3">
                            <Card v-for="(item, i) in paginatedRecent" :key="i" hover padding="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-content">{{ item.title }}</p>
                                    <div class="shrink-0 flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 text-xs text-content-muted">
                                            <ClockIcon class="w-3.5 h-3.5" />
                                            {{ item.time }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="openEdit(item)"
                                            class="ui-btn-ghost p-1.5"
                                            aria-label="Edit announcement"
                                            title="Edit announcement"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-content-muted mt-1">{{ item.message }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <Badge v-if="item.audience" variant="violet" dot>{{ item.audience }}</Badge>
                                    <Badge variant="slate">
                                        <UsersIcon class="w-3.5 h-3.5" />
                                        {{ item.recipients }} recipient(s)
                                    </Badge>
                                </div>
                            </Card>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="totalPages > 1"
                            class="flex items-center justify-between gap-4 pt-1"
                        >
                            <span class="text-sm text-content-muted">
                                Page {{ currentPage }} of {{ totalPages }} · {{ recent.length }} results
                            </span>
                            <div class="flex items-center gap-2">
                                <button
                                    class="ui-btn-secondary"
                                    :disabled="currentPage === 1"
                                    @click="goToPage(currentPage - 1)"
                                >
                                    Previous
                                </button>
                                <button
                                    class="ui-btn-secondary"
                                    :disabled="currentPage === totalPages"
                                    @click="goToPage(currentPage + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit announcement modal -->
            <Teleport to="body">
                <div v-if="showEdit" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="closeEdit"></div>
                        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden ui-card">
                            <div class="flex flex-shrink-0 items-center justify-between border-b border-line px-6 py-4">
                                <h3 class="ui-card-title">Edit announcement</h3>
                                <button type="button" @click="closeEdit" class="ui-btn-ghost p-2" aria-label="Close">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <form @submit.prevent="submitEdit" class="flex min-h-0 flex-1 flex-col">
                                <div class="flex-1 space-y-4 overflow-y-auto p-6">
                                    <p class="text-xs text-content-muted">
                                        Editing updates the title and message for everyone who received this announcement. The audience can't be changed.
                                    </p>
                                    <div>
                                        <label class="ui-label">Title</label>
                                        <input
                                            v-model="editForm.title"
                                            type="text"
                                            maxlength="150"
                                            class="ui-input"
                                        />
                                        <p v-if="editForm.errors.title" class="text-xs text-danger-fg mt-1">{{ editForm.errors.title }}</p>
                                    </div>
                                    <div>
                                        <label class="ui-label">Message</label>
                                        <textarea
                                            v-model="editForm.message"
                                            rows="4"
                                            maxlength="2000"
                                            class="ui-input resize-none"
                                        ></textarea>
                                        <p v-if="editForm.errors.message" class="text-xs text-danger-fg mt-1">{{ editForm.errors.message }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                                    <button type="button" @click="closeEdit" class="ui-btn-ghost">Cancel</button>
                                    <button type="submit" :disabled="editForm.processing" class="ui-btn-primary">
                                        {{ editForm.processing ? 'Saving…' : 'Save changes' }}
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

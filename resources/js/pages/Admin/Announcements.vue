<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    MegaphoneIcon,
    PaperAirplaneIcon,
    UsersIcon,
    ClockIcon,
    InboxStackIcon,
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

const submit = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'message'),
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
                />

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Compose -->
                    <div class="lg:col-span-3">
                        <Card title="Compose announcement" subtitle="Send a new notification" :icon="PaperAirplaneIcon">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label class="ui-label" for="announcement-audience">Audience</label>
                                    <select
                                        id="announcement-audience"
                                        v-model="form.audience"
                                        class="ui-input"
                                    >
                                        <option v-for="a in audiences" :key="a.value" :value="a.value">{{ a.label }}</option>
                                    </select>
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
                            <Card v-for="(item, i) in recent" :key="i" hover padding="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-content">{{ item.title }}</p>
                                    <span class="shrink-0 inline-flex items-center gap-1 text-xs text-content-muted">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ item.time }}
                                    </span>
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
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

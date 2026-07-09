<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { useConfirm } from '@/composables/useConfirm';
import { ShieldCheckIcon, TrashIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    reports: { type: Object, default: () => ({ data: [] }) },
});

const { confirm } = useConfirm();
const items = computed(() => props.reports.data ?? []);

const dismiss = (report) => {
    router.post(route('admin.discussions.reports.resolve', report.id), {}, { preserveScroll: true });
};

const remove = async (report) => {
    const ok = await confirm({
        title: 'Remove content',
        message: `Remove this ${report.type} and resolve the report?`,
        confirmLabel: 'Remove',
        variant: 'danger',
    });
    if (ok) router.post(route('admin.discussions.reports.remove', report.id), {}, { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Discussion Reports" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <PageHeader
                    title="Discussion Reports"
                    subtitle="Review reported posts and comments across all section discussions."
                    :icon="ShieldCheckIcon"
                />

                <EmptyState
                    v-if="items.length === 0"
                    title="No open reports"
                    description="Reported posts and comments will appear here for review."
                    :icon="ShieldCheckIcon"
                />

                <Card v-for="report in items" :key="report.id" padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :variant="report.type === 'post' ? 'primary' : 'neutral'">{{ report.type }}</Badge>
                                <Badge v-if="report.section" variant="neutral">{{ report.section }}</Badge>
                                <span class="text-xs text-content-faint">Reported by {{ report.reporter }} · {{ report.reportedAt }}</span>
                            </div>
                            <p class="text-sm text-danger-fg mt-2">Reason: {{ report.reason }}</p>
                            <div class="mt-2 rounded-lg bg-surface-muted p-3">
                                <p v-if="report.removed" class="text-sm italic text-content-faint">Content already removed.</p>
                                <template v-else>
                                    <p class="text-xs text-content-faint">{{ report.author }}</p>
                                    <p class="text-sm text-content whitespace-pre-line">{{ report.body }}</p>
                                </template>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 flex-shrink-0">
                            <button type="button" class="ui-btn-ghost text-xs" @click="dismiss(report)">
                                <CheckIcon class="w-4 h-4" /> Dismiss
                            </button>
                            <button v-if="!report.removed" type="button" class="ui-btn-ghost text-xs text-danger-fg" @click="remove(report)">
                                <TrashIcon class="w-4 h-4" /> Remove
                            </button>
                        </div>
                    </div>
                </Card>

                <Pagination :paginator="reports" label="reports" />
            </div>
        </AppLayout>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    ClipboardDocumentListIcon,
    PlusIcon,
    PencilSquareIcon,
    ChartBarIcon,
    TrashIcon,
    UsersIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    tests: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();

const statusVariant = (status) => ({
    draft: 'slate',
    published: 'success',
    closed: 'warning',
}[status] ?? 'slate');

const togglePublish = (test) => {
    router.patch(route('faculty.class-tests.status', test.id), {}, { preserveScroll: true });
};

const remove = async (test) => {
    const ok = await confirm({
        title: 'Delete class test',
        message: `Delete "${test.title}"? All attempts and results will be removed.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (ok) {
        router.delete(route('faculty.class-tests.destroy', test.id), { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <Head title="Class Tests" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Class Tests"
                    subtitle="Create timed, auto-graded quizzes for your sections."
                    :icon="ClipboardDocumentListIcon"
                >
                    <template #actions>
                        <Link :href="route('faculty.class-tests.create')" class="ui-btn ui-btn-primary">
                            <PlusIcon class="h-4 w-4" /> New class test
                        </Link>
                    </template>
                </PageHeader>

                <Card padding="p-0">
                    <EmptyState
                        v-if="tests.length === 0"
                        title="No class tests yet"
                        description="Create your first timed quiz for a section you teach."
                        :icon="ClipboardDocumentListIcon"
                    />

                    <div v-else class="divide-y divide-line">
                        <div
                            v-for="test in tests"
                            :key="test.id"
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate font-semibold text-content">{{ test.title }}</h3>
                                    <Badge :variant="statusVariant(test.status)" class="capitalize">{{ test.status }}</Badge>
                                </div>
                                <p class="mt-1 text-sm text-content-muted">
                                    {{ test.course.code }} · Section {{ test.section }}
                                </p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-content-faint">
                                    <span>{{ test.questionCount }} questions · {{ test.totalMarks }} marks</span>
                                    <span class="flex items-center gap-1"><ClockIcon class="h-3.5 w-3.5" /> {{ test.durationMinutes }} min</span>
                                    <span class="flex items-center gap-1"><UsersIcon class="h-3.5 w-3.5" /> {{ test.attemptCount ?? 0 }} attempts</span>
                                </div>
                            </div>

                            <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                                <Link :href="route('faculty.class-tests.results', test.id)" class="ui-btn ui-btn-ghost">
                                    <ChartBarIcon class="h-4 w-4" /> Results
                                </Link>
                                <Link :href="route('faculty.class-tests.edit', test.id)" class="ui-btn ui-btn-ghost">
                                    <PencilSquareIcon class="h-4 w-4" /> Edit
                                </Link>
                                <button type="button" class="ui-btn ui-btn-ghost" @click="togglePublish(test)">
                                    {{ test.status === 'published' ? 'Close' : 'Publish' }}
                                </button>
                                <button type="button" class="ui-btn ui-btn-ghost text-danger-fg" @click="remove(test)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    SparklesIcon,
    FireIcon,
    TrophyIcon,
    StarIcon,
    RectangleStackIcon,
    ClipboardDocumentCheckIcon,
    DocumentCheckIcon,
    PencilSquareIcon,
    PencilIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    badges: { type: Array, default: () => [] },
    earnedCount: { type: Number, default: 0 },
    totalCount: { type: Number, default: 0 },
});

// Enum icon() strings → heroicon components.
const icons = {
    FireIcon,
    TrophyIcon,
    StarIcon,
    RectangleStackIcon,
    ClipboardDocumentCheckIcon,
    DocumentCheckIcon,
    PencilSquareIcon,
    PencilIcon,
    SparklesIcon,
};

const iconFor = (name) => icons[name] ?? SparklesIcon;

// Tier → badge medallion colours (earned state).
const tierClasses = {
    gold: 'bg-amber-100 text-amber-700 ring-amber-300 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30',
    silver: 'bg-slate-100 text-slate-600 ring-slate-300 dark:bg-slate-400/15 dark:text-slate-200 dark:ring-slate-400/30',
    bronze: 'bg-orange-100 text-orange-700 ring-orange-300 dark:bg-orange-500/15 dark:text-orange-300 dark:ring-orange-500/30',
};

const tierBadge = { gold: 'warning', silver: 'neutral', bronze: 'neutral' };

// Group badges by category, preserving first-seen category order.
const grouped = computed(() => {
    const groups = new Map();
    for (const badge of props.badges) {
        if (!groups.has(badge.category)) groups.set(badge.category, []);
        groups.get(badge.category).push(badge);
    }
    return Array.from(groups, ([category, items]) => ({ category, items }));
});

const completionPercent = computed(() =>
    props.totalCount > 0 ? Math.round((props.earnedCount / props.totalCount) * 100) : 0,
);
</script>

<template>
    <div>
        <Head title="Achievements" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <PageHeader
                    title="Achievements"
                    subtitle="Earn badges for streaks, practice, flashcards and academic effort."
                    :icon="SparklesIcon"
                />

                <!-- Progress summary -->
                <Card padding="p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-content-muted">Badges earned</p>
                            <p class="text-2xl font-bold text-content">{{ earnedCount }} <span class="text-base font-normal text-content-muted">/ {{ totalCount }}</span></p>
                        </div>
                        <div class="w-40">
                            <div class="h-2 rounded-full bg-line overflow-hidden">
                                <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${completionPercent}%` }" />
                            </div>
                            <p class="text-xs text-content-faint text-right mt-1">{{ completionPercent }}% complete</p>
                        </div>
                    </div>
                </Card>

                <!-- Badge groups -->
                <section v-for="group in grouped" :key="group.category" class="space-y-3">
                    <h2 class="text-sm font-semibold text-content-muted uppercase tracking-wide">{{ group.category }}</h2>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="badge in group.items"
                            :key="badge.key"
                            padding="p-4"
                            :class="badge.earned ? '' : 'opacity-70'"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="shrink-0 w-12 h-12 rounded-xl grid place-items-center ring-1"
                                    :class="badge.earned ? tierClasses[badge.tier] : 'bg-surface-subtle text-content-faint ring-line'"
                                >
                                    <component :is="badge.earned ? iconFor(badge.icon) : LockClosedIcon" class="w-6 h-6" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-content truncate">{{ badge.title }}</h3>
                                        <Badge v-if="badge.earned" :variant="tierBadge[badge.tier]" class="capitalize">{{ badge.tier }}</Badge>
                                    </div>
                                    <p class="text-sm text-content-muted mt-0.5">{{ badge.description }}</p>

                                    <!-- Progress toward locked badges -->
                                    <div v-if="!badge.earned" class="mt-2">
                                        <div class="h-1.5 rounded-full bg-line overflow-hidden">
                                            <div class="h-full rounded-full bg-primary/60" :style="{ width: `${badge.percent}%` }" />
                                        </div>
                                        <p class="text-xs text-content-faint mt-1">{{ badge.progress }} / {{ badge.threshold }}</p>
                                    </div>
                                    <p v-else class="text-xs text-success-fg mt-2">Unlocked</p>
                                </div>
                            </div>
                        </Card>
                    </div>
                </section>
            </div>
        </AppLayout>
    </div>
</template>

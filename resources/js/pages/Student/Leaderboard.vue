<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { TrophyIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    scope: { type: String, default: 'department' },
    sectionId: { type: [Number, null], default: null },
    sections: { type: Array, default: () => [] },
    rankings: { type: Object, default: () => ({ entries: [], viewerRank: null, totalRanked: 0 }) },
    settings: { type: Object, default: () => ({ optIn: false, alias: '' }) },
});

const scopes = [
    { value: 'department', label: 'Department' },
    { value: 'semester', label: 'Semester' },
    { value: 'section', label: 'Section' },
];

const selectedSection = ref(props.sectionId);
const showSettings = ref(false);

const visit = (query) => {
    router.get(route('leaderboard'), query, { preserveScroll: true, preserveState: false });
};

const changeScope = (scope) => {
    visit({ scope, ...(scope === 'section' && selectedSection.value ? { section_id: selectedSection.value } : {}) });
};

const changeSection = () => {
    visit({ scope: 'section', section_id: selectedSection.value });
};

const settingsForm = useForm({
    leaderboard_opt_in: props.settings.optIn,
    leaderboard_alias: props.settings.alias ?? '',
});

const saveSettings = () => {
    settingsForm.patch(route('leaderboard.settings'), {
        preserveScroll: true,
        onSuccess: () => { showSettings.value = false; },
    });
};

const medal = (rank) => ({ 1: '🥇', 2: '🥈', 3: '🥉' }[rank] ?? null);

const entries = computed(() => props.rankings.entries ?? []);
const viewerRank = computed(() => props.rankings.viewerRank);
const viewerInTable = computed(() => entries.value.some((entry) => entry.isViewer));
</script>

<template>
    <div>
        <Head title="Leaderboard" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <PageHeader
                        title="Leaderboard"
                        subtitle="Earn XP from class tests, assignments and attendance. Opt in to compete."
                        :icon="TrophyIcon"
                    />
                    <button type="button" class="ui-btn-ghost text-sm" @click="showSettings = !showSettings">
                        <Cog6ToothIcon class="w-4 h-4" /> Settings
                    </button>
                </div>

                <!-- Settings -->
                <Card v-if="showSettings">
                    <form @submit.prevent="saveSettings" class="space-y-4">
                        <label class="flex items-center gap-3">
                            <input v-model="settingsForm.leaderboard_opt_in" type="checkbox" />
                            <span class="text-sm text-content">Show me on the leaderboard</span>
                        </label>
                        <div>
                            <label class="ui-label" for="alias">Display alias (optional)</label>
                            <input id="alias" v-model="settingsForm.leaderboard_alias" type="text" maxlength="30" class="ui-input" placeholder="Shown instead of your name" />
                            <p v-if="settingsForm.errors.leaderboard_alias" class="text-xs text-danger-fg mt-1">{{ settingsForm.errors.leaderboard_alias }}</p>
                        </div>
                        <button type="submit" :disabled="settingsForm.processing" class="ui-btn-primary">
                            {{ settingsForm.processing ? 'Saving…' : 'Save settings' }}
                        </button>
                    </form>
                </Card>

                <!-- Scope controls -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-for="option in scopes"
                        :key="option.value"
                        type="button"
                        class="ui-btn-secondary text-sm"
                        :class="scope === option.value ? 'ring-2 ring-primary' : ''"
                        @click="changeScope(option.value)"
                    >
                        {{ option.label }}
                    </button>

                    <select
                        v-if="scope === 'section'"
                        v-model.number="selectedSection"
                        class="ui-input w-auto"
                        @change="changeSection"
                    >
                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.label }}</option>
                    </select>
                </div>

                <!-- Viewer's rank banner -->
                <Card v-if="viewerRank" padding="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-content-muted">Your rank</span>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold text-content">#{{ viewerRank.rank }}</span>
                            <Badge variant="primary">{{ viewerRank.points }} XP</Badge>
                        </div>
                    </div>
                </Card>
                <Card v-else-if="!settings.optIn" padding="p-4">
                    <p class="text-sm text-content-muted">
                        You're not on the leaderboard yet.
                        <button type="button" class="text-primary font-medium" @click="showSettings = true">Opt in</button>
                        to compete.
                    </p>
                </Card>

                <!-- Rankings -->
                <EmptyState
                    v-if="entries.length === 0"
                    title="No ranked students yet"
                    description="Once classmates opt in and earn XP, they'll appear here."
                    :icon="TrophyIcon"
                />

                <Card v-else padding="p-0">
                    <ul class="divide-y divide-line">
                        <li
                            v-for="entry in entries"
                            :key="entry.userId"
                            class="flex items-center gap-4 p-4"
                            :class="entry.isViewer ? 'bg-primary-soft' : ''"
                        >
                            <span class="w-8 text-center font-semibold text-content-muted">
                                {{ medal(entry.rank) ?? `#${entry.rank}` }}
                            </span>
                            <span class="flex-1 min-w-0 font-medium text-content truncate">
                                {{ entry.name }}
                                <Badge v-if="entry.isViewer" variant="primary" class="ml-2">You</Badge>
                            </span>
                            <span class="font-semibold text-content">{{ entry.points }} <span class="text-xs text-content-muted">XP</span></span>
                        </li>
                    </ul>
                </Card>

                <p v-if="entries.length > 0" class="text-xs text-content-faint text-center">
                    Showing top {{ entries.length }} of {{ rankings.totalRanked }} ranked students.
                    <span v-if="viewerRank && !viewerInTable"> Your position (#{{ viewerRank.rank }}) is outside the top list.</span>
                </p>
            </div>
        </AppLayout>
    </div>
</template>

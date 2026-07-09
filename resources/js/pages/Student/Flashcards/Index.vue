<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import { RectangleStackIcon, SparklesIcon, PlusIcon, TrashIcon, PlayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    decks: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const mode = ref('manual'); // 'manual' | 'ai'

const deckForm = useForm({ title: '', description: '', course_id: null });
const aiForm = useForm({ topic: '', title: '', count: 10, difficulty: 'medium', course_id: null });

const createDeck = () => {
    deckForm.post(route('flashcards.store'), { preserveScroll: true, onSuccess: () => deckForm.reset() });
};

const generate = () => {
    aiForm.post(route('flashcards.generate'), { preserveScroll: true });
};

const removeDeck = async (deck) => {
    const ok = await confirm({
        title: 'Delete deck',
        message: `Delete "${deck.title}" and all its cards?`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (ok) {
        router.delete(route('flashcards.destroy', deck.id), { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <Head title="Flashcards" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Flashcards"
                    subtitle="Build decks by hand or generate them with AI, then review with spaced repetition."
                    :icon="RectangleStackIcon"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="h-fit">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="ui-btn-ghost text-xs"
                                    :class="mode === 'manual' ? 'text-primary font-semibold' : ''"
                                    @click="mode = 'manual'"
                                >
                                    New deck
                                </button>
                                <button
                                    type="button"
                                    class="ui-btn-ghost text-xs"
                                    :class="mode === 'ai' ? 'text-primary font-semibold' : ''"
                                    @click="mode = 'ai'"
                                >
                                    <SparklesIcon class="w-4 h-4" /> Generate with AI
                                </button>
                            </div>
                        </template>

                        <form v-if="mode === 'manual'" @submit.prevent="createDeck" class="space-y-4">
                            <div>
                                <label class="ui-label" for="deck-title">Title</label>
                                <input id="deck-title" v-model="deckForm.title" type="text" class="ui-input" placeholder="e.g. OS: Scheduling" />
                                <p v-if="deckForm.errors.title" class="text-xs text-danger-fg mt-1">{{ deckForm.errors.title }}</p>
                            </div>
                            <div>
                                <label class="ui-label" for="deck-desc">Description</label>
                                <textarea id="deck-desc" v-model="deckForm.description" rows="2" class="ui-input resize-none" placeholder="Optional"></textarea>
                            </div>
                            <div>
                                <label class="ui-label" for="deck-course">Course</label>
                                <select id="deck-course" v-model="deckForm.course_id" class="ui-input">
                                    <option :value="null">— No course —</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                </select>
                            </div>
                            <button type="submit" :disabled="deckForm.processing" class="ui-btn-primary w-full">
                                <PlusIcon class="w-4 h-4" /> {{ deckForm.processing ? 'Creating…' : 'Create deck' }}
                            </button>
                        </form>

                        <form v-else @submit.prevent="generate" class="space-y-4">
                            <div>
                                <label class="ui-label" for="ai-topic">Topic</label>
                                <input id="ai-topic" v-model="aiForm.topic" type="text" class="ui-input" placeholder="e.g. TCP/IP handshake" />
                                <p v-if="aiForm.errors.topic" class="text-xs text-danger-fg mt-1">{{ aiForm.errors.topic }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ui-label" for="ai-count">Cards</label>
                                    <input id="ai-count" v-model.number="aiForm.count" type="number" min="1" max="30" class="ui-input" />
                                </div>
                                <div>
                                    <label class="ui-label" for="ai-diff">Difficulty</label>
                                    <select id="ai-diff" v-model="aiForm.difficulty" class="ui-input">
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="ui-label" for="ai-course">Course</label>
                                <select id="ai-course" v-model="aiForm.course_id" class="ui-input">
                                    <option :value="null">— No course —</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                                </select>
                            </div>
                            <button type="submit" :disabled="aiForm.processing" class="ui-btn-primary w-full">
                                <SparklesIcon class="w-4 h-4" /> {{ aiForm.processing ? 'Generating…' : 'Generate deck' }}
                            </button>
                        </form>
                    </Card>

                    <div class="lg:col-span-2 space-y-4">
                        <EmptyState
                            v-if="decks.length === 0"
                            title="No decks yet"
                            description="Create a deck or generate one with AI to start studying."
                            :icon="RectangleStackIcon"
                        />

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Card v-for="deck in decks" :key="deck.id" padding="p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium text-content truncate">{{ deck.title }}</p>
                                        <p v-if="deck.description" class="text-sm text-content-muted line-clamp-2 mt-0.5">{{ deck.description }}</p>
                                    </div>
                                    <button type="button" class="ui-btn-ghost p-1.5 text-content-faint hover:text-danger-fg" @click="removeDeck(deck)">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <Badge v-if="deck.course" variant="primary">{{ deck.course }}</Badge>
                                    <Badge v-if="deck.source === 'ai'" variant="neutral" class="gap-1"><SparklesIcon class="w-3 h-3" /> AI</Badge>
                                    <span class="text-xs text-content-muted">{{ deck.cardCount }} cards</span>
                                    <Badge v-if="deck.dueCount > 0" variant="warning">{{ deck.dueCount }} due</Badge>
                                </div>
                                <Link :href="route('flashcards.show', deck.id)" class="ui-btn-primary w-full mt-4">
                                    <PlayIcon class="w-4 h-4" /> Study
                                </Link>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

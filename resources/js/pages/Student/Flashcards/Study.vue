<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useConfirm } from '@/composables/useConfirm';
import { RectangleStackIcon, ArrowLeftIcon, PlusIcon, TrashIcon, PencilSquareIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    deck: { type: Object, required: true },
});

const { confirm } = useConfirm();

// Local review queue: due cards first, then the rest so a full pass is possible.
const buildQueue = () => {
    const due = props.deck.cards.filter((card) => card.isDue);
    const rest = props.deck.cards.filter((card) => !card.isDue);
    return [...due, ...rest].map((card) => card.id);
};

const queue = ref(buildQueue());
const position = ref(0);
const flipped = ref(false);

const cardsById = computed(() => Object.fromEntries(props.deck.cards.map((card) => [card.id, card])));
const currentCard = computed(() => cardsById.value[queue.value[position.value]] ?? null);
const finished = computed(() => position.value >= queue.value.length);

const ratings = [
    { quality: 0, label: 'Again', variant: 'danger' },
    { quality: 3, label: 'Hard', variant: 'warning' },
    { quality: 4, label: 'Good', variant: 'primary' },
    { quality: 5, label: 'Easy', variant: 'success' },
];

const rate = (quality) => {
    const card = currentCard.value;
    if (!card) return;

    router.post(
        route('flashcards.cards.review', card.id),
        { quality },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                flipped.value = false;
                position.value += 1;
            },
        },
    );
};

const restart = () => {
    queue.value = buildQueue();
    position.value = 0;
    flipped.value = false;
};

// --- Card management --------------------------------------------------------
const editingId = ref(null);
const cardForm = useForm({ front: '', back: '' });

const startCreate = () => {
    editingId.value = null;
    cardForm.reset();
    cardForm.clearErrors();
};

const startEdit = (card) => {
    editingId.value = card.id;
    cardForm.front = card.front;
    cardForm.back = card.back;
    cardForm.clearErrors();
};

const submitCard = () => {
    if (editingId.value) {
        cardForm.patch(route('flashcards.cards.update', editingId.value), { preserveScroll: true, onSuccess: startCreate });
    } else {
        cardForm.post(route('flashcards.cards.store', props.deck.id), { preserveScroll: true, onSuccess: () => cardForm.reset() });
    }
};

const removeCard = async (card) => {
    const ok = await confirm({ title: 'Delete card', message: 'Delete this card?', confirmLabel: 'Delete', variant: 'danger' });
    if (ok) {
        router.delete(route('flashcards.cards.destroy', card.id), { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <Head :title="deck.title" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <PageHeader :title="deck.title" :subtitle="deck.description || 'Review your flashcards.'" :icon="RectangleStackIcon" />
                    <Link :href="route('flashcards.index')" class="ui-btn-ghost text-sm">
                        <ArrowLeftIcon class="w-4 h-4" /> All decks
                    </Link>
                </div>

                <!-- Review -->
                <Card v-if="deck.cards.length > 0">
                    <template v-if="!finished && currentCard">
                        <p class="text-xs text-content-muted mb-3">Card {{ position + 1 }} of {{ queue.length }}</p>
                        <button
                            type="button"
                            class="w-full min-h-48 rounded-lg border border-line bg-surface-muted p-8 text-center flex items-center justify-center transition"
                            @click="flipped = !flipped"
                        >
                            <div>
                                <p class="text-xs uppercase tracking-wide text-content-faint mb-2">{{ flipped ? 'Answer' : 'Question' }}</p>
                                <p class="text-lg text-content whitespace-pre-line">{{ flipped ? currentCard.back : currentCard.front }}</p>
                                <p v-if="!flipped" class="text-xs text-content-faint mt-4">Click to reveal answer</p>
                            </div>
                        </button>

                        <div v-if="flipped" class="grid grid-cols-4 gap-2 mt-4">
                            <button
                                v-for="rating in ratings"
                                :key="rating.quality"
                                type="button"
                                class="ui-btn-secondary"
                                @click="rate(rating.quality)"
                            >
                                {{ rating.label }}
                            </button>
                        </div>
                    </template>

                    <EmptyState
                        v-else
                        title="Session complete"
                        description="You've reviewed every card in the queue."
                        :icon="CheckCircleIcon"
                    >
                        <button type="button" class="ui-btn-primary mt-2" @click="restart">Review again</button>
                    </EmptyState>
                </Card>

                <!-- Manage cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="h-fit">
                        <template #header>
                            <div class="flex items-center justify-between">
                                <h2 class="ui-card-title">{{ editingId ? 'Edit card' : 'Add card' }}</h2>
                                <button v-if="editingId" type="button" class="ui-btn-ghost text-xs" @click="startCreate">
                                    <PlusIcon class="w-4 h-4" /> New
                                </button>
                            </div>
                        </template>
                        <form @submit.prevent="submitCard" class="space-y-4">
                            <div>
                                <label class="ui-label" for="card-front">Front</label>
                                <textarea id="card-front" v-model="cardForm.front" rows="2" class="ui-input resize-none"></textarea>
                                <p v-if="cardForm.errors.front" class="text-xs text-danger-fg mt-1">{{ cardForm.errors.front }}</p>
                            </div>
                            <div>
                                <label class="ui-label" for="card-back">Back</label>
                                <textarea id="card-back" v-model="cardForm.back" rows="3" class="ui-input resize-none"></textarea>
                                <p v-if="cardForm.errors.back" class="text-xs text-danger-fg mt-1">{{ cardForm.errors.back }}</p>
                            </div>
                            <button type="submit" :disabled="cardForm.processing" class="ui-btn-primary w-full">
                                {{ cardForm.processing ? 'Saving…' : (editingId ? 'Save changes' : 'Add card') }}
                            </button>
                        </form>
                    </Card>

                    <div class="lg:col-span-2 space-y-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-content-muted">Cards ({{ deck.cards.length }})</h2>
                        <EmptyState
                            v-if="deck.cards.length === 0"
                            title="No cards"
                            description="Add cards to start studying this deck."
                            :icon="RectangleStackIcon"
                        />
                        <Card v-for="card in deck.cards" :key="card.id" padding="p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-content">{{ card.front }}</p>
                                    <p class="text-sm text-content-muted mt-1">{{ card.back }}</p>
                                    <Badge v-if="card.isDue" variant="warning" class="mt-2">Due</Badge>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" class="ui-btn-ghost p-1.5" @click="startEdit(card)">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="ui-btn-ghost p-1.5 text-content-faint hover:text-danger-fg" @click="removeCard(card)">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

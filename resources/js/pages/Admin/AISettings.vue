<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    CloudIcon,
    CpuChipIcon,
    Cog6ToothIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PlayIcon,
    ChatBubbleLeftRightIcon,
    CircleStackIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            provider: 'mock',
            model: '',
            temperature: 0.7,
            max_tokens: 2048,
            embedding_model: '',
            rag_top_k: 5,
            rag_similarity_threshold: 0.7,
            system_prompt: '',
        }),
    },
    providerStatus: {
        type: Object,
        default: () => ({ active: 'mock', openaiConfigured: false }),
    },
});

const form = useForm({
    temperature: Number(props.settings.temperature),
    max_tokens: Number(props.settings.max_tokens),
    rag_top_k: Number(props.settings.rag_top_k),
    rag_similarity_threshold: Number(props.settings.rag_similarity_threshold),
    system_prompt: props.settings.system_prompt ?? '',
});

const isTesting = ref(false);
const testResult = ref(null);

const save = () => {
    form.patch(route('admin.settings.update'), { preserveScroll: true });
};

const testConnection = async () => {
    isTesting.value = true;
    testResult.value = null;
    try {
        const { data } = await axios.post(route('admin.settings.test'));
        testResult.value = { ok: data.available, ...data };
    } catch (e) {
        testResult.value = { ok: false, message: 'Test request failed.' };
    } finally {
        isTesting.value = false;
    }
};
</script>

<template>
    <div>
        <Head title="AI Settings" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="AI Settings"
                    subtitle="Configure the active AI provider and RAG behavior"
                    :icon="Cog6ToothIcon"
                />

                <!-- Provider status -->
                <Card title="Active Provider" subtitle="Current model backend and connection check" :icon="CloudIcon">
                    <template #actions>
                        <button
                            @click="testConnection"
                            :disabled="isTesting"
                            class="ui-btn-secondary disabled:opacity-50"
                        >
                            <PlayIcon class="h-4 w-4" />
                            {{ isTesting ? 'Testing…' : 'Test Connection' }}
                        </button>
                    </template>

                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="violet" class="capitalize">{{ providerStatus.active }}</Badge>
                        <span
                            v-if="providerStatus.openaiConfigured"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-success-fg"
                        >
                            <CheckCircleIcon class="h-4 w-4" /> OpenAI key configured
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-warning-fg"
                        >
                            <ExclamationTriangleIcon class="h-4 w-4" /> No OpenAI key — using mock provider
                        </span>
                    </div>

                    <div
                        v-if="testResult"
                        :class="`mt-4 flex items-center gap-2 rounded-control p-3 text-sm ${testResult.ok ? 'bg-success-bg text-success-fg' : 'bg-warning-bg text-warning-fg'}`"
                    >
                        <component :is="testResult.ok ? CheckCircleIcon : ExclamationTriangleIcon" class="h-4 w-4 flex-shrink-0" />
                        <span><strong class="capitalize">{{ testResult.provider }}</strong> — {{ testResult.message }}</span>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div class="flex items-start gap-3 rounded-control border border-line bg-bg p-3">
                            <ChatBubbleLeftRightIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                            <div>
                                <div class="text-content-muted">Chat model</div>
                                <div class="font-medium text-content">{{ settings.model || '—' }}</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-control border border-line bg-bg p-3">
                            <CircleStackIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                            <div>
                                <div class="text-content-muted">Embedding model</div>
                                <div class="font-medium text-content">{{ settings.embedding_model || '—' }}</div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Editable settings -->
                <form @submit.prevent="save" class="space-y-6 sm:space-y-8">
                    <Card title="Model Parameters" subtitle="Tune generation behavior" :icon="CpuChipIcon">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <label class="ui-label flex items-center justify-between">
                                    <span>Temperature</span>
                                    <span class="font-mono text-primary">{{ form.temperature }}</span>
                                </label>
                                <input
                                    v-model.number="form.temperature"
                                    type="range" min="0" max="2" step="0.1"
                                    class="ui-range mt-2"
                                />
                                <p v-if="form.errors.temperature" class="mt-1 text-xs text-danger-fg">{{ form.errors.temperature }}</p>
                            </div>
                            <div>
                                <label class="ui-label">Max Tokens</label>
                                <input
                                    v-model.number="form.max_tokens"
                                    type="number" min="1" max="32000"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.max_tokens" class="mt-1 text-xs text-danger-fg">{{ form.errors.max_tokens }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card title="RAG Retrieval" subtitle="Document retrieval and similarity matching" :icon="DocumentTextIcon">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <label class="ui-label">Top K chunks</label>
                                <input
                                    v-model.number="form.rag_top_k"
                                    type="number" min="1" max="20"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.rag_top_k" class="mt-1 text-xs text-danger-fg">{{ form.errors.rag_top_k }}</p>
                            </div>
                            <div>
                                <label class="ui-label flex items-center justify-between">
                                    <span>Similarity Threshold</span>
                                    <span class="font-mono text-primary">{{ form.rag_similarity_threshold }}</span>
                                </label>
                                <input
                                    v-model.number="form.rag_similarity_threshold"
                                    type="range" min="0" max="1" step="0.05"
                                    class="ui-range mt-2"
                                />
                                <p v-if="form.errors.rag_similarity_threshold" class="mt-1 text-xs text-danger-fg">{{ form.errors.rag_similarity_threshold }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card title="System Prompt Override" subtitle="Replace the built-in default system prompt" :icon="ChatBubbleLeftRightIcon">
                        <textarea
                            v-model="form.system_prompt"
                            rows="6"
                            placeholder="Leave blank to use the built-in default system prompt…"
                            class="ui-input resize-none"
                        ></textarea>
                        <p v-if="form.errors.system_prompt" class="mt-1 text-xs text-danger-fg">{{ form.errors.system_prompt }}</p>
                    </Card>

                    <div class="flex items-center justify-end gap-3">
                        <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm font-medium text-success-fg">
                            <CheckCircleIcon class="h-4 w-4" /> Saved.
                        </span>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="ui-btn-primary disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving…' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
.ui-range {
    @apply h-2 w-full cursor-pointer appearance-none rounded-lg bg-line;
}
.ui-range::-webkit-slider-thumb {
    appearance: none;
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
}
.ui-range::-moz-range-thumb {
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}
</style>

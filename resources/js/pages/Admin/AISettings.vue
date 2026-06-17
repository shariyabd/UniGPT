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
    LanguageIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            provider: 'mock',
            model: 'gpt-4o',
            temperature: 0.3,
            max_tokens: 2048,
            embedding_model: '',
            rag_top_k: 5,
            rag_similarity_threshold: 0.7,
            system_prompt: '',
            supported_languages: [
                { code: 'en', name: 'English' },
                { code: 'bn', name: 'Bangla' },
            ],
        }),
    },
    providerStatus: {
        type: Object,
        default: () => ({ active: 'mock', openaiConfigured: false, openaiKeyStored: false }),
    },
    providerOptions: {
        type: Array,
        default: () => [
            { value: 'openai', label: 'OpenAI' },
            { value: 'mock', label: 'Mock (offline / no key)' },
        ],
    },
});

const form = useForm({
    provider: props.settings.provider ?? 'openai',
    model: props.settings.model ?? '',
    embedding_model: props.settings.embedding_model ?? '',
    openai_api_key: '',
    remove_openai_key: false,
    temperature: Number(props.settings.temperature),
    max_tokens: Number(props.settings.max_tokens),
    rag_top_k: Number(props.settings.rag_top_k),
    rag_similarity_threshold: Number(props.settings.rag_similarity_threshold),
    system_prompt: props.settings.system_prompt ?? '',
    supported_languages: (props.settings.supported_languages ?? []).map((l) => ({ code: l.code, name: l.name })),
});

const isTesting = ref(false);
const testResult = ref(null);

const addLanguage = () => {
    form.supported_languages.push({ code: '', name: '' });
};

const removeLanguage = (index) => {
    form.supported_languages.splice(index, 1);
};

const chatModelSuggestions = ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'];
const embeddingModelSuggestions = ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'];

const save = () => {
    form.patch(route('admin.settings.update'), {
        preserveScroll: true,
        // Never keep the typed key in memory after a successful save.
        onSuccess: () => {
            form.openai_api_key = '';
            form.remove_openai_key = false;
        },
    });
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
                    <Card title="Provider & Credentials" subtitle="Choose the AI backend, set the API key and models" :icon="CloudIcon">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <label class="ui-label">Provider</label>
                                <select v-model="form.provider" class="ui-input">
                                    <option v-for="option in providerOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-content-muted">
                                    OpenAI needs an API key. Mock works offline with canned responses.
                                </p>
                                <p v-if="form.errors.provider" class="mt-1 text-xs text-danger-fg">{{ form.errors.provider }}</p>
                            </div>

                            <div>
                                <label class="ui-label">OpenAI API Key</label>
                                <input
                                    v-model="form.openai_api_key"
                                    type="password"
                                    autocomplete="off"
                                    :placeholder="providerStatus.openaiKeyStored ? '•••••••••• (stored — leave blank to keep)' : 'sk-…'"
                                    class="ui-input font-mono"
                                />
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="text-xs text-content-muted">Stored encrypted; never shown again.</p>
                                    <label v-if="providerStatus.openaiKeyStored" class="inline-flex items-center gap-1.5 text-xs text-danger-fg">
                                        <input v-model="form.remove_openai_key" type="checkbox" class="rounded border-line text-danger-fg" />
                                        Remove stored key
                                    </label>
                                </div>
                                <p v-if="form.errors.openai_api_key" class="mt-1 text-xs text-danger-fg">{{ form.errors.openai_api_key }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Chat Model</label>
                                <input
                                    v-model="form.model"
                                    type="text"
                                    list="chat-model-options"
                                    placeholder="gpt-4o"
                                    class="ui-input"
                                />
                                <datalist id="chat-model-options">
                                    <option v-for="m in chatModelSuggestions" :key="m" :value="m" />
                                </datalist>
                                <p v-if="form.errors.model" class="mt-1 text-xs text-danger-fg">{{ form.errors.model }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Embedding Model</label>
                                <input
                                    v-model="form.embedding_model"
                                    type="text"
                                    list="embedding-model-options"
                                    placeholder="text-embedding-3-small"
                                    class="ui-input"
                                />
                                <datalist id="embedding-model-options">
                                    <option v-for="m in embeddingModelSuggestions" :key="m" :value="m" />
                                </datalist>
                                <p v-if="form.errors.embedding_model" class="mt-1 text-xs text-danger-fg">{{ form.errors.embedding_model }}</p>
                            </div>
                        </div>
                    </Card>

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

                    <Card title="Supported Languages" subtitle="Languages students and faculty can ask the AI to respond in" :icon="LanguageIcon">
                        <div class="space-y-3">
                            <div
                                v-for="(language, index) in form.supported_languages"
                                :key="index"
                                class="flex items-end gap-3"
                            >
                                <div class="w-28">
                                    <label class="ui-label">Code</label>
                                    <input
                                        v-model="language.code"
                                        type="text"
                                        placeholder="en"
                                        class="ui-input font-mono"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="ui-label">Name</label>
                                    <input
                                        v-model="language.name"
                                        type="text"
                                        placeholder="English"
                                        class="ui-input"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removeLanguage(index)"
                                    :disabled="form.supported_languages.length <= 1"
                                    class="ui-btn-ghost h-11 w-11 p-0 text-danger-fg disabled:opacity-40"
                                    aria-label="Remove language"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <button type="button" @click="addLanguage" class="ui-btn-secondary">
                                <PlusIcon class="h-4 w-4" /> Add Language
                            </button>

                            <p class="text-xs text-content-muted">
                                Use a short code (e.g. <span class="font-mono">en</span>, <span class="font-mono">bn</span>)
                                and a display name. The first language is the default for new chats.
                            </p>
                            <p v-if="form.errors.supported_languages" class="text-xs text-danger-fg">{{ form.errors.supported_languages }}</p>
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

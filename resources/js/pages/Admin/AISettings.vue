<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CloudIcon,
    CpuChipIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PlayIcon,
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
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 dark:from-purple-900 dark:via-blue-900 dark:to-indigo-900">
                    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">🤖 AI Settings</h1>
                                <p class="text-xl text-white/90">Configure the active AI provider and RAG behavior</p>
                            </div>
                            <Link
                                href="/admin/dashboard"
                                class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all self-start"
                            >
                                ← Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6 space-y-8">
                    <!-- Provider status -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <CloudIcon class="w-5 h-5 text-blue-600" /> Active Provider
                        </h2>
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 capitalize">
                                    {{ providerStatus.active }}
                                </span>
                                <span
                                    v-if="providerStatus.openaiConfigured"
                                    class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400"
                                >
                                    <CheckCircleIcon class="w-4 h-4" /> OpenAI key configured
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-sm text-yellow-600 dark:text-yellow-400"
                                >
                                    <ExclamationTriangleIcon class="w-4 h-4" /> No OpenAI key — using mock provider
                                </span>
                            </div>

                            <button
                                @click="testConnection"
                                :disabled="isTesting"
                                class="ml-auto flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                            >
                                <PlayIcon class="w-4 h-4" />
                                {{ isTesting ? 'Testing…' : 'Test Connection' }}
                            </button>
                        </div>

                        <div
                            v-if="testResult"
                            :class="`mt-4 p-3 rounded-lg text-sm flex items-center gap-2 ${testResult.ok ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400'}`"
                        >
                            <component :is="testResult.ok ? CheckCircleIcon : ExclamationTriangleIcon" class="w-4 h-4" />
                            <span><strong class="capitalize">{{ testResult.provider }}</strong> — {{ testResult.message }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 text-sm">
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                                <div class="text-gray-500 dark:text-gray-400">Chat model</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ settings.model || '—' }}</div>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                                <div class="text-gray-500 dark:text-gray-400">Embedding model</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ settings.embedding_model || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Editable settings -->
                    <form @submit.prevent="save" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-8">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <CpuChipIcon class="w-5 h-5 text-purple-600" /> Model Parameters
                            </h2>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Temperature: {{ form.temperature }}
                                    </label>
                                    <input
                                        v-model.number="form.temperature"
                                        type="range" min="0" max="2" step="0.1"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                    />
                                    <p v-if="form.errors.temperature" class="text-xs text-red-500 mt-1">{{ form.errors.temperature }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Tokens</label>
                                    <input
                                        v-model.number="form.max_tokens"
                                        type="number" min="1" max="32000"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    />
                                    <p v-if="form.errors.max_tokens" class="text-xs text-red-500 mt-1">{{ form.errors.max_tokens }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-green-600" /> RAG Retrieval
                            </h2>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top K chunks</label>
                                    <input
                                        v-model.number="form.rag_top_k"
                                        type="number" min="1" max="20"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    />
                                    <p v-if="form.errors.rag_top_k" class="text-xs text-red-500 mt-1">{{ form.errors.rag_top_k }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Similarity Threshold: {{ form.rag_similarity_threshold }}
                                    </label>
                                    <input
                                        v-model.number="form.rag_similarity_threshold"
                                        type="range" min="0" max="1" step="0.05"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                    />
                                    <p v-if="form.errors.rag_similarity_threshold" class="text-xs text-red-500 mt-1">{{ form.errors.rag_similarity_threshold }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">System Prompt Override</h3>
                            <textarea
                                v-model="form.system_prompt"
                                rows="6"
                                placeholder="Leave blank to use the built-in default system prompt…"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
                            ></textarea>
                            <p v-if="form.errors.system_prompt" class="text-xs text-red-500 mt-1">{{ form.errors.system_prompt }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <span v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">Saved.</span>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving…' : 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
input[type="range"] {
    background: transparent;
    cursor: pointer;
}
input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
}
input[type="range"]::-moz-range-thumb {
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}
</style>

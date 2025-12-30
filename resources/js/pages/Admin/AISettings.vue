<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Cog6ToothIcon,
    CpuChipIcon,
    CloudIcon,
    ShieldCheckIcon,
    BeakerIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    AdjustmentsHorizontalIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ServerIcon,
    BoltIcon,
    EyeIcon,
    PlayIcon,
    StopIcon
} from '@heroicons/vue/24/outline';

// Component state
const activeTab = ref('providers');
const isTestingConnection = ref(false);
const testResults = ref({});
const hasUnsavedChanges = ref(false);

// AI Provider Settings
const aiProviders = ref({
    openai: {
        enabled: true,
        apiKey: 'sk-proj-***********************************',
        organization: 'org-**********************',
        baseUrl: 'https://api.openai.com/v1',
        models: {
            chat: 'gpt-4-turbo-preview',
            embedding: 'text-embedding-ada-002'
        },
        rateLimits: {
            requestsPerMinute: 3500,
            tokensPerMinute: 90000
        },
        timeout: 30,
        retryAttempts: 3,
        status: 'connected'
    },
    gemini: {
        enabled: false,
        apiKey: 'AIza***************************',
        baseUrl: 'https://generativelanguage.googleapis.com/v1',
        models: {
            chat: 'gemini-pro',
            embedding: 'embedding-001'
        },
        rateLimits: {
            requestsPerMinute: 1000,
            tokensPerMinute: 32000
        },
        timeout: 25,
        retryAttempts: 2,
        status: 'disconnected'
    },
    localLlm: {
        enabled: false,
        baseUrl: 'http://localhost:11434',
        models: {
            chat: 'llama2:13b',
            embedding: 'nomic-embed-text'
        },
        timeout: 60,
        retryAttempts: 1,
        status: 'disconnected',
        gpuEnabled: true,
        contextWindow: 4096
    }
});

// Model Parameters
const modelSettings = ref({
    temperature: 0.7,
    maxTokens: 2048,
    topP: 0.9,
    frequencyPenalty: 0.0,
    presencePenalty: 0.0,
    stopSequences: [],
    systemPrompt: `You are UniGPT, an AI academic assistant for university students and faculty. You help with:

1. Academic questions and explanations
2. Course material clarification
3. Research assistance
4. Study planning and guidance

Always be helpful, accurate, and educational in your responses. If you're unsure about something, acknowledge the uncertainty and suggest reliable academic sources.`,
    maxConversationHistory: 10,
    streamingEnabled: true
});

// RAG Configuration
const ragSettings = ref({
    enabled: true,
    chunkSize: 1000,
    chunkOverlap: 200,
    maxChunks: 5,
    similarityThreshold: 0.7,
    retrievalMethod: 'hybrid', // semantic, keyword, hybrid
    rerankingEnabled: true,
    contextWindow: 4000,
    citationFormat: 'apa',
    confidenceThreshold: 0.8,
    fallbackToGeneral: true
});

// Vector Database Settings
const vectorDbSettings = ref({
    provider: 'pinecone', // pinecone, chroma, faiss
    pinecone: {
        apiKey: 'pc-***************************',
        environment: 'us-west1-gcp-free',
        indexName: 'unigpt-documents',
        dimensions: 1536,
        metric: 'cosine'
    },
    chroma: {
        host: 'localhost',
        port: 8000,
        collection: 'university_docs',
        persistDirectory: '/data/chromadb'
    },
    embeddingModel: 'text-embedding-ada-002',
    batchSize: 100,
    indexingSchedule: 'daily'
});

// Content Safety Settings
const safetySettings = ref({
    contentFiltering: {
        enabled: true,
        strictMode: false,
        categories: {
            hate: { enabled: true, threshold: 'medium' },
            harassment: { enabled: true, threshold: 'medium' },
            selfHarm: { enabled: true, threshold: 'high' },
            sexual: { enabled: true, threshold: 'high' },
            violence: { enabled: true, threshold: 'medium' }
        }
    },
    academicMode: {
        enabled: true,
        restrictToAcademicContent: false,
        requireSourceCitation: true,
        blockOffTopicQueries: false
    },
    rateLimiting: {
        perUser: {
            requestsPerHour: 100,
            requestsPerDay: 1000
        },
        perIP: {
            requestsPerMinute: 20,
            requestsPerHour: 200
        }
    },
    moderationWebhook: '',
    logLevel: 'info'
});

// Performance Settings
const performanceSettings = ref({
    caching: {
        enabled: true,
        ttl: 3600, // seconds
        maxSize: '1GB',
        provider: 'redis'
    },
    loadBalancing: {
        enabled: false,
        strategy: 'round_robin', // round_robin, least_connections, weighted
        healthCheckInterval: 30
    },
    monitoring: {
        enabled: true,
        metricsRetention: 30, // days
        alertThresholds: {
            responseTime: 5000, // ms
            errorRate: 0.05, // 5%
            tokensPerMinute: 50000
        }
    }
});

// Prompt Templates
const promptTemplates = ref([
    {
        id: 1,
        name: 'Academic Question',
        category: 'Education',
        template: `Based on the following academic context, please provide a comprehensive answer to the student's question.

Context: {context}

Question: {question}

Please structure your response with:
1. A clear, direct answer
2. Detailed explanation with examples
3. Relevant citations from the provided context
4. Additional study suggestions if applicable`,
        variables: ['context', 'question'],
        isActive: true
    },
    {
        id: 2,
        name: 'Course Material Explanation',
        category: 'Education',
        template: `You are explaining course material to a university student. Use the following context to provide a clear explanation.

Context: {context}
Topic: {topic}
Student Level: {level}

Provide an explanation that:
- Uses appropriate academic language for the student level
- Includes practical examples
- References the source material
- Suggests related concepts to explore`,
        variables: ['context', 'topic', 'level'],
        isActive: true
    }
]);

// Tab options
const tabs = [
    { id: 'providers', label: 'AI Providers', icon: CloudIcon },
    { id: 'models', label: 'Model Settings', icon: CpuChipIcon },
    { id: 'rag', label: 'RAG Configuration', icon: DocumentTextIcon },
    { id: 'vector', label: 'Vector Database', icon: ServerIcon },
    { id: 'safety', label: 'Safety & Moderation', icon: ShieldCheckIcon },
    { id: 'performance', label: 'Performance', icon: BoltIcon },
    { id: 'prompts', label: 'Prompt Templates', icon: ChatBubbleLeftRightIcon }
];

// Utility functions
const getProviderStatus = (provider) => {
    const status = aiProviders.value[provider]?.status || 'disconnected';
    const colors = {
        connected: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        disconnected: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        testing: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
    };
    return colors[status] || colors.disconnected;
};

const getProviderIcon = (status) => {
    const icons = {
        connected: CheckCircleIcon,
        disconnected: ExclamationTriangleIcon,
        testing: InformationCircleIcon
    };
    return icons[status] || ExclamationTriangleIcon;
};

// Actions
const testConnection = async (provider) => {
    isTestingConnection.value = true;
    aiProviders.value[provider].status = 'testing';

    // Simulate API test
    setTimeout(() => {
        const success = Math.random() > 0.3; // 70% success rate
        aiProviders.value[provider].status = success ? 'connected' : 'disconnected';
        testResults.value[provider] = {
            success,
            message: success ? 'Connection successful' : 'Failed to connect - check API key',
            latency: Math.round(Math.random() * 500 + 100),
            timestamp: new Date()
        };
        isTestingConnection.value = false;
    }, 2000);
};

const toggleProvider = (provider) => {
    aiProviders.value[provider].enabled = !aiProviders.value[provider].enabled;
    hasUnsavedChanges.value = true;
};

const saveSettings = () => {
    // Simulate saving
    setTimeout(() => {
        hasUnsavedChanges.value = false;
        alert('AI settings saved successfully!');
    }, 1000);
};

const resetToDefaults = () => {
    if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
        // Reset logic here
        alert('Settings reset to defaults');
        hasUnsavedChanges.value = true;
    }
};

const addPromptTemplate = () => {
    const newTemplate = {
        id: Date.now(),
        name: 'New Template',
        category: 'Custom',
        template: 'Enter your prompt template here...',
        variables: [],
        isActive: false
    };
    promptTemplates.value.push(newTemplate);
    hasUnsavedChanges.value = true;
};

const deleteTemplate = (templateId) => {
    if (confirm('Are you sure you want to delete this template?')) {
        promptTemplates.value = promptTemplates.value.filter(t => t.id !== templateId);
        hasUnsavedChanges.value = true;
    }
};

const testPrompt = (template) => {
    alert(`Testing prompt template: ${template.name}`);
};
</script>

<template>
    <div>
        <Head title="AI Settings" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 dark:from-purple-900 dark:via-blue-900 dark:to-indigo-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    🤖 AI Settings
                                </h1>
                                <p class="text-xl text-white/90">
                                    Configure AI providers, models, and system behavior
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Unsaved Changes Indicator -->
                                <div v-if="hasUnsavedChanges" class="bg-yellow-500/20 backdrop-blur-lg border border-yellow-400/30 rounded-xl px-4 py-2 text-yellow-200">
                                    <div class="flex items-center gap-2">
                                        <ExclamationTriangleIcon class="w-4 h-4" />
                                        <span class="text-sm">Unsaved changes</span>
                                    </div>
                                </div>

                                <Link
                                    href="/admin/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Tab Navigation -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg mb-8 overflow-hidden">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="flex overflow-x-auto">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                                        activeTab === tab.id
                                            ? 'border-purple-600 text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/30'
                                            : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
                                    }`"
                                >
                                    <component :is="tab.icon" class="w-4 h-4" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="p-6">
                            <!-- AI Providers Tab -->
                            <div v-if="activeTab === 'providers'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">AI Provider Configuration</h2>

                                <div class="space-y-6">
                                    <!-- OpenAI -->
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                                    <CloudIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">OpenAI</h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">GPT-4, GPT-3.5 Turbo, Embeddings</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getProviderStatus('openai')}`">
                                                    <component :is="getProviderIcon(aiProviders.openai.status)" class="w-3 h-3 inline mr-1" />
                                                    {{ aiProviders.openai.status }}
                                                </span>
                                                <label class="flex items-center cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        v-model="aiProviders.openai.enabled"
                                                        @change="toggleProvider('openai')"
                                                        class="sr-only"
                                                    />
                                                    <div :class="`relative w-11 h-6 rounded-full transition-colors ${aiProviders.openai.enabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'}`">
                                                        <div :class="`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform ${aiProviders.openai.enabled ? 'translate-x-5' : 'translate-x-0'}`"></div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div v-if="aiProviders.openai.enabled" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    API Key
                                                </label>
                                                <input
                                                    v-model="aiProviders.openai.apiKey"
                                                    type="password"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Organization ID
                                                </label>
                                                <input
                                                    v-model="aiProviders.openai.organization"
                                                    type="text"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Chat Model
                                                </label>
                                                <select
                                                    v-model="aiProviders.openai.models.chat"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                >
                                                    <option value="gpt-4-turbo-preview">GPT-4 Turbo Preview</option>
                                                    <option value="gpt-4">GPT-4</option>
                                                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Embedding Model
                                                </label>
                                                <select
                                                    v-model="aiProviders.openai.models.embedding"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                >
                                                    <option value="text-embedding-ada-002">text-embedding-ada-002</option>
                                                    <option value="text-embedding-3-small">text-embedding-3-small</option>
                                                    <option value="text-embedding-3-large">text-embedding-3-large</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-3 mt-4">
                                            <button
                                                @click="testConnection('openai')"
                                                :disabled="isTestingConnection || !aiProviders.openai.enabled"
                                                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <PlayIcon class="w-4 h-4" />
                                                Test Connection
                                            </button>
                                        </div>

                                        <!-- Test Results -->
                                        <div v-if="testResults.openai" class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                            <div :class="`flex items-center gap-2 text-sm ${testResults.openai.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`">
                                                <component :is="testResults.openai.success ? CheckCircleIcon : ExclamationTriangleIcon" class="w-4 h-4" />
                                                {{ testResults.openai.message }}
                                                <span v-if="testResults.openai.success" class="text-gray-500 dark:text-gray-400">
                                                    ({{ testResults.openai.latency }}ms)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Similar sections for Gemini and Local LLM would follow the same pattern -->
                                    <!-- Gemini Provider (collapsed for brevity) -->
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 opacity-75">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                                    <BeakerIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Google Gemini</h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Gemini Pro, Embeddings</p>
                                                </div>
                                            </div>
                                            <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Coming Soon</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Model Settings Tab -->
                            <div v-if="activeTab === 'models'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Model Parameters</h2>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Basic Parameters -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Parameters</h3>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Temperature: {{ modelSettings.temperature }}
                                            </label>
                                            <input
                                                v-model.number="modelSettings.temperature"
                                                type="range"
                                                min="0"
                                                max="2"
                                                step="0.1"
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                            />
                                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <span>Focused (0)</span>
                                                <span>Balanced (1)</span>
                                                <span>Creative (2)</span>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Max Tokens
                                            </label>
                                            <input
                                                v-model.number="modelSettings.maxTokens"
                                                type="number"
                                                min="1"
                                                max="4096"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Top P: {{ modelSettings.topP }}
                                            </label>
                                            <input
                                                v-model.number="modelSettings.topP"
                                                type="range"
                                                min="0"
                                                max="1"
                                                step="0.05"
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                            />
                                        </div>
                                    </div>

                                    <!-- Advanced Parameters -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Advanced Parameters</h3>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Frequency Penalty: {{ modelSettings.frequencyPenalty }}
                                            </label>
                                            <input
                                                v-model.number="modelSettings.frequencyPenalty"
                                                type="range"
                                                min="-2"
                                                max="2"
                                                step="0.1"
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Presence Penalty: {{ modelSettings.presencePenalty }}
                                            </label>
                                            <input
                                                v-model.number="modelSettings.presencePenalty"
                                                type="range"
                                                min="-2"
                                                max="2"
                                                step="0.1"
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                            />
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    v-model="modelSettings.streamingEnabled"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Streaming</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Prompt -->
                                <div class="mt-8">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Prompt</h3>
                                    <textarea
                                        v-model="modelSettings.systemPrompt"
                                        rows="8"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
                                        placeholder="Enter system prompt..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- RAG Configuration Tab -->
                            <div v-if="activeTab === 'rag'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">RAG System Configuration</h2>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Retrieval Settings -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Retrieval Settings</h3>

                                        <div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    v-model="ragSettings.enabled"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable RAG System</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Chunk Size
                                            </label>
                                            <input
                                                v-model.number="ragSettings.chunkSize"
                                                type="number"
                                                min="100"
                                                max="2000"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Maximum Chunks
                                            </label>
                                            <input
                                                v-model.number="ragSettings.maxChunks"
                                                type="number"
                                                min="1"
                                                max="10"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Similarity Threshold: {{ ragSettings.similarityThreshold }}
                                            </label>
                                            <input
                                                v-model.number="ragSettings.similarityThreshold"
                                                type="range"
                                                min="0"
                                                max="1"
                                                step="0.05"
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                            />
                                        </div>
                                    </div>

                                    <!-- Processing Settings -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Processing Settings</h3>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Retrieval Method
                                            </label>
                                            <select
                                                v-model="ragSettings.retrievalMethod"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            >
                                                <option value="semantic">Semantic Search</option>
                                                <option value="keyword">Keyword Search</option>
                                                <option value="hybrid">Hybrid (Recommended)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    v-model="ragSettings.rerankingEnabled"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Re-ranking</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Citation Format
                                            </label>
                                            <select
                                                v-model="ragSettings.citationFormat"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            >
                                                <option value="apa">APA</option>
                                                <option value="mla">MLA</option>
                                                <option value="chicago">Chicago</option>
                                                <option value="ieee">IEEE</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    v-model="ragSettings.fallbackToGeneral"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fallback to General Knowledge</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional tabs would follow similar patterns... -->
                            <!-- For brevity, I'll show placeholders for the remaining tabs -->

                            <div v-if="activeTab === 'vector'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Vector Database Configuration</h2>
                                <div class="text-center py-16">
                                    <ServerIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-600 dark:text-gray-400">Vector database settings configuration panel</p>
                                </div>
                            </div>

                            <div v-if="activeTab === 'safety'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Safety & Content Moderation</h2>
                                <div class="text-center py-16">
                                    <ShieldCheckIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-600 dark:text-gray-400">Content filtering and safety settings</p>
                                </div>
                            </div>

                            <div v-if="activeTab === 'performance'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Performance Optimization</h2>
                                <div class="text-center py-16">
                                    <BoltIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-600 dark:text-gray-400">Caching, load balancing, and monitoring settings</p>
                                </div>
                            </div>

                            <div v-if="activeTab === 'prompts'">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Prompt Templates</h2>
                                <div class="text-center py-16">
                                    <ChatBubbleLeftRightIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-600 dark:text-gray-400">Manage and customize AI prompt templates</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between">
                                <button
                                    @click="resetToDefaults"
                                    class="px-4 py-2 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium"
                                >
                                    Reset to Defaults
                                </button>

                                <div class="flex items-center gap-3">
                                    <button
                                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="saveSettings"
                                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                    >
                                        Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Custom range slider styles */
input[type="range"] {
    background: transparent;
    cursor: pointer;
}

input[type="range"]::-webkit-slider-track {
    background: #e5e7eb;
    height: 8px;
    border-radius: 4px;
}

.dark input[type="range"]::-webkit-slider-track {
    background: #374151;
}

input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-moz-range-track {
    background: #e5e7eb;
    height: 8px;
    border-radius: 4px;
    border: none;
}

.dark input[type="range"]::-moz-range-track {
    background: #374151;
}

input[type="range"]::-moz-range-thumb {
    background: #8b5cf6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Tab overflow scroll */
nav {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

nav::-webkit-scrollbar {
    display: none;
}
</style>
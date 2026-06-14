<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChatBubbleLeftRightIcon,
    MagnifyingGlassIcon,
    MicrophoneIcon,
    PaperAirplaneIcon,
    BookmarkIcon,
    ShareIcon,
    DocumentTextIcon,
    AcademicCapIcon,
    StarIcon,
    AdjustmentsHorizontalIcon,
    SpeakerWaveIcon,
    ClockIcon,
    UserIcon,
    TagIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    EyeIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ArrowTopRightOnSquareIcon,
    Bars3Icon,
    XMarkIcon,
    SparklesIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    studentContext: { type: Object, default: () => ({}) },
    modes: { type: Array, default: () => [] },
});

// Component state
const messageInput = ref('');
const isTyping = ref(false);
const isRecording = ref(false);
const showSidebar = ref(true);
const showSourcePanel = ref(true);
const currentChatMode = ref('detailed');
const selectedLanguage = ref('en');
const messagesContainer = ref(null);
const searchQuery = ref('');
const selectedChatHistory = ref(null);
const currentMessageId = ref(null);
const currentSessionId = ref(null);

// Student context (from the authenticated user)
const studentContext = ref({
    name: props.studentContext.name ?? 'Student',
    department: props.studentContext.department ?? '',
    semester: props.studentContext.semester ?? '',
    year: props.studentContext.year ?? '',
    currentCourses: props.studentContext.currentCourses ?? []
});

// UI chat modes mapped to backend ChatMode enum values.
const chatModes = [
    { id: 'simple', label: 'Simple', icon: '💬', description: 'Quick, concise answers', backend: 'general' },
    { id: 'detailed', label: 'Detailed', icon: '📚', description: 'In-depth explanations with examples', backend: 'academic' },
    { id: 'exam', label: 'Exam Mode', icon: '🎯', description: 'Exam-focused with key points', backend: 'exam_prep' }
];

const backendMode = computed(() =>
    chatModes.find((m) => m.id === currentChatMode.value)?.backend ?? 'academic'
);

// Current chat messages
const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content: `Hello **${studentContext.value.name}**! 👋

I'm your **UniGPT Academic Assistant**, and I'm here to help you excel in your studies.

**What I can help you with:**
• Course-specific questions and explanations
• Assignment guidance and problem-solving
• Exam preparation and study strategies
• University policies and procedures

I have access to your current courses: **${studentContext.value.currentCourses.join(', ')}**

**How would you like to start?** You can ask me anything about your coursework, or try one of the suggestions below.`,
        timestamp: new Date().toISOString(),
        confidence: 100,
        sources: [],
        contextRelevance: 'profile',
        isExpanded: false,
        saved: false,
        followUpSuggestions: [
            'What are the attendance requirements for CSE?',
            'Explain database normalization with examples',
            'Show me the Machine Learning syllabus',
            'Help with Software Engineering assignment'
        ]
    }
]);

// Chat history (sidebar), from the server.
const chatHistory = ref(props.sessions.map((s) => ({
    id: s.id,
    title: s.title,
    lastMessage: s.lastMessage ?? '',
    timestamp: s.timestamp,
    messageCount: s.messageCount,
    category: (s.mode || 'academic').replace('_', ' '),
    tags: [],
})));

// Filtered chat history
const filteredChatHistory = computed(() => {
    if (!searchQuery.value) return chatHistory.value;

    const query = searchQuery.value.toLowerCase();
    return chatHistory.value.filter(chat =>
        chat.title.toLowerCase().includes(query) ||
        (chat.category || '').toLowerCase().includes(query)
    );
});

// Normalize a server message into the shape this template renders
// (source confidence is 0-100 on the server; the template expects 0-1).
const normalizeMessage = (msg) => ({
    id: msg.id,
    role: msg.role,
    content: msg.content,
    timestamp: msg.timestamp,
    confidence: msg.confidence ?? 100,
    saved: msg.saved ?? false,
    contextRelevance: (msg.sources && msg.sources.length) ? 'course' : null,
    followUpSuggestions: msg.followUpSuggestions ?? [],
    sources: (msg.sources ?? []).map((s) => ({
        ...s,
        confidence: (s.confidence ?? 0) / 100,
        lastUpdated: s.lastUpdated ?? null,
    })),
});

// Current sources (for selected message)
const currentSources = ref([]);

// Utility functions
const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatRelativeTime = (timestamp) => {
    const now = new Date();
    const date = new Date(timestamp);
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;

    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
};

const getConfidenceColor = (confidence) => {
    if (confidence >= 90) return 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800';
    if (confidence >= 75) return 'text-blue-700 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
    if (confidence >= 60) return 'text-yellow-700 bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800';
    return 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800';
};

const getSourceTypeIcon = (type) => {
    const icons = {
        handbook: '📖',
        syllabus: '📋',
        guidelines: '📝',
        policy: '📄',
        schedule: '📅'
    };
    return icons[type] || '📄';
};

// Enhanced markdown-like parsing
const parseMessageContent = (content) => {
    return content
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-gray-900 dark:text-white">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
        .replace(/•\s/g, '<span class="text-blue-600 dark:text-blue-400 font-bold">•</span> ')
        .replace(/\n/g, '<br>');
};

// Actions
const upsertSession = (session) => {
    const entry = {
        id: session.id,
        title: session.title,
        lastMessage: '',
        timestamp: session.timestamp,
        messageCount: session.messageCount,
        category: (session.mode || 'academic').replace('_', ' '),
        tags: [],
    };
    const idx = chatHistory.value.findIndex((c) => c.id === session.id);
    if (idx === -1) chatHistory.value.unshift(entry);
    else chatHistory.value[idx] = { ...chatHistory.value[idx], ...entry };
};

const sendMessage = async () => {
    if (!messageInput.value.trim() || isTyping.value) return;

    const text = messageInput.value.trim();
    messages.value.push({
        id: 'u' + Date.now(),
        role: 'user',
        content: text,
        timestamp: new Date().toISOString()
    });
    messageInput.value = '';
    isTyping.value = true;
    scrollToBottom();

    try {
        const { data } = await axios.post(route('chat.send'), {
            message: text,
            session_id: currentSessionId.value,
            mode: backendMode.value,
        });

        currentSessionId.value = data.session.id;
        selectedChatHistory.value = data.session.id;
        upsertSession(data.session);

        const assistant = normalizeMessage(data.assistantMessage);
        messages.value.push(assistant);
        currentSources.value = assistant.sources;
        currentMessageId.value = assistant.id;
    } catch (e) {
        messages.value.push({
            id: 'e' + Date.now(),
            role: 'assistant',
            content: 'Sorry, something went wrong while generating a response. Please try again.',
            timestamp: new Date().toISOString(),
            confidence: 0,
            sources: [],
            followUpSuggestions: [],
            saved: false,
        });
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

const toggleSaved = async (messageId) => {
    const message = messages.value.find(m => m.id === messageId);
    if (!message || message.role !== 'assistant' || message.saved) return;

    try {
        await axios.post(route('saved.store'), { chat_message_id: messageId });
        message.saved = true;
    } catch (e) {
        // ignore — flash handles errors
    }
};

const selectChatHistory = async (chatId) => {
    selectedChatHistory.value = chatId;
    currentSessionId.value = chatId;

    try {
        const { data } = await axios.get(route('chat.session', chatId));
        messages.value = data.messages.map(normalizeMessage);
        const lastWithSources = [...messages.value].reverse().find(m => m.sources?.length);
        currentSources.value = lastWithSources ? lastWithSources.sources : [];
    } catch (e) {
        // ignore
    }
    scrollToBottom();
};

const startVoiceInput = () => {
    isRecording.value = true;

    // Mock voice recording
    setTimeout(() => {
        isRecording.value = false;
        messageInput.value = "Explain the difference between supervised and unsupervised learning";
    }, 3000);
};

const showSourceDetails = (messageId) => {
    const message = messages.value.find(m => m.id === messageId);
    if (message && message.sources) {
        currentSources.value = message.sources;
        currentMessageId.value = messageId;
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const askFollowUp = (question) => {
    messageInput.value = question;
    sendMessage();
};

const welcomeMessage = ref(null);

const newChat = () => {
    messages.value = welcomeMessage.value ? [welcomeMessage.value] : [];
    currentSources.value = [];
    selectedChatHistory.value = null;
    currentSessionId.value = null;
};

const exportChat = () => {
    const text = messages.value
        .map(m => `${m.role === 'user' ? 'You' : 'UniGPT'}: ${m.content}`)
        .join('\n\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'unigpt-chat.txt';
    a.click();
    URL.revokeObjectURL(url);
};

const copyMessage = (content) => {
    navigator.clipboard.writeText(content);
    // You could add a toast notification here
};

// Auto-resize textarea
const autoResize = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
};

// Lifecycle
onMounted(() => {
    // Capture the seeded welcome message so "New Chat" can restore it.
    welcomeMessage.value = messages.value[0];
    scrollToBottom();

    // Auto-focus input
    nextTick(() => {
        const input = document.getElementById('message-input');
        if (input) input.focus();
    });
});

// Watch for new messages
watch(() => messages.value.length, () => {
    scrollToBottom();
});
</script>

<template>
    <div>
        <Head title="Academic Chat Assistant" />

        <AppLayout>
            <div class="h-[calc(100vh-4rem)] bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950 flex">

                <!-- Left Sidebar - Chat History -->
                <div v-if="showSidebar" class="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col shadow-xl">
                    <!-- Sidebar Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="w-5 h-5 text-blue-600" />
                                Chat History
                            </h2>
                            <button
                                @click="newChat"
                                class="px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm rounded-lg hover:shadow-lg transition-all duration-200 font-medium"
                            >
                                <span class="hidden sm:inline">+ New Chat</span>
                                <span class="sm:hidden">+</span>
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search conversations..."
                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                            />
                        </div>
                    </div>

                    <!-- Chat List -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="p-3 space-y-2">
                            <div
                                v-for="chat in filteredChatHistory"
                                :key="chat.id"
                                @click="selectChatHistory(chat.id)"
                                :class="`p-4 rounded-xl cursor-pointer transition-all duration-200 ${
                                    selectedChatHistory === chat.id
                                        ? 'bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 border-2 border-blue-300 dark:border-blue-700 shadow-md'
                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-2 border-transparent hover:border-gray-200 dark:hover:border-gray-600'
                                }`"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-1">
                                        {{ chat.title }}
                                    </h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                        {{ formatRelativeTime(chat.timestamp) }}
                                    </span>
                                </div>

                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                                    {{ chat.lastMessage }}
                                </p>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-lg">
                                        {{ chat.category }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ chat.messageCount }} msgs
                                    </span>
                                </div>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <span
                                        v-for="tag in chat.tags.slice(0, 3)"
                                        :key="tag"
                                        class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs rounded-full"
                                    >
                                        #{{ tag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Chat Area -->
                <div class="flex-1 flex flex-col min-w-0">
                    <!-- Chat Header -->
                    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Toggle Sidebar -->
                                <button
                                    @click="showSidebar = !showSidebar"
                                    class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors lg:hidden"
                                >
                                    <Bars3Icon v-if="!showSidebar" class="w-5 h-5" />
                                    <XMarkIcon v-else class="w-5 h-5" />
                                </button>

                                <!-- Student Context Badge -->
                                <div class="bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center gap-3">
                                        <AcademicCapIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        <div>
                                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">
                                                {{ studentContext.name }}
                                            </p>
                                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                                {{ studentContext.department }} • {{ studentContext.semester }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chat Mode Selector -->
                                <div class="hidden md:flex bg-gray-100 dark:bg-gray-700 rounded-xl p-1 border border-gray-200 dark:border-gray-600">
                                    <button
                                        v-for="mode in chatModes"
                                        :key="mode.id"
                                        @click="currentChatMode = mode.id"
                                        :class="`px-3 py-2 text-sm rounded-lg transition-all duration-200 ${
                                            currentChatMode === mode.id
                                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-md font-medium'
                                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-600'
                                        }`"
                                        :title="mode.description"
                                    >
                                        {{ mode.icon }} {{ mode.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Chat Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="exportChat"
                                    class="p-2.5 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    title="Export Chat"
                                >
                                    <ShareIcon class="w-5 h-5" />
                                </button>

                                <button
                                    @click="showSourcePanel = !showSourcePanel"
                                    :class="`p-2.5 rounded-xl transition-colors ${
                                        showSourcePanel
                                            ? 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400'
                                            : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    }`"
                                    title="Toggle Sources Panel"
                                >
                                    <DocumentTextIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 space-y-6"
                        style="scroll-behavior: smooth;"
                    >
                        <div v-for="message in messages" :key="message.id" class="space-y-4">
                            <!-- User Message -->
                            <div v-if="message.role === 'user'" class="flex justify-end">
                                <div class="max-w-[75%] bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white rounded-2xl rounded-br-md px-6 py-4 shadow-xl">
                                    <p class="text-sm leading-relaxed">{{ message.content }}</p>
                                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-white/20">
                                        <span class="text-xs text-white/80">
                                            {{ formatTime(message.timestamp) }}
                                        </span>
                                        <UserIcon class="w-4 h-4 text-white/80" />
                                    </div>
                                </div>
                            </div>

                            <!-- AI Assistant Message -->
                            <div v-else class="flex justify-start">
                                <div class="max-w-[90%] bg-white dark:bg-gray-800 rounded-2xl rounded-bl-md shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                    <!-- Message Header -->
                                    <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 via-emerald-600 to-teal-600 rounded-full flex items-center justify-center shadow-lg">
                                                    <SparklesIcon class="w-5 h-5 text-white" />
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                        UniGPT Assistant
                                                        <span class="text-xs text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-full">
                                                            AI
                                                        </span>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatTime(message.timestamp) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Confidence Score & Actions -->
                                            <div class="flex items-center gap-2">
                                                <span :class="`px-3 py-1.5 text-xs font-semibold rounded-full border ${getConfidenceColor(message.confidence)}`">
                                                    <CheckCircleIcon class="w-3 h-3 inline mr-1" />
                                                    {{ message.confidence }}% Confident
                                                </span>

                                                <div class="flex items-center gap-1">
                                                    <button
                                                        @click="copyMessage(message.content)"
                                                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                        title="Copy Message"
                                                    >
                                                        <ClipboardDocumentCheckIcon class="w-4 h-4" />
                                                    </button>

                                                    <button
                                                        @click="toggleSaved(message.id)"
                                                        :class="`p-1.5 rounded-lg transition-colors ${
                                                            message.saved
                                                                ? 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                                : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                                                        }`"
                                                        title="Bookmark Message"
                                                    >
                                                        <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': message.saved }" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Context Relevance Badge -->
                                        <div v-if="message.contextRelevance" class="mt-3">
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-medium">
                                                <InformationCircleIcon class="w-4 h-4" />
                                                {{ message.contextRelevance === 'profile' ? '🎓 Personalized for your profile' : '📚 Based on your current courses' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Content -->
                                    <div class="p-6">
                                        <!-- Enhanced Message Text -->
                                        <div class="prose prose-sm dark:prose-invert max-w-none">
                                            <div
                                                v-html="parseMessageContent(message.content)"
                                                class="text-gray-800 dark:text-gray-200 leading-relaxed"
                                            ></div>
                                        </div>

                                        <!-- Sources Preview -->
                                        <div v-if="message.sources && message.sources.length > 0" class="mt-6 p-4 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-900/50 dark:to-blue-900/20 rounded-xl border border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center justify-between mb-3">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                                    <DocumentTextIcon class="w-4 h-4 text-blue-600" />
                                                    Academic Sources
                                                </p>
                                                <button
                                                    @click="showSourceDetails(message.id)"
                                                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                                >
                                                    View All Sources →
                                                </button>
                                            </div>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="source in message.sources.slice(0, 2)"
                                                    :key="source.id"
                                                    class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-600"
                                                >
                                                    <span class="text-lg">{{ getSourceTypeIcon(source.type) }}</span>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                                                            {{ source.title }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Page {{ source.page }} • {{ source.section }}
                                                        </p>
                                                    </div>
                                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getConfidenceColor(source.confidence * 100)}`">
                                                        {{ Math.round(source.confidence * 100) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Follow-up Suggestions -->
                                        <div v-if="message.followUpSuggestions && message.followUpSuggestions.length > 0" class="mt-6">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                                <ChatBubbleLeftRightIcon class="w-4 h-4 text-indigo-600" />
                                                Continue Learning
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <button
                                                    v-for="suggestion in message.followUpSuggestions"
                                                    :key="suggestion"
                                                    @click="askFollowUp(suggestion)"
                                                    class="text-left px-4 py-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 text-indigo-700 dark:text-indigo-300 text-sm rounded-xl hover:from-indigo-100 hover:to-purple-100 dark:hover:from-indigo-900/40 dark:hover:to-purple-900/40 transition-all duration-200 border border-indigo-200 dark:border-indigo-800 font-medium"
                                                >
                                                    {{ suggestion }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Typing Indicator -->
                        <div v-if="isTyping" class="flex justify-start">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-bl-md px-6 py-4 shadow-xl border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                        <SparklesIcon class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex space-x-1">
                                            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                            UniGPT is analyzing and crafting your response...
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Message Input -->
                    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 shadow-lg">
                        <form @submit.prevent="sendMessage" class="space-y-3">
                            <!-- Input Area -->
                            <div class="flex items-end gap-3">
                                <div class="flex-1 relative">
                                    <textarea
                                        id="message-input"
                                        v-model="messageInput"
                                        :disabled="isTyping"
                                        placeholder="Ask anything about your courses, assignments, syllabus, or university policies..."
                                        rows="1"
                                        class="w-full px-4 py-3 pr-24 border-2 border-gray-300 dark:border-gray-600 rounded-2xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-all shadow-sm"
                                        @keydown.enter.exact.prevent="sendMessage"
                                        @keydown.enter.shift.exact="() => {}"
                                        @input="autoResize"
                                    ></textarea>

                                    <!-- Voice Input Button -->
                                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="startVoiceInput"
                                            :disabled="isTyping || isRecording"
                                            :class="`p-2 rounded-xl transition-all duration-200 ${
                                                isRecording
                                                    ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 animate-pulse scale-110'
                                                    : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:scale-105'
                                            }`"
                                            title="Voice Input"
                                        >
                                            <MicrophoneIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Send Button -->
                                <button
                                    type="submit"
                                    :disabled="!messageInput.trim() || isTyping"
                                    class="p-3 bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white rounded-2xl hover:shadow-lg hover:shadow-blue-500/25 transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none"
                                >
                                    <PaperAirplaneIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <!-- Enhanced Input Controls -->
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1">
                                        <InformationCircleIcon class="w-3 h-3" />
                                        <span>Press <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">Enter</kbd> to send, <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">Shift + Enter</kbd> for new line</span>
                                    </div>
                                    <div v-if="isRecording" class="flex items-center gap-2 text-red-600 dark:text-red-400 font-medium">
                                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                        <span>Recording audio...</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <select
                                        v-model="selectedLanguage"
                                        class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="en">🇺🇸 English</option>
                                        <option value="hi">🇮🇳 हिंदी</option>
                                        <option value="te">🇮🇳 తెలుగు</option>
                                    </select>
                                    <span class="text-blue-600 dark:text-blue-400 font-medium">{{ currentChatMode.toUpperCase() }} Mode</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Right Panel - Sources & Context -->
                <div v-if="showSourcePanel" class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col shadow-xl">
                    <!-- Panel Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <DocumentTextIcon class="w-5 h-5 text-green-600" />
                            Academic Sources
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Verified university documents and references
                        </p>
                    </div>

                    <!-- Sources List -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div v-if="currentSources.length > 0" class="space-y-4">
                            <div
                                v-for="source in currentSources"
                                :key="source.id"
                                class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-600"
                            >
                                <!-- Source Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl flex items-center justify-center">
                                            <span class="text-xl">{{ getSourceTypeIcon(source.type) }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2">
                                                {{ source.title }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ source.section }} • Page {{ source.page }}
                                            </p>
                                        </div>
                                    </div>

                                    <span :class="`px-2 py-1 text-xs font-semibold rounded-full border ${getConfidenceColor(source.confidence * 100)}`">
                                        {{ Math.round(source.confidence * 100) }}%
                                    </span>
                                </div>

                                <!-- Relevant Text Quote -->
                                <div class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-900/50 dark:to-blue-900/20 p-4 rounded-xl mb-3 border-l-4 border-blue-500">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">
                                        "{{ source.relevantText }}"
                                    </p>
                                </div>

                                <!-- Source Meta & Actions -->
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Updated {{ new Date(source.lastUpdated).toLocaleDateString() }}
                                    </span>
                                    <button class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        <EyeIcon class="w-3 h-3" />
                                        View Document
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Default State -->
                        <div v-else class="text-center py-16">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <DocumentTextIcon class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">No Sources Selected</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                Ask a question to see the academic sources and references that UniGPT uses to provide accurate answers.
                            </p>
                        </div>

                        <!-- Enhanced Student Course Context -->
                        <div class="mt-6 p-4 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <AcademicCapIcon class="w-5 h-5 text-blue-600" />
                                Your Active Courses
                            </h4>
                            <div class="space-y-2">
                                <div
                                    v-for="course in studentContext.currentCourses"
                                    :key="course"
                                    class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300 bg-white/60 dark:bg-gray-800/60 px-3 py-2 rounded-lg border border-blue-200 dark:border-blue-700"
                                >
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    {{ course }}
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-3 italic">
                                AI responses are tailored to your enrolled courses
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Enhanced scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #94a3b8, #64748b);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #475569, #334155);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #64748b, #475569);
}

/* Enhanced line clamp utilities */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Enhanced prose styling for AI responses */
.prose strong {
    font-weight: 600;
    color: inherit;
}

.prose em {
    font-style: italic;
    color: inherit;
}

/* Enhanced animations */
@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-bounce {
    animation: bounce 1.4s ease-in-out infinite both;
}

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced hover effects */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Keyboard shortcuts styling */
kbd {
    font-family: inherit;
    font-size: inherit;
}

/* Auto-resize textarea */
textarea {
    resize: none;
    min-height: 48px;
    max-height: 120px;
}
</style>
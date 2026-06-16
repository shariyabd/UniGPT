<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
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
    ClipboardDocumentCheckIcon,
    PlusIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    PencilSquareIcon,
    DocumentIcon,
    CalendarDaysIcon,
    ChatBubbleBottomCenterTextIcon,
    LightBulbIcon,
    FlagIcon,
    InboxIcon
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
    { id: 'simple', label: 'Simple', icon: ChatBubbleBottomCenterTextIcon, description: 'Quick, concise answers', backend: 'general' },
    { id: 'detailed', label: 'Detailed', icon: BookOpenIcon, description: 'In-depth explanations with examples', backend: 'academic' },
    { id: 'exam', label: 'Exam Mode', icon: FlagIcon, description: 'Exam-focused with key points', backend: 'exam_prep' }
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
    if (confidence >= 90) return 'text-success-fg bg-success-bg border-line';
    if (confidence >= 75) return 'text-primary bg-primary-soft border-line';
    if (confidence >= 60) return 'text-warning-fg bg-warning-bg border-line';
    return 'text-danger-fg bg-danger-bg border-line';
};

const getSourceTypeIcon = (type) => {
    const icons = {
        handbook: BookOpenIcon,
        syllabus: ClipboardDocumentListIcon,
        guidelines: PencilSquareIcon,
        policy: DocumentIcon,
        schedule: CalendarDaysIcon
    };
    return icons[type] || DocumentIcon;
};

// Enhanced markdown-like parsing
const parseMessageContent = (content) => {
    return content
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-content">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
        .replace(/•\s/g, '<span class="text-content font-bold">•</span> ')
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
        <Head title="AI Chat" />

        <AppLayout>
            <div class="page-container py-6 space-y-6">
                <PageHeader
                    title="AI Chat"
                    subtitle="Your AI academic assistant, grounded in your courses and university documents."
                    :icon="ChatBubbleLeftRightIcon"
                >
                    <template #actions>
                        <button
                            @click="newChat"
                            class="ui-btn-primary"
                        >
                            <PlusIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">New Chat</span>
                        </button>
                        <button
                            @click="exportChat"
                            class="ui-btn-secondary"
                            title="Export Chat"
                            aria-label="Export chat"
                        >
                            <ShareIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Export</span>
                        </button>
                        <button
                            @click="showSourcePanel = !showSourcePanel"
                            :class="[
                                'ui-btn-ghost',
                                showSourcePanel ? 'text-primary' : ''
                            ]"
                            title="Toggle Sources Panel"
                            aria-label="Toggle sources panel"
                        >
                            <DocumentTextIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Sources</span>
                        </button>
                    </template>
                </PageHeader>

                <!-- Chat workspace -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-[calc(100vh-13rem)] min-h-[32rem]">

                    <!-- Left Sidebar - Chat History -->
                    <div
                        v-if="showSidebar"
                        class="hidden lg:flex lg:col-span-3 flex-col ui-card p-0 overflow-hidden"
                    >
                        <!-- Sidebar Header -->
                        <div class="p-4 border-b border-line">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-sm font-semibold text-content flex items-center gap-2">
                                    <ChatBubbleLeftRightIcon class="w-4 h-4 text-content-muted" />
                                    History
                                </h2>
                                <button
                                    @click="newChat"
                                    class="ui-btn-ghost h-8 px-2"
                                    aria-label="New chat"
                                >
                                    <PlusIcon class="w-4 h-4" />
                                    <span class="hidden xl:inline">New</span>
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-content-faint" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search conversations..."
                                    class="ui-input pl-9"
                                    aria-label="Search conversations"
                                />
                            </div>
                        </div>

                        <!-- Chat List -->
                        <div class="flex-1 overflow-y-auto p-3 space-y-2">
                            <div
                                v-for="chat in filteredChatHistory"
                                :key="chat.id"
                                @click="selectChatHistory(chat.id)"
                                :class="[
                                    'p-3 rounded-control cursor-pointer transition-all duration-200',
                                    selectedChatHistory === chat.id
                                        ? 'bg-primary text-white'
                                        : 'hover:bg-primary-soft'
                                ]"
                            >
                                <div class="flex items-start justify-between mb-1.5">
                                    <h3
                                        :class="[
                                            'text-sm font-semibold line-clamp-1',
                                            selectedChatHistory === chat.id ? 'text-white' : 'text-content'
                                        ]"
                                    >
                                        {{ chat.title }}
                                    </h3>
                                    <span
                                        :class="[
                                            'text-xs ml-2 flex-shrink-0',
                                            selectedChatHistory === chat.id ? 'text-white/70' : 'text-content-faint'
                                        ]"
                                    >
                                        {{ formatRelativeTime(chat.timestamp) }}
                                    </span>
                                </div>

                                <p
                                    :class="[
                                        'text-xs line-clamp-2 mb-2',
                                        selectedChatHistory === chat.id ? 'text-white/80' : 'text-content-muted'
                                    ]"
                                >
                                    {{ chat.lastMessage }}
                                </p>

                                <div class="flex items-center justify-between">
                                    <span
                                        :class="[
                                            'ui-badge capitalize',
                                            selectedChatHistory === chat.id
                                                ? 'bg-white/15 text-white'
                                                : 'bg-neutral-bg text-neutral-fg'
                                        ]"
                                    >
                                        {{ chat.category }}
                                    </span>
                                    <span
                                        :class="[
                                            'text-xs',
                                            selectedChatHistory === chat.id ? 'text-white/70' : 'text-content-faint'
                                        ]"
                                    >
                                        {{ chat.messageCount }} msgs
                                    </span>
                                </div>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <span
                                        v-for="tag in chat.tags.slice(0, 3)"
                                        :key="tag"
                                        :class="[
                                            'px-2 py-0.5 text-xs rounded-pill',
                                            selectedChatHistory === chat.id
                                                ? 'bg-white/15 text-white'
                                                : 'bg-neutral-bg text-neutral-fg'
                                        ]"
                                    >
                                        #{{ tag }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="filteredChatHistory.length === 0" class="pt-6">
                                <EmptyState
                                    title="No conversations"
                                    description="Start a new chat to see it here."
                                    :icon="InboxIcon"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Main Chat Area -->
                    <div
                        :class="[
                            'flex flex-col min-w-0 ui-card p-0 overflow-hidden',
                            showSidebar && showSourcePanel ? 'lg:col-span-6' : showSidebar || showSourcePanel ? 'lg:col-span-9' : 'lg:col-span-12'
                        ]"
                    >
                        <!-- Chat Header -->
                        <div class="border-b border-line p-3 sm:p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Toggle Sidebar -->
                                    <button
                                        @click="showSidebar = !showSidebar"
                                        class="ui-btn-ghost h-9 w-9 p-0 lg:hidden"
                                        aria-label="Toggle history"
                                    >
                                        <Bars3Icon v-if="!showSidebar" class="w-5 h-5" />
                                        <XMarkIcon v-else class="w-5 h-5" />
                                    </button>

                                    <!-- Student Context Badge -->
                                    <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-control bg-primary-soft text-primary min-w-0">
                                        <AcademicCapIcon class="w-5 h-5 flex-shrink-0" />
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-content truncate">
                                                {{ studentContext.name }}
                                            </p>
                                            <p class="text-xs text-content-muted truncate">
                                                {{ studentContext.department }} • {{ studentContext.semester }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chat Mode Selector -->
                                <div class="hidden md:flex items-center bg-neutral-bg rounded-control p-1">
                                    <button
                                        v-for="mode in chatModes"
                                        :key="mode.id"
                                        @click="currentChatMode = mode.id"
                                        :class="[
                                            'flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-control transition-all duration-200',
                                            currentChatMode === mode.id
                                                ? 'bg-primary text-white font-medium'
                                                : 'text-content-muted hover:text-content'
                                        ]"
                                        :title="mode.description"
                                    >
                                        <component :is="mode.icon" class="w-4 h-4" />
                                        <span class="hidden lg:inline">{{ mode.label }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-6 bg-bg"
                            style="scroll-behavior: smooth;"
                        >
                            <div v-for="message in messages" :key="message.id" class="space-y-4">
                                <!-- User Message -->
                                <div v-if="message.role === 'user'" class="flex justify-end">
                                    <div class="max-w-[80%] bg-primary text-white rounded-2xl rounded-br-md px-5 py-3.5 shadow-card">
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                        <div class="flex items-center justify-end gap-2 mt-2 text-white/70">
                                            <span class="text-xs">{{ formatTime(message.timestamp) }}</span>
                                            <UserIcon class="w-3.5 h-3.5" />
                                        </div>
                                    </div>
                                </div>

                                <!-- AI Assistant Message -->
                                <div v-else class="flex justify-start">
                                    <div class="max-w-[92%] w-full sm:w-auto ui-card p-0 overflow-hidden">
                                        <!-- Message Header -->
                                        <div class="flex items-center justify-between gap-3 p-4 border-b border-line">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                                    <SparklesIcon class="w-5 h-5" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-content text-sm flex items-center gap-2">
                                                        UniGPT Assistant
                                                        <span class="ui-badge bg-neutral-bg text-neutral-fg">AI</span>
                                                    </p>
                                                    <p class="text-xs text-content-faint">
                                                        {{ formatTime(message.timestamp) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Confidence Score & Actions -->
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span :class="`hidden sm:inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border ${getConfidenceColor(message.confidence)}`">
                                                    <CheckCircleIcon class="w-3.5 h-3.5 mr-1" />
                                                    {{ message.confidence }}%
                                                </span>

                                                <button
                                                    @click="copyMessage(message.content)"
                                                    class="p-1.5 rounded-control text-content-faint hover:text-content hover:bg-neutral-bg transition-colors"
                                                    title="Copy Message"
                                                    aria-label="Copy message"
                                                >
                                                    <ClipboardDocumentCheckIcon class="w-4 h-4" />
                                                </button>

                                                <button
                                                    @click="toggleSaved(message.id)"
                                                    :class="[
                                                        'p-1.5 rounded-control transition-colors',
                                                        message.saved
                                                            ? 'text-warning-fg bg-warning-bg'
                                                            : 'text-content-faint hover:text-warning-fg hover:bg-warning-bg'
                                                    ]"
                                                    title="Bookmark Message"
                                                    aria-label="Bookmark message"
                                                >
                                                    <BookmarkIcon class="w-4 h-4" :class="{ 'fill-current': message.saved }" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Message Content -->
                                        <div class="p-5">
                                            <!-- Context Relevance Badge -->
                                            <div v-if="message.contextRelevance" class="mb-4">
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-success-bg text-success-fg rounded-control text-xs font-medium">
                                                    <component :is="message.contextRelevance === 'profile' ? AcademicCapIcon : BookOpenIcon" class="w-4 h-4" />
                                                    {{ message.contextRelevance === 'profile' ? 'Personalized for your profile' : 'Based on your current courses' }}
                                                </div>
                                            </div>

                                            <!-- Enhanced Message Text -->
                                            <div class="prose prose-sm max-w-none">
                                                <div
                                                    v-html="parseMessageContent(message.content)"
                                                    class="text-content-muted leading-relaxed"
                                                ></div>
                                            </div>

                                            <!-- Sources Preview -->
                                            <div v-if="message.sources && message.sources.length > 0" class="mt-5 p-4 bg-neutral-bg rounded-control">
                                                <div class="flex items-center justify-between mb-3">
                                                    <p class="text-sm font-semibold text-content flex items-center gap-2">
                                                        <DocumentTextIcon class="w-4 h-4 text-content-muted" />
                                                        Academic Sources
                                                    </p>
                                                    <button
                                                        @click="showSourceDetails(message.id)"
                                                        class="text-xs text-primary hover:text-primary-hover font-semibold inline-flex items-center gap-1"
                                                    >
                                                        View All
                                                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                                <div class="space-y-2">
                                                    <div
                                                        v-for="source in message.sources.slice(0, 2)"
                                                        :key="source.id"
                                                        class="flex items-center gap-3 p-2 bg-surface rounded-control border border-line"
                                                    >
                                                        <div class="ui-icon-tile h-8 w-8 bg-primary-soft text-primary flex-shrink-0">
                                                            <component :is="getSourceTypeIcon(source.type)" class="w-4 h-4" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-medium text-content truncate">
                                                                {{ source.title }}
                                                            </p>
                                                            <p class="text-xs text-content-faint">
                                                                Page {{ source.page }} • {{ source.section }}
                                                            </p>
                                                        </div>
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-pill border ${getConfidenceColor(source.confidence * 100)}`">
                                                            {{ Math.round(source.confidence * 100) }}%
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Follow-up Suggestions -->
                                            <div v-if="message.followUpSuggestions && message.followUpSuggestions.length > 0" class="mt-5">
                                                <p class="text-sm font-semibold text-content mb-3 flex items-center gap-2">
                                                    <LightBulbIcon class="w-4 h-4 text-primary" />
                                                    Continue Learning
                                                </p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <button
                                                        v-for="suggestion in message.followUpSuggestions"
                                                        :key="suggestion"
                                                        @click="askFollowUp(suggestion)"
                                                        class="text-left px-4 py-2.5 bg-neutral-bg text-content text-sm rounded-control hover:bg-primary-soft hover:text-primary hover:shadow-card hover:-translate-y-0.5 transition-all duration-200 font-medium"
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
                                <div class="ui-card px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="ui-icon-tile h-8 w-8 bg-primary-soft text-primary flex-shrink-0">
                                            <SparklesIcon class="w-4 h-4" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex space-x-1">
                                                <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                                <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                                <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                            </div>
                                            <span class="text-sm text-content-muted font-medium">
                                                UniGPT is thinking...
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sticky Message Input -->
                        <div class="sticky bottom-0 border-t border-line bg-surface p-3 sm:p-4">
                            <form @submit.prevent="sendMessage" class="space-y-2.5">
                                <!-- Input Area -->
                                <div class="flex items-end gap-2.5">
                                    <div class="flex-1 relative">
                                        <textarea
                                            id="message-input"
                                            v-model="messageInput"
                                            :disabled="isTyping"
                                            placeholder="Ask anything about your courses, assignments, syllabus, or policies..."
                                            rows="1"
                                            class="ui-input pr-12 resize-none"
                                            @keydown.enter.exact.prevent="sendMessage"
                                            @keydown.enter.shift.exact="() => {}"
                                            @input="autoResize"
                                        ></textarea>

                                        <!-- Voice Input Button -->
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2">
                                            <button
                                                type="button"
                                                @click="startVoiceInput"
                                                :disabled="isTyping || isRecording"
                                                :class="[
                                                    'p-2 rounded-control transition-all duration-200',
                                                    isRecording
                                                        ? 'bg-danger-bg text-danger-fg animate-pulse'
                                                        : 'text-content-faint hover:text-content hover:bg-neutral-bg'
                                                ]"
                                                title="Voice Input"
                                                aria-label="Voice input"
                                            >
                                                <MicrophoneIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Send Button -->
                                    <button
                                        type="submit"
                                        :disabled="!messageInput.trim() || isTyping"
                                        class="ui-btn-primary h-11 w-11 p-0 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                        aria-label="Send message"
                                    >
                                        <PaperAirplaneIcon class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Input Controls -->
                                <div class="flex items-center justify-between gap-3 text-xs text-content-muted">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="hidden sm:flex items-center gap-1">
                                            <InformationCircleIcon class="w-3.5 h-3.5" />
                                            <span><kbd class="px-1 py-0.5 bg-neutral-bg rounded">Enter</kbd> to send, <kbd class="px-1 py-0.5 bg-neutral-bg rounded">Shift+Enter</kbd> new line</span>
                                        </span>
                                        <div v-if="isRecording" class="flex items-center gap-1.5 text-danger-fg font-medium">
                                            <span class="w-2 h-2 bg-danger-fg rounded-full animate-pulse"></span>
                                            <span>Recording...</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2.5 flex-shrink-0">
                                        <select
                                            v-model="selectedLanguage"
                                            class="ui-input w-auto text-xs px-2 py-1"
                                            aria-label="Response language"
                                        >
                                            <option value="en">🇺🇸 English</option>
                                            <option value="hi">🇮🇳 हिंदी</option>
                                            <option value="te">🇮🇳 తెలుగు</option>
                                        </select>
                                        <span class="text-content font-semibold uppercase">{{ currentChatMode }}</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Panel - Sources & Context -->
                    <div
                        v-if="showSourcePanel"
                        class="hidden lg:flex lg:col-span-3 flex-col ui-card p-0 overflow-hidden"
                    >
                        <!-- Panel Header -->
                        <div class="p-4 border-b border-line">
                            <h3 class="text-sm font-semibold text-content flex items-center gap-2">
                                <DocumentTextIcon class="w-4 h-4 text-content-muted" />
                                Academic Sources
                            </h3>
                            <p class="text-xs text-content-muted mt-1">
                                Verified university documents and references
                            </p>
                        </div>

                        <!-- Sources List -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <div v-if="currentSources.length > 0" class="space-y-3">
                                <div
                                    v-for="source in currentSources"
                                    :key="source.id"
                                    class="bg-neutral-bg rounded-control p-4 hover:bg-surface hover:shadow-card hover:-translate-y-0.5 transition-all duration-200"
                                >
                                    <!-- Source Header -->
                                    <div class="flex items-start justify-between gap-2 mb-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                                <component :is="getSourceTypeIcon(source.type)" class="w-5 h-5" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-content text-sm line-clamp-2">
                                                    {{ source.title }}
                                                </h4>
                                                <p class="text-xs text-content-faint mt-0.5">
                                                    {{ source.section }} • Page {{ source.page }}
                                                </p>
                                            </div>
                                        </div>

                                        <span :class="`px-2 py-1 text-xs font-semibold rounded-pill border flex-shrink-0 ${getConfidenceColor(source.confidence * 100)}`">
                                            {{ Math.round(source.confidence * 100) }}%
                                        </span>
                                    </div>

                                    <!-- Relevant Text Quote -->
                                    <div class="bg-surface p-3 rounded-control mb-3 border-l-4 border-primary">
                                        <p class="text-sm text-content-muted italic leading-relaxed">
                                            "{{ source.relevantText }}"
                                        </p>
                                    </div>

                                    <!-- Source Meta & Actions -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-content-faint">
                                            Updated {{ new Date(source.lastUpdated).toLocaleDateString() }}
                                        </span>
                                        <button class="flex items-center gap-1 text-xs text-primary hover:text-primary-hover font-semibold">
                                            <EyeIcon class="w-3.5 h-3.5" />
                                            View
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <EmptyState
                                v-else
                                title="No sources selected"
                                description="Ask a question to see the academic sources and references UniGPT uses for accurate answers."
                                :icon="DocumentTextIcon"
                            />

                            <!-- Student Course Context -->
                            <div class="mt-5 p-4 bg-primary-soft rounded-control">
                                <h4 class="font-semibold text-primary text-sm mb-3 flex items-center gap-2">
                                    <AcademicCapIcon class="w-4 h-4" />
                                    Your Active Courses
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="course in studentContext.currentCourses"
                                        :key="course"
                                        class="flex items-center gap-2 text-sm text-content bg-surface px-3 py-2 rounded-control"
                                    >
                                        <span class="w-2 h-2 bg-primary rounded-full flex-shrink-0"></span>
                                        <span class="truncate">{{ course }}</span>
                                    </div>
                                </div>
                                <p class="text-xs text-primary/80 mt-3 italic">
                                    AI responses are tailored to your enrolled courses
                                </p>
                            </div>
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
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Line clamp utilities */
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

/* Prose styling for AI responses */
.prose strong {
    font-weight: 600;
    color: inherit;
}

.prose em {
    font-style: italic;
    color: inherit;
}

/* Typing dot animation */
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

/* Auto-resize textarea */
textarea {
    resize: none;
    min-height: 44px;
    max-height: 120px;
}
</style>

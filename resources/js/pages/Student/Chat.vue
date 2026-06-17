<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
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
    InboxIcon,
    ArrowLeftIcon,
    MapPinIcon,
    EllipsisHorizontalIcon,
    ArchiveBoxIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import { MapPinIcon as MapPinSolidIcon } from '@heroicons/vue/24/solid';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useTheme } from '@/composables/useTheme';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    studentContext: { type: Object, default: () => ({}) },
    modes: { type: Array, default: () => [] },
    supportedLanguages: { type: Array, default: () => [{ code: 'en', name: 'English' }] },
    defaultLanguage: { type: String, default: 'en' },
});

const toast = useToast();
const { confirm } = useConfirm();
// This page renders without the global AppLayout, so initialise the theme here.
const { initTheme } = useTheme();

// Component state
const messageInput = ref('');
const isTyping = ref(false);
const isRecording = ref(false);
// History is inline from lg (≥1024px); sources from xl (≥1280px). Below those
// breakpoints the panels open as overlays, so start them collapsed there to keep
// the chat full-width and focused.
const viewportWidth = typeof window !== 'undefined' ? window.innerWidth : 1280;
const showSidebar = ref(viewportWidth >= 1024);
const showSourcePanel = ref(viewportWidth >= 1280);
const currentChatMode = ref('detailed');
const selectedLanguage = ref(props.defaultLanguage || 'en');
const messagesContainer = ref(null);
const searchQuery = ref('');
const selectedChatHistory = ref(null);
const currentMessageId = ref(null);
const currentSessionId = ref(null);

// Student context (from the authenticated user). currentCourses is a list of
// { id, code, name } objects sourced from the student's enrollments.
const studentContext = ref({
    name: props.studentContext.name ?? 'Student',
    department: props.studentContext.department ?? '',
    semester: props.studentContext.semester ?? '',
    year: props.studentContext.year ?? '',
    currentCourses: props.studentContext.currentCourses ?? []
});

// Human-readable course labels, e.g. "CS301 — Data Structures & Algorithms".
const courseLabels = computed(() =>
    studentContext.value.currentCourses.map((c) =>
        c.code && c.name ? `${c.code} — ${c.name}` : (c.name || c.code || '')
    )
);

// UI chat modes mapped to backend ChatMode enum values.
const chatModes = [
    { id: 'simple', label: 'Simple', icon: ChatBubbleBottomCenterTextIcon, description: 'Quick, concise answers', backend: 'general' },
    { id: 'detailed', label: 'Detailed', icon: BookOpenIcon, description: 'In-depth explanations with examples', backend: 'academic' },
    { id: 'exam', label: 'Exam Mode', icon: FlagIcon, description: 'Exam-focused with key points', backend: 'exam_prep' }
];

const backendMode = computed(() =>
    chatModes.find((m) => m.id === currentChatMode.value)?.backend ?? 'academic'
);

// Build the welcome message. The guiding copy is static, but anything that
// depends on the student's data (their courses, follow-up prompts) is derived
// from the enrollments passed by the server.
const buildWelcomeFollowUps = () => {
    const courses = studentContext.value.currentCourses;
    if (courses.length === 0) {
        return [
            'How do I plan an effective study schedule?',
            'Give me tips to prepare for exams',
            'How can I improve my academic writing?',
            'What are good note-taking strategies?'
        ];
    }

    const suggestions = [];
    const first = courses[0];
    const second = courses[1] ?? courses[0];
    suggestions.push(`Explain a key concept from ${first.name || first.code}`);
    suggestions.push(`Help me prepare for my ${second.code || second.name} exam`);
    suggestions.push('Summarize the topics I should focus on this semester');
    suggestions.push('Give me practice questions for my courses');
    return suggestions;
};

const buildWelcomeContent = () => {
    const coursesLine = courseLabels.value.length
        ? `I can see you're enrolled in: **${courseLabels.value.join(', ')}**`
        : `Once your course enrollments are set up, I'll tailor answers to them.`;

    return `Hello **${studentContext.value.name}**! 👋

I'm your **UniGPT Academic Assistant**, and I'm here to help you excel in your studies.

**What I can help you with:**
• Course-specific questions and explanations
• Assignment guidance and problem-solving
• Exam preparation and study strategies
• University policies and procedures

${coursesLine}

**How would you like to start?** You can ask me anything about your coursework, or try one of the suggestions below.`;
};

// Current chat messages
const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content: buildWelcomeContent(),
        timestamp: new Date().toISOString(),
        confidence: 100,
        sources: [],
        contextRelevance: 'profile',
        isExpanded: false,
        saved: false,
        followUpSuggestions: buildWelcomeFollowUps()
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
    pinned: s.pinned ?? false,
})));

// Filtered chat history (by search)
const filteredChatHistory = computed(() => {
    if (!searchQuery.value) return chatHistory.value;

    const query = searchQuery.value.toLowerCase();
    return chatHistory.value.filter(chat =>
        chat.title.toLowerCase().includes(query) ||
        (chat.category || '').toLowerCase().includes(query)
    );
});

// Pinned conversations float to the top, separated from the rest.
const pinnedChats = computed(() => filteredChatHistory.value.filter((c) => c.pinned));
const regularChats = computed(() => filteredChatHistory.value.filter((c) => !c.pinned));
const orderedChats = computed(() => [...pinnedChats.value, ...regularChats.value]);

const togglePin = async (chat) => {
    const previous = chat.pinned;
    chat.pinned = !chat.pinned; // optimistic
    closeMenu();
    try {
        await axios.patch(route('chat.session.pin', chat.id));
    } catch (e) {
        chat.pinned = previous; // revert on failure
        toast.error('Could not update pin.');
    }
};

// --- Per-item context (3-dot) menu -------------------------------------------
const openMenuChat = ref(null);
const menuPos = ref({ top: 0, left: 0 });

const toggleMenu = (chat, event) => {
    if (openMenuChat.value?.id === chat.id) {
        closeMenu();
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    // Anchor the menu under the button, right-aligned to it.
    menuPos.value = { top: rect.bottom + 4, left: Math.max(8, rect.right - 176) };
    openMenuChat.value = chat;
};

const closeMenu = () => {
    openMenuChat.value = null;
};

// --- Rename (inline) ---------------------------------------------------------
const renamingId = ref(null);
const renameText = ref('');

const startRename = (chat) => {
    closeMenu();
    renamingId.value = chat.id;
    renameText.value = chat.title;
    nextTick(() => {
        const input = document.getElementById('rename-' + chat.id);
        if (input) { input.focus(); input.select(); }
    });
};

const cancelRename = () => {
    renamingId.value = null;
    renameText.value = '';
};

const submitRename = async (chat) => {
    const title = renameText.value.trim();
    if (!title || title === chat.title) { cancelRename(); return; }

    const previous = chat.title;
    chat.title = title; // optimistic
    renamingId.value = null;
    try {
        await axios.patch(route('chat.session.rename', chat.id), { title });
    } catch (e) {
        chat.title = previous;
        toast.error('Could not rename the conversation.');
    }
};

// --- Archive -----------------------------------------------------------------
const archiveChat = async (chat) => {
    closeMenu();
    try {
        await axios.patch(route('chat.session.archive', chat.id));
        chatHistory.value = chatHistory.value.filter((c) => c.id !== chat.id);
        if (currentSessionId.value === chat.id) newChat();
        toast.success('Conversation archived.');
    } catch (e) {
        toast.error('Could not archive the conversation.');
    }
};

// --- Delete ------------------------------------------------------------------
const deleteChat = async (chat) => {
    closeMenu();
    const ok = await confirm({
        title: 'Delete conversation',
        message: `“${chat.title}” will be permanently deleted. This cannot be undone.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });
    if (!ok) return;

    try {
        await axios.delete(route('chat.session.destroy', chat.id));
        chatHistory.value = chatHistory.value.filter((c) => c.id !== chat.id);
        if (currentSessionId.value === chat.id) newChat();
        toast.success('Conversation deleted.');
    } catch (e) {
        toast.error('Could not delete the conversation.');
    }
};

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
        pinned: session.pinned ?? false,
    };
    const idx = chatHistory.value.findIndex((c) => c.id === session.id);
    // Preserve an existing pin when the session is updated by a new message.
    if (idx === -1) chatHistory.value.unshift(entry);
    else chatHistory.value[idx] = { ...chatHistory.value[idx], ...entry, pinned: chatHistory.value[idx].pinned };
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
            language: selectedLanguage.value,
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

// Deep-link support: /chat?session=<id>&message=<id> opens the conversation and
// briefly highlights the referenced message (used by "View in Chat" on /saved).
const highlightedMessageId = ref(null);

const openFromQuery = async () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const session = params.get('session');
    const message = params.get('message');

    // Deep-link from other pages (e.g. "Ask AI" on a material) prefills the composer.
    const prefill = params.get('q');
    if (prefill && !session) {
        messageInput.value = prefill;
        nextTick(() => {
            const box = document.querySelector('textarea');
            if (box) box.focus();
        });
        return;
    }

    if (!session) return;

    await selectChatHistory(Number(session));

    if (message) {
        nextTick(() => {
            const el = document.getElementById('msg-' + message);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            highlightedMessageId.value = Number(message);
            setTimeout(() => { highlightedMessageId.value = null; }, 3500);
        });
    }
};

// Maps an app language code to a BCP-47 locale for the browser speech engine.
const speechLocale = (code) => ({ en: 'en-US', bn: 'bn-BD' }[code] || code || 'en-US');

let recognition = null;

const startVoiceInput = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        toast.info('Voice input is not supported in this browser.');
        return;
    }

    if (isRecording.value && recognition) {
        recognition.stop();
        return;
    }

    recognition = new SpeechRecognition();
    recognition.lang = speechLocale(selectedLanguage.value);
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
        const transcript = event.results?.[0]?.[0]?.transcript;
        if (transcript) {
            messageInput.value = messageInput.value
                ? `${messageInput.value} ${transcript}`
                : transcript;
        }
    };
    recognition.onerror = () => {
        toast.error('Could not capture audio. Please try again.');
    };
    recognition.onend = () => {
        isRecording.value = false;
        recognition = null;
    };

    isRecording.value = true;
    recognition.start();
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
    toast.success('Copied to clipboard');
};

// Auto-resize textarea
const autoResize = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
};

// Lifecycle
onMounted(() => {
    initTheme();
    // Capture the seeded welcome message so "New Chat" can restore it.
    welcomeMessage.value = messages.value[0];
    scrollToBottom();

    // Auto-focus input
    nextTick(() => {
        const input = document.getElementById('message-input');
        if (input) input.focus();
    });

    // Honor a deep link from "View in Chat" on the saved-answers page.
    openFromQuery();
});

// Watch for new messages
watch(() => messages.value.length, () => {
    scrollToBottom();
});
</script>

<template>
    <div>
        <Head title="AI Chat" />

        <!-- Full-screen, focused chat workspace (no global nav) -->
        <div class="flex h-dvh flex-col overflow-hidden bg-bg text-content">
            <!-- Top bar -->
            <header class="flex h-14 flex-shrink-0 items-center gap-1.5 border-b border-line bg-surface px-3 sm:gap-2 sm:px-4">
                <!-- History toggle -->
                <button
                    @click="showSidebar = !showSidebar"
                    class="ui-btn-ghost h-9 w-9 p-0"
                    :title="showSidebar ? 'Hide history' : 'Show history'"
                    aria-label="Toggle history"
                >
                    <Bars3Icon class="h-5 w-5" />
                </button>

                <!-- Back to dashboard -->
                <Link :href="route('dashboard')" class="ui-btn-ghost h-9 px-2" title="Back to dashboard">
                    <ArrowLeftIcon class="h-5 w-5" />
                    <span class="hidden sm:inline">Back</span>
                </Link>

                <!-- Brand + student -->
                <div class="ml-1 flex min-w-0 items-center gap-2.5">
                    <div class="ui-icon-tile h-9 w-9 flex-shrink-0 bg-primary text-white">
                        <SparklesIcon class="h-5 w-5" />
                    </div>
                    <div class="hidden min-w-0 sm:block">
                        <p class="truncate text-sm font-bold leading-tight text-content">UniGPT Assistant</p>
                        <p class="truncate text-xs text-content-faint">
                            {{ studentContext.name }}<span v-if="studentContext.department"> • {{ studentContext.department }}</span><span v-if="studentContext.semester"> • {{ studentContext.semester }}</span>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                    <!-- Mode selector (md+) -->
                    <div class="hidden items-center rounded-control bg-neutral-bg p-1 md:flex">
                        <button
                            v-for="mode in chatModes"
                            :key="mode.id"
                            @click="currentChatMode = mode.id"
                            :class="[
                                'flex items-center gap-1.5 rounded-control px-3 py-1.5 text-sm transition-all duration-200',
                                currentChatMode === mode.id
                                    ? 'bg-primary text-white font-medium'
                                    : 'text-content-muted hover:text-content'
                            ]"
                            :title="mode.description"
                        >
                            <component :is="mode.icon" class="h-4 w-4" />
                            <span class="hidden lg:inline">{{ mode.label }}</span>
                        </button>
                    </div>

                    <!-- Mode selector (mobile) -->
                    <select
                        v-model="currentChatMode"
                        class="ui-input w-auto px-2 py-1.5 text-xs md:hidden"
                        aria-label="Response mode"
                    >
                        <option v-for="mode in chatModes" :key="mode.id" :value="mode.id">{{ mode.label }}</option>
                    </select>

                    <button
                        @click="showSourcePanel = !showSourcePanel"
                        :class="['ui-btn-ghost h-9 w-9 p-0', showSourcePanel ? 'text-primary' : '']"
                        title="Toggle sources"
                        aria-label="Toggle sources panel"
                    >
                        <DocumentTextIcon class="h-5 w-5" />
                    </button>
                    <button
                        @click="exportChat"
                        class="ui-btn-ghost h-9 w-9 p-0"
                        title="Export chat"
                        aria-label="Export chat"
                    >
                        <ShareIcon class="h-5 w-5" />
                    </button>
                    <button @click="newChat" class="ui-btn-primary h-9">
                        <PlusIcon class="h-4 w-4" />
                        <span class="hidden sm:inline">New Chat</span>
                    </button>
                </div>
            </header>

            <!-- Body: history · chat · sources -->
            <div class="relative flex min-h-0 flex-1 overflow-hidden">

                <!-- Mobile scrim for history -->
                <Transition
                    enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                    leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0"
                >
                    <div v-if="showSidebar" class="absolute inset-0 z-20 bg-content/30 backdrop-blur-sm lg:hidden" @click="showSidebar = false"></div>
                </Transition>

                <!-- Left Sidebar - Chat History (collapses to 0 width on lg+ so the chat reclaims the space) -->
                <aside
                    class="absolute inset-y-0 left-0 z-30 flex flex-col overflow-hidden border-r border-line bg-surface transition-all duration-300 ease-in-out lg:static lg:z-auto"
                    :class="showSidebar ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:w-0 lg:translate-x-0 lg:border-r-0'"
                >
                    <div class="flex h-full w-72 flex-shrink-0 flex-col">
                    <!-- Sidebar Header -->
                    <div class="border-b border-line p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-content">
                                <ChatBubbleLeftRightIcon class="h-4 w-4 text-content-muted" />
                                History
                            </h2>
                            <button
                                @click="newChat"
                                class="ui-btn-ghost h-8 px-2"
                                aria-label="New chat"
                            >
                                <PlusIcon class="h-4 w-4" />
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
                    <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
                        <!-- Pinned label -->
                        <p v-if="pinnedChats.length" class="px-2 pb-0.5 pt-1 text-[11px] font-semibold uppercase tracking-wide text-content-faint">
                            Pinned
                        </p>

                        <template v-for="(chat, idx) in orderedChats" :key="chat.id">
                            <!-- Separator between pinned chats and the rest -->
                            <div v-if="pinnedChats.length && idx === pinnedChats.length" class="my-1.5 flex items-center gap-2 px-2">
                                <span class="h-px flex-1 bg-line"></span>
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-content-faint">Chats</span>
                                <span class="h-px flex-1 bg-line"></span>
                            </div>

                            <div
                                @click="renamingId === chat.id ? null : selectChatHistory(chat.id)"
                                :class="[
                                    'group relative flex items-center gap-1 rounded-control px-2.5 py-1.5 transition-colors duration-150',
                                    renamingId === chat.id ? '' : 'cursor-pointer',
                                    (selectedChatHistory === chat.id || (openMenuChat && openMenuChat.id === chat.id)) ? 'bg-primary-soft' : 'hover:bg-primary-soft'
                                ]"
                            >
                                <div class="min-w-0 flex-1">
                                    <!-- Inline rename -->
                                    <input
                                        v-if="renamingId === chat.id"
                                        :id="'rename-' + chat.id"
                                        v-model="renameText"
                                        type="text"
                                        class="w-full rounded-control border border-primary bg-surface px-1.5 py-0.5 text-sm text-content focus:outline-none"
                                        @click.stop
                                        @keydown.enter.prevent="submitRename(chat)"
                                        @keydown.esc.prevent="cancelRename"
                                        @blur="submitRename(chat)"
                                    />
                                    <template v-else>
                                        <p
                                            :class="[
                                                'truncate text-sm font-medium',
                                                selectedChatHistory === chat.id ? 'text-primary' : 'text-content'
                                            ]"
                                        >
                                            {{ chat.title }}
                                        </p>
                                        <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-content-faint">
                                            <span>{{ formatRelativeTime(chat.timestamp) }}</span>
                                            <span>•</span>
                                            <span>{{ chat.messageCount }} msgs</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Hover actions: pin + 3-dot menu -->
                                <div
                                    v-if="renamingId !== chat.id"
                                    class="flex flex-shrink-0 items-center gap-0.5"
                                >
                                    <!-- Pin: visible on hover, or persistently when pinned -->
                                    <button
                                        @click.stop="togglePin(chat)"
                                        :class="[
                                            'rounded-control p-1 transition-all',
                                            chat.pinned
                                                ? 'text-primary'
                                                : 'text-content-faint opacity-0 hover:text-primary focus:opacity-100 group-hover:opacity-100'
                                        ]"
                                        :title="chat.pinned ? 'Unpin' : 'Pin'"
                                        :aria-label="chat.pinned ? 'Unpin conversation' : 'Pin conversation'"
                                    >
                                        <component :is="chat.pinned ? MapPinSolidIcon : MapPinIcon" class="h-4 w-4" />
                                    </button>

                                    <!-- 3-dot menu trigger -->
                                    <button
                                        @click.stop="toggleMenu(chat, $event)"
                                        :class="[
                                            'rounded-control p-1 text-content-faint transition-all hover:text-content focus:opacity-100',
                                            (openMenuChat && openMenuChat.id === chat.id) ? 'opacity-100 text-content' : 'opacity-0 group-hover:opacity-100'
                                        ]"
                                        title="More options"
                                        aria-label="More options"
                                    >
                                        <EllipsisHorizontalIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div v-if="filteredChatHistory.length === 0" class="pt-6">
                            <EmptyState
                                title="No conversations"
                                description="Start a new chat to see it here."
                                :icon="InboxIcon"
                            />
                        </div>
                    </div>

                    <!-- Archived chats entry point -->
                    <div class="flex-shrink-0 border-t border-line p-2">
                        <Link
                            :href="route('chat.archived')"
                            class="flex items-center gap-2 rounded-control px-2.5 py-2 text-sm font-medium text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                        >
                            <ArchiveBoxIcon class="h-4 w-4" />
                            Archived chats
                        </Link>
                    </div>
                    </div>
                    </aside>

                    <!-- Main Chat Area -->
                    <main class="flex min-w-0 flex-1 flex-col bg-bg">
                        <!-- Messages Area -->
                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto"
                            style="scroll-behavior: smooth;"
                        >
                            <div class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6 sm:px-6">
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                :id="'msg-' + message.id"
                                :class="[
                                    'space-y-4 rounded-card transition-all duration-500',
                                    highlightedMessageId === message.id ? 'ring-2 ring-primary ring-offset-2 ring-offset-bg' : ''
                                ]"
                            >
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
                                    <div class="w-full ui-card p-0 overflow-hidden">
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
                        </div>

                        <!-- Composer -->
                        <div class="flex-shrink-0 border-t border-line bg-surface">
                            <div class="mx-auto w-full max-w-3xl px-4 py-3 sm:px-6 sm:py-4">
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
                                            <option
                                                v-for="language in supportedLanguages"
                                                :key="language.code"
                                                :value="language.code"
                                            >
                                                {{ language.name }}
                                            </option>
                                        </select>
                                        <span class="text-content font-semibold uppercase">{{ currentChatMode }}</span>
                                    </div>
                                </div>
                            </form>
                            </div>
                        </div>
                    </main>

                    <!-- Scrim for sources (below xl, where the panel overlays) -->
                    <Transition
                        enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                        leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0"
                    >
                        <div v-if="showSourcePanel" class="absolute inset-0 z-20 bg-content/30 backdrop-blur-sm xl:hidden" @click="showSourcePanel = false"></div>
                    </Transition>

                    <!-- Right Panel - Sources & Context (collapses to 0 width on xl+ so the chat reclaims the space) -->
                    <aside
                        class="absolute inset-y-0 right-0 z-30 flex flex-col overflow-hidden border-l border-line bg-surface transition-all duration-300 ease-in-out xl:static xl:z-auto"
                        :class="showSourcePanel ? 'w-80 translate-x-0' : 'w-80 translate-x-full xl:w-0 xl:translate-x-0 xl:border-l-0'"
                    >
                        <div class="flex h-full w-80 flex-shrink-0 flex-col">
                        <!-- Panel Header -->
                        <div class="flex items-center justify-between p-4 border-b border-line">
                            <div>
                                <h3 class="text-sm font-semibold text-content flex items-center gap-2">
                                    <DocumentTextIcon class="w-4 h-4 text-content-muted" />
                                    Academic Sources
                                </h3>
                                <p class="text-xs text-content-muted mt-1">
                                    Verified university documents and references
                                </p>
                            </div>
                            <button
                                @click="showSourcePanel = false"
                                class="ui-btn-ghost h-8 w-8 p-0"
                                aria-label="Close sources panel"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
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
                                <div v-if="studentContext.currentCourses.length" class="space-y-2">
                                    <div
                                        v-for="course in studentContext.currentCourses"
                                        :key="course.id"
                                        class="flex items-center gap-2 text-sm text-content bg-surface px-3 py-2 rounded-control"
                                    >
                                        <span class="w-2 h-2 bg-primary rounded-full flex-shrink-0"></span>
                                        <span class="truncate">
                                            <span class="font-semibold">{{ course.code }}</span>
                                            <span v-if="course.name" class="text-content-muted"> — {{ course.name }}</span>
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-content-muted">
                                    No active enrollments yet.
                                </p>
                                <p v-if="studentContext.currentCourses.length" class="text-xs text-primary/80 mt-3 italic">
                                    AI responses are tailored to your enrolled courses
                                </p>
                            </div>
                        </div>
                        </div>
                    </aside>

                    <!-- Floating tab to reopen the sources panel once it's collapsed -->
                    <Transition
                        enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-x-2"
                        leave-active-class="transition duration-150" leave-to-class="opacity-0 translate-x-2"
                    >
                        <button
                            v-if="!showSourcePanel"
                            @click="showSourcePanel = true"
                            class="absolute right-0 top-1/2 z-20 flex -translate-y-1/2 items-center justify-center rounded-l-control border border-r-0 border-line bg-surface px-2 py-4 text-content-muted shadow-card transition-colors hover:bg-primary-soft hover:text-primary"
                            title="Show sources"
                            aria-label="Show sources panel"
                        >
                            <DocumentTextIcon class="h-5 w-5" />
                        </button>
                    </Transition>
                </div>

            <!-- Per-item context menu (teleported so the scroll area can't clip it) -->
            <Teleport to="body">
                <div v-if="openMenuChat">
                    <!-- click-away backdrop -->
                    <div class="fixed inset-0 z-40" @click="closeMenu" @contextmenu.prevent="closeMenu"></div>
                    <div
                        class="fixed z-50 w-44 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-card-hover"
                        :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }"
                    >
                        <button
                            @click="startRename(openMenuChat)"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                        >
                            <PencilSquareIcon class="h-4 w-4" /> Rename
                        </button>
                        <button
                            @click="togglePin(openMenuChat)"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                        >
                            <component :is="openMenuChat.pinned ? MapPinSolidIcon : MapPinIcon" class="h-4 w-4" />
                            {{ openMenuChat.pinned ? 'Unpin chat' : 'Pin chat' }}
                        </button>
                        <button
                            @click="archiveChat(openMenuChat)"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary"
                        >
                            <ArchiveBoxIcon class="h-4 w-4" /> Archive
                        </button>
                        <div class="my-1 border-t border-line"></div>
                        <button
                            @click="deleteChat(openMenuChat)"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-danger-fg transition-colors hover:bg-danger-bg"
                        >
                            <TrashIcon class="h-4 w-4" /> Delete
                        </button>
                    </div>
                </div>
            </Teleport>

            <!-- Confirmation modal (this page renders without AppLayout, which usually hosts it) -->
            <ConfirmDialog />
        </div>
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

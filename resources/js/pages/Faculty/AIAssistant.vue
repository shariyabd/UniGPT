<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import EmptyState from '@/components/ui/EmptyState.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useTheme } from '@/composables/useTheme';
import { renderMarkdown } from '@/lib/markdown';
import { postEventStream } from '@/lib/sse';
import {
    SparklesIcon,
    PaperAirplaneIcon,
    PlusIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    EllipsisHorizontalIcon,
    PencilSquareIcon,
    ArchiveBoxIcon,
    TrashIcon,
    InboxIcon,
    LightBulbIcon,
    ClipboardDocumentCheckIcon,
    AcademicCapIcon,
    ClipboardDocumentListIcon,
    DocumentTextIcon,
    BeakerIcon,
    CheckIcon,
    ArrowDownTrayIcon,
    ArrowPathIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline';
import { MapPinIcon as MapPinSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    facultyContext: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { confirm } = useConfirm();
// This page renders without the global AppLayout, so initialise the theme here.
const { initTheme } = useTheme();

// ---------------------------------------------------------------------------
// Faculty context
// ---------------------------------------------------------------------------
const facultyContext = computed(() => ({
    name: props.facultyContext.name ?? 'Professor',
    department: props.facultyContext.department ?? '',
    courses: (props.facultyContext.courses ?? []).map((course) =>
        typeof course === 'string' ? { id: null, code: course, name: course } : course
    ),
}));

const courseIdByName = computed(() =>
    Object.fromEntries(facultyContext.value.courses.map((c) => [c.name, c.id]))
);

// ---------------------------------------------------------------------------
// Layout state — chat is the main focus; both side panels collapse.
// ---------------------------------------------------------------------------
const viewportWidth = typeof window !== 'undefined' ? window.innerWidth : 1280;
const showSidebar = ref(viewportWidth >= 1024);
const showToolsPanel = ref(viewportWidth >= 1280);

// ---------------------------------------------------------------------------
// Chat state
// ---------------------------------------------------------------------------
const messageInput = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);
const searchQuery = ref('');
const selectedChatHistory = ref(null);
const currentSessionId = ref(null);

const buildWelcomeContent = () => `Hello **${facultyContext.value.name}**! 👋

I'm your **AI Teaching Assistant**. Ask me anything about teaching — explaining tough concepts, designing activities, rubrics, lesson planning, or student engagement.

Use the **Teaching Tools** panel to generate ready-to-publish **quizzes** and **assignments** for your courses.`;

const buildWelcomeFollowUps = () => {
    const first = facultyContext.value.courses[0];
    return [
        first ? `Suggest active-learning activities for ${first.name}` : 'Suggest active-learning activities for my course',
        'How do I write a clear grading rubric?',
        'Give me strategies to engage quiet students',
        'Explain a complex topic in simple terms',
    ];
};

const makeWelcome = () => ({
    id: 'welcome',
    role: 'assistant',
    content: buildWelcomeContent(),
    timestamp: new Date().toISOString(),
    followUpSuggestions: buildWelcomeFollowUps(),
});

const messages = ref([makeWelcome()]);
const welcomeMessage = ref(null);

// ---------------------------------------------------------------------------
// Chat history (sidebar)
// ---------------------------------------------------------------------------
const chatHistory = ref(props.sessions.map((s) => ({
    id: s.id,
    title: s.title,
    timestamp: s.timestamp,
    messageCount: s.messageCount,
    pinned: s.pinned ?? false,
})));

const filteredChatHistory = computed(() => {
    if (!searchQuery.value) return chatHistory.value;
    const query = searchQuery.value.toLowerCase();
    return chatHistory.value.filter((chat) => (chat.title || '').toLowerCase().includes(query));
});

const pinnedChats = computed(() => filteredChatHistory.value.filter((c) => c.pinned));
const regularChats = computed(() => filteredChatHistory.value.filter((c) => !c.pinned));
const orderedChats = computed(() => [...pinnedChats.value, ...regularChats.value]);

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------
const formatTime = (timestamp) => new Date(timestamp).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

const formatRelativeTime = (timestamp) => {
    if (!timestamp) return '';
    const now = new Date();
    const date = new Date(timestamp);
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;
    return `${Math.floor(diffInHours / 24)}d ago`;
};

const parseMessageContent = (content) => renderMarkdown(content);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    });
};

const autoResize = (event) => {
    const el = event.target;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
};

const copyMessage = (content) => {
    navigator.clipboard.writeText(content);
    toast.success('Copied to clipboard');
};

// ---------------------------------------------------------------------------
// Chat actions
// ---------------------------------------------------------------------------
const normalizeMessage = (msg) => ({
    id: msg.id,
    role: msg.role,
    content: msg.content,
    timestamp: msg.timestamp,
    followUpSuggestions: msg.followUpSuggestions ?? [],
});

const upsertSession = (session) => {
    const entry = {
        id: session.id,
        title: session.title,
        timestamp: session.timestamp,
        messageCount: session.messageCount,
        pinned: session.pinned ?? false,
    };
    const idx = chatHistory.value.findIndex((c) => c.id === session.id);
    if (idx === -1) chatHistory.value.unshift(entry);
    else chatHistory.value[idx] = { ...chatHistory.value[idx], ...entry, pinned: chatHistory.value[idx].pinned };
};

// The in-flight streaming assistant message (reactive entry in `messages`).
// Non-null hides the "thinking" indicator once live text is rendering.
const streamingDraft = ref(null);

const appendStreamDelta = (text) => {
    if (!streamingDraft.value) {
        messages.value.push({
            id: 's' + Date.now(),
            role: 'assistant',
            content: '',
            streaming: true,
            timestamp: new Date().toISOString(),
            followUpSuggestions: [],
        });
        // Grab the reactive proxy of the entry we just pushed so content
        // mutations re-render as deltas arrive.
        streamingDraft.value = messages.value[messages.value.length - 1];
    }
    streamingDraft.value.content += text;
};

const pushAssistantError = (content) => {
    if (streamingDraft.value) {
        messages.value = messages.value.filter((m) => m !== streamingDraft.value);
        streamingDraft.value = null;
    }
    messages.value.push({
        id: 'e' + Date.now(),
        role: 'assistant',
        content,
        timestamp: new Date().toISOString(),
        followUpSuggestions: [],
    });
};

const sendMessage = async () => {
    if (!messageInput.value.trim() || isTyping.value) return;

    const text = messageInput.value.trim();
    messages.value.push({ id: 'u' + Date.now(), role: 'user', content: text, timestamp: new Date().toISOString() });
    messageInput.value = '';
    isTyping.value = true;
    scrollToBottom();

    let finalized = false;
    let streamError = null;

    try {
        await postEventStream(route('faculty.ai-assistant.stream'), {
            message: text,
            session_id: currentSessionId.value,
        }, {
            onEvent: (event, data) => {
                if (event === 'delta') {
                    appendStreamDelta(data.text);
                    scrollToBottom();
                } else if (event === 'done') {
                    finalized = true;
                    currentSessionId.value = data.session.id;
                    selectedChatHistory.value = data.session.id;
                    upsertSession(data.session);

                    const assistant = normalizeMessage(data.assistantMessage);
                    if (streamingDraft.value) {
                        const idx = messages.value.indexOf(streamingDraft.value);
                        messages.value.splice(idx === -1 ? messages.value.length : idx, idx === -1 ? 0 : 1, assistant);
                        streamingDraft.value = null;
                    } else {
                        messages.value.push(assistant);
                    }
                } else if (event === 'error') {
                    streamError = data.message;
                }
            },
        });

        if (!finalized) {
            pushAssistantError(streamError || 'Sorry, something went wrong while generating a response. Please try again.');
        }
    } catch (e) {
        pushAssistantError('Sorry, something went wrong while generating a response. Please try again.');
    } finally {
        isTyping.value = false;
        streamingDraft.value = null;
        scrollToBottom();
    }
};

const askFollowUp = (question) => {
    messageInput.value = question;
    sendMessage();
};

const selectChatHistory = async (chatId) => {
    selectedChatHistory.value = chatId;
    currentSessionId.value = chatId;
    if (viewportWidth < 1024) showSidebar.value = false;
    try {
        const { data } = await axios.get(route('faculty.ai-assistant.session', chatId));
        messages.value = data.messages.map(normalizeMessage);
    } catch (e) {
        toast.error('Could not open that conversation.');
    }
    scrollToBottom();
};

const newChat = () => {
    messages.value = welcomeMessage.value ? [welcomeMessage.value] : [makeWelcome()];
    selectedChatHistory.value = null;
    currentSessionId.value = null;
    nextTick(() => document.getElementById('message-input')?.focus());
};

// ---------------------------------------------------------------------------
// Per-session menu (pin / rename / archive / delete)
// ---------------------------------------------------------------------------
const openMenuChat = ref(null);
const menuPos = ref({ top: 0, left: 0 });

const toggleMenu = (chat, event) => {
    if (openMenuChat.value?.id === chat.id) { closeMenu(); return; }
    const rect = event.currentTarget.getBoundingClientRect();
    menuPos.value = { top: rect.bottom + 4, left: Math.max(8, rect.right - 176) };
    openMenuChat.value = chat;
};
const closeMenu = () => { openMenuChat.value = null; };

const togglePin = async (chat) => {
    const previous = chat.pinned;
    chat.pinned = !chat.pinned;
    closeMenu();
    try {
        await axios.patch(route('faculty.ai-assistant.session.pin', chat.id));
    } catch (e) {
        chat.pinned = previous;
        toast.error('Could not update pin.');
    }
};

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
const cancelRename = () => { renamingId.value = null; renameText.value = ''; };
const submitRename = async (chat) => {
    const title = renameText.value.trim();
    if (!title || title === chat.title) { cancelRename(); return; }
    const previous = chat.title;
    chat.title = title;
    renamingId.value = null;
    try {
        await axios.patch(route('faculty.ai-assistant.session.rename', chat.id), { title });
    } catch (e) {
        chat.title = previous;
        toast.error('Could not rename the conversation.');
    }
};

const archiveChat = async (chat) => {
    closeMenu();
    try {
        await axios.patch(route('faculty.ai-assistant.session.archive', chat.id));
        chatHistory.value = chatHistory.value.filter((c) => c.id !== chat.id);
        if (currentSessionId.value === chat.id) newChat();
        toast.success('Conversation archived.');
    } catch (e) {
        toast.error('Could not archive the conversation.');
    }
};

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
        await axios.delete(route('faculty.ai-assistant.session.destroy', chat.id));
        chatHistory.value = chatHistory.value.filter((c) => c.id !== chat.id);
        if (currentSessionId.value === chat.id) newChat();
        toast.success('Conversation deleted.');
    } catch (e) {
        toast.error('Could not delete the conversation.');
    }
};

// ===========================================================================
// Teaching tools — Quiz & Assignment generators
// ===========================================================================
const activeTool = ref('quiz');
const isGenerating = ref(false);
const generatedContent = ref(null);
const showPreview = ref(false);
const isPublishing = ref(false);
const newTopic = ref('');
// Whether the published quiz reveals correct answers to students. Faculty always
// see answers in the preview below; this only governs what students receive.
// Defaults to off so students never see answers unless explicitly opted in.
const includeAnswers = ref(false);
// How a generated quiz is published: 'assignment' (text handout, manual grading)
// or 'class_test' (interactive, auto-graded via the Class Test engine).
const publishTarget = ref('assignment');

const difficultyOptions = [
    { value: 'easy', label: 'Easy' },
    { value: 'medium', label: 'Medium' },
    { value: 'hard', label: 'Hard' },
];

const questionTypeOptions = [
    { value: 'multiple-choice', label: 'Multiple Choice' },
    { value: 'true-false', label: 'True / False' },
    { value: 'short-answer', label: 'Short Answer' },
    { value: 'essay', label: 'Essay' },
    { value: 'fill-in-blank', label: 'Fill in the Blank' },
    { value: 'matching', label: 'Matching' },
];

const bloomLevels = [
    { value: 'remember', label: 'Remember' },
    { value: 'understand', label: 'Understand' },
    { value: 'apply', label: 'Apply' },
    { value: 'analyze', label: 'Analyze' },
    { value: 'evaluate', label: 'Evaluate' },
    { value: 'create', label: 'Create' },
];

const questionTypeLabel = (value) => questionTypeOptions.find((t) => t.value === value)?.label ?? value;

const assignmentTypes = [
    { value: 'Project', label: 'Project' },
    { value: 'Research', label: 'Research Paper' },
    { value: 'Lab', label: 'Lab Exercise' },
    { value: 'Case Study', label: 'Case Study' },
    { value: 'Presentation', label: 'Presentation' },
];

const quizForm = ref({
    topic: '', course: '', difficulty: 'medium', questionCount: 10, timeLimit: 30,
    questionTypes: ['multiple-choice', 'true-false'], bloomLevel: 'apply', includeExplanations: true,
});
const assignmentForm = ref({
    title: '', course: '', type: 'Project', topics: [], difficulty: 'medium', duration: '14', points: '100',
    allowGroup: false, includeRubric: true, includeResources: true,
});

const isQuizFormValid = computed(() =>
    quizForm.value.topic && quizForm.value.course && quizForm.value.questionCount > 0 && quizForm.value.questionTypes.length > 0
);
const isAssignmentFormValid = computed(() => assignmentForm.value.title && assignmentForm.value.course && assignmentForm.value.topics.length > 0);

const addTopicFromInput = () => {
    const topic = newTopic.value.trim();
    if (topic && !assignmentForm.value.topics.includes(topic)) assignmentForm.value.topics.push(topic);
    newTopic.value = '';
};
const removeTopic = (topic) => {
    const i = assignmentForm.value.topics.indexOf(topic);
    if (i > -1) assignmentForm.value.topics.splice(i, 1);
};

const KNOWN_QUESTION_TYPES = ['multiple-choice', 'true-false', 'short-answer', 'essay', 'fill-in-blank', 'matching'];

const mapQuizQuestion = (question, index) => {
    const hasOptions = Array.isArray(question.options) && question.options.length > 0;
    let type = (question.type || '').toLowerCase();
    if (!KNOWN_QUESTION_TYPES.includes(type)) {
        type = hasOptions ? 'multiple-choice' : (typeof question.answer === 'boolean' ? 'true-false' : 'short-answer');
    }

    let correctAnswer = null;
    if (type === 'multiple-choice') {
        correctAnswer = (question.options ?? []).findIndex((o) => o === question.answer);
        if (correctAnswer === -1) correctAnswer = typeof question.answer === 'number' ? question.answer : 0;
    } else if (type === 'true-false') {
        correctAnswer = question.answer === true || String(question.answer).toLowerCase() === 'true';
    }

    return {
        id: question.id ?? index + 1,
        type,
        question: question.question,
        options: question.options ?? [],
        correctAnswer,
        // Expected/sample answer text for open-ended types.
        answerText: ['multiple-choice', 'true-false'].includes(type) ? null : String(question.answer ?? ''),
        explanation: question.explanation ?? null,
        points: question.points ?? 1,
    };
};

const generateQuiz = async () => {
    if (!isQuizFormValid.value || isGenerating.value) return;
    isGenerating.value = true;
    try {
        const { data } = await axios.post(route('faculty.ai-assistant.quiz'), {
            topic: quizForm.value.topic,
            course: quizForm.value.course,
            difficulty: quizForm.value.difficulty,
            questionCount: quizForm.value.questionCount,
            questionTypes: quizForm.value.questionTypes,
            bloomLevel: quizForm.value.bloomLevel,
            includeExplanations: quizForm.value.includeExplanations,
        });
        const quiz = data.quiz ?? {};
        const questions = (quiz.questions ?? []).map(mapQuizQuestion);
        generatedContent.value = {
            type: 'quiz',
            title: quiz.title ?? `${quizForm.value.topic} Quiz`,
            course: quizForm.value.course,
            difficulty: quiz.difficulty ?? quizForm.value.difficulty,
            totalQuestions: questions.length,
            timeLimit: quizForm.value.timeLimit,
            instructions: `This quiz covers ${quiz.topic ?? quizForm.value.topic} for ${quizForm.value.course}. ${questions.length} questions, ${quizForm.value.timeLimit} minutes.`,
            questions,
        };
        showPreview.value = true;
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Could not generate the quiz. Please try again.');
    } finally {
        isGenerating.value = false;
    }
};

const mapAssignmentTask = (task, index, sectionPoints) => {
    if (typeof task === 'string') {
        return { title: `Task ${index + 1}`, description: task, points: sectionPoints, requirements: [task] };
    }
    return {
        title: task.title ?? `Task ${index + 1}`,
        description: task.description ?? '',
        points: task.points ?? sectionPoints,
        requirements: task.requirements ?? (task.description ? [task.description] : []),
    };
};

const mapRubric = (rubric) => {
    const total = (rubric ?? []).reduce((sum, item) => sum + (item.points ?? 0), 0);
    return (rubric ?? []).map((item) => ({
        category: item.criterion,
        description: `Worth ${item.points} points`,
        weight: total > 0 ? Math.round(((item.points ?? 0) / total) * 100) : 0,
    }));
};

const generateAssignment = async () => {
    if (!isAssignmentFormValid.value || isGenerating.value) return;
    isGenerating.value = true;
    try {
        const { data } = await axios.post(route('faculty.ai-assistant.assignment'), {
            title: assignmentForm.value.title,
            topics: assignmentForm.value.topics,
            points: parseInt(assignmentForm.value.points),
        });
        const assignment = data.assignment ?? {};
        const totalPoints = assignment.points ?? parseInt(assignmentForm.value.points);
        const tasks = assignment.tasks ?? [];
        const perTaskPoints = tasks.length > 0 ? Math.round(totalPoints / tasks.length) : totalPoints;
        generatedContent.value = {
            type: 'assignment',
            title: assignment.title ?? `${assignmentForm.value.title}`,
            description: assignment.description ?? `A ${assignmentForm.value.type.toLowerCase()} covering ${assignmentForm.value.topics.join(', ')}.`,
            course: assignmentForm.value.course,
            category: assignmentForm.value.type,
            duration: assignmentForm.value.duration,
            totalPoints,
            groupWork: assignmentForm.value.allowGroup,
            dueDate: new Date(Date.now() + parseInt(assignmentForm.value.duration) * 86400000).toLocaleDateString(),
            sections: tasks.map((task, index) => mapAssignmentTask(task, index, perTaskPoints)),
            rubric: assignmentForm.value.includeRubric ? mapRubric(assignment.rubric) : null,
            resources: assignmentForm.value.includeResources ? (assignment.resources ?? null) : null,
        };
        showPreview.value = true;
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Could not generate the assignment. Please try again.');
    } finally {
        isGenerating.value = false;
    }
};

// Re-run generation with the current form values (the modal stays open and
// swaps in the fresh result).
const regenerateContent = () => {
    if (!generatedContent.value || isGenerating.value) return;
    if (generatedContent.value.type === 'quiz') generateQuiz();
    else generateAssignment();
};

// Close the preview and return to the matching tool form to tweak inputs.
const editGenerated = () => {
    if (!generatedContent.value) return;
    activeTool.value = generatedContent.value.type === 'quiz' ? 'quiz' : 'assignment';
    showPreview.value = false;
    showToolsPanel.value = true;
};

const deleteQuestion = (id) => {
    if (!generatedContent.value || generatedContent.value.type !== 'quiz') return;
    generatedContent.value.questions = generatedContent.value.questions.filter((q) => q.id !== id);
    generatedContent.value.totalQuestions = generatedContent.value.questions.length;
};

const clearForms = () => {
    quizForm.value = { topic: '', course: '', difficulty: 'medium', questionCount: 10, timeLimit: 30, questionTypes: ['multiple-choice', 'true-false'], bloomLevel: 'apply', includeExplanations: true };
    assignmentForm.value = { title: '', course: '', type: 'Project', topics: [], difficulty: 'medium', duration: '14', points: '100', allowGroup: false, includeRubric: true, includeResources: true };
    generatedContent.value = null;
    showPreview.value = false;
    includeAnswers.value = false;
    publishTarget.value = 'assignment';
};

// --- Export (browser print → PDF) ---
const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const buildPrintHtml = (content) => {
    const rows = [];
    if (content.type === 'quiz') {
        rows.push(`<p><em>${escapeHtml(content.instructions)}</em></p>`);
        content.questions.forEach((q, i) => {
            rows.push(`<div class="q"><strong>Q${i + 1} (${q.points} pt).</strong> ${escapeHtml(q.question)} <em style="color:#888">[${escapeHtml(q.type)}]</em>`);
            if (q.type === 'multiple-choice') {
                rows.push('<ol type="A">' + q.options.map((o, idx) => `<li${idx === q.correctAnswer ? ' class="correct"' : ''}>${escapeHtml(o)}</li>`).join('') + '</ol>');
            } else if (q.type === 'true-false') {
                rows.push(`<p>Answer: <strong>${q.correctAnswer ? 'True' : 'False'}</strong></p>`);
            } else {
                rows.push(`<p>Expected answer: <strong>${escapeHtml(q.answerText)}</strong></p>`);
            }
            if (q.explanation) rows.push(`<p class="exp">Explanation: ${escapeHtml(q.explanation)}</p>`);
            rows.push('</div>');
        });
    } else {
        rows.push(`<p>${escapeHtml(content.description)}</p><p><strong>Due:</strong> ${escapeHtml(content.dueDate)} · <strong>Points:</strong> ${content.totalPoints}</p>`);
        (content.sections ?? []).forEach((s) => {
            rows.push(`<div class="q"><strong>${escapeHtml(s.title)}</strong> (${s.points} pts)<p>${escapeHtml(s.description)}</p>`);
            if (s.requirements?.length) rows.push('<ul>' + s.requirements.map((r) => `<li>${escapeHtml(r)}</li>`).join('') + '</ul>');
            rows.push('</div>');
        });
        if (content.rubric?.length) {
            rows.push('<h3>Grading Rubric</h3><ul>' + content.rubric.map((c) => `<li>${escapeHtml(c.category)} — ${c.weight}%</li>`).join('') + '</ul>');
        }
    }
    return `<!doctype html><html><head><meta charset="utf-8"><title>${escapeHtml(content.title)}</title>
        <style>body{font:14px/1.5 system-ui,sans-serif;max-width:760px;margin:32px auto;padding:0 16px;color:#111}
        h1{font-size:22px}.q{margin:16px 0;padding:12px;border:1px solid #ddd;border-radius:8px}
        .correct{font-weight:700}.exp{color:#555;font-size:13px}ol,ul{margin:6px 0}</style></head>
        <body><h1>${escapeHtml(content.title)}</h1><p>${escapeHtml(content.course)}</p>${rows.join('')}</body></html>`;
};

const exportGenerated = () => {
    const content = generatedContent.value;
    if (!content) return;
    const win = window.open('', '_blank');
    if (!win) { toast.error('Allow pop-ups to export the document.'); return; }
    win.document.write(buildPrintHtml(content));
    win.document.close();
    win.focus();
    win.print();
};

// --- Publish (persist as a real Assignment on the course) ---
// When `withAnswers` is false the published text is question-only — no correct
// option marker, no answer line, no explanation — so students never see answers.
const quizToText = (content, withAnswers = false) => {
    const lines = [content.instructions, ''];
    content.questions.forEach((q, i) => {
        lines.push(`Q${i + 1} (${q.points} pt) [${q.type}]. ${q.question}`);
        if (q.type === 'multiple-choice') {
            q.options.forEach((o, idx) => lines.push(`  ${String.fromCharCode(65 + idx)}. ${o}${withAnswers && idx === q.correctAnswer ? '  ✓' : ''}`));
        } else if (q.type === 'true-false') {
            if (withAnswers) lines.push(`  Answer: ${q.correctAnswer ? 'True' : 'False'}`);
        } else if (withAnswers && q.answerText) {
            lines.push(`  Expected answer: ${q.answerText}`);
        }
        if (withAnswers && q.explanation) lines.push(`  Explanation: ${q.explanation}`);
        lines.push('');
    });
    return lines.join('\n');
};

const rubricForPublish = (content) => {
    if (!content.rubric?.length) return null;
    return content.rubric.map((c) => ({ criterion: c.category, points: Math.round((c.weight / 100) * content.totalPoints) }));
};

// Convert generated quiz questions into the Class Test engine's question shape.
// Only multiple-choice and true/false are auto-gradable, so other types are
// dropped — the UI tells faculty how many will be included before publishing.
const OPTION_KEYS = ['A', 'B', 'C', 'D', 'E', 'F'];
const quizToClassTestQuestions = (content) => {
    const questions = [];
    (content?.questions ?? []).forEach((q) => {
        if (q.type === 'multiple-choice') {
            const options = (q.options ?? [])
                .map((text) => String(text))
                .filter((text) => text.trim() !== '')
                .slice(0, OPTION_KEYS.length)
                .map((text, i) => ({ key: OPTION_KEYS[i], text }));
            if (options.length < 2) return;
            const idx = Number.isInteger(q.correctAnswer) && q.correctAnswer >= 0 && q.correctAnswer < options.length ? q.correctAnswer : 0;
            questions.push({ type: 'mcq', question_text: q.question, options, correct_answer: options[idx].key, marks: q.points || 1 });
        } else if (q.type === 'true-false') {
            questions.push({ type: 'true_false', question_text: q.question, options: [], correct_answer: q.correctAnswer ? 'true' : 'false', marks: q.points || 1 });
        }
        // short-answer / essay / fill-in-blank / matching are not auto-gradable.
    });
    return questions;
};

// How many of the generated questions can become class-test questions.
const classTestEligibleCount = computed(() =>
    generatedContent.value?.type === 'quiz' ? quizToClassTestQuestions(generatedContent.value).length : 0
);

const publishContent = async () => {
    const content = generatedContent.value;
    if (!content) return;
    const courseId = courseIdByName.value[content.course];
    if (!courseId) { toast.error('Select one of your courses before publishing.'); return; }

    // Quiz → Class Test: hand the questions to the interactive, auto-graded engine.
    if (content.type === 'quiz' && publishTarget.value === 'class_test') {
        const questions = quizToClassTestQuestions(content);
        if (questions.length === 0) {
            toast.error('Class tests support only multiple-choice and true/false questions. Add some, or publish as an assignment.');
            return;
        }
        isPublishing.value = true;
        try {
            const { data } = await axios.post(route('faculty.ai-assistant.publish-class-test'), {
                course_id: courseId,
                title: content.title,
                description: content.instructions,
                duration_minutes: content.timeLimit || 30,
                questions,
            });
            toast.success(data.message ?? 'Published as a class test.');
            showPreview.value = false;
        } catch (e) {
            toast.error(e?.response?.data?.message || 'Could not publish the class test. Please try again.');
        } finally {
            isPublishing.value = false;
        }
        return;
    }

    isPublishing.value = true;
    try {
        const payload = content.type === 'quiz'
            ? {
                course_id: courseId, title: content.title, type: 'quiz',
                description: quizToText(content, includeAnswers.value),
                total_points: content.questions.reduce((sum, q) => sum + (q.points || 1), 0),
                due_at: null, rubric: null,
            }
            : {
                course_id: courseId, title: content.title,
                type: (assignmentForm.value.type || 'project').toLowerCase(),
                description: content.description, total_points: content.totalPoints,
                due_at: new Date(Date.now() + (parseInt(assignmentForm.value.duration) || 14) * 86400000).toISOString(),
                rubric: rubricForPublish(content),
            };
        const { data } = await axios.post(route('faculty.ai-assistant.publish'), payload);
        toast.success(data.message ?? 'Published to the course.');
        showPreview.value = false;
    } catch (e) {
        toast.error('Could not publish. Please try again.');
    } finally {
        isPublishing.value = false;
    }
};

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(() => {
    initTheme();
    welcomeMessage.value = messages.value[0];
    scrollToBottom();

    // Deep link from other pages: /faculty/ai-assistant?tool=quiz|assignment opens
    // the Teaching Tools panel on the requested generator. An optional ?course=
    // pre-selects the course in that generator.
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tool = params.get('tool');
        if (tool === 'quiz' || tool === 'assignment') {
            activeTool.value = tool;
            showToolsPanel.value = true;
            const course = params.get('course');
            if (course) {
                if (tool === 'quiz') quizForm.value.course = course;
                else assignmentForm.value.course = course;
            }
        } else {
            nextTick(() => document.getElementById('message-input')?.focus());
        }
    }
});

watch(() => messages.value.length, scrollToBottom);
</script>

<template>
    <div>
        <Head title="AI Teaching Assistant" />

        <!-- Full-screen, focused chat workspace (no global nav) -->
        <div class="flex h-dvh flex-col overflow-hidden bg-bg text-content">
            <!-- Top bar -->
            <header class="flex h-14 flex-shrink-0 items-center gap-1.5 border-b border-line bg-surface px-3 sm:gap-2 sm:px-4">
                <button @click="showSidebar = !showSidebar" class="ui-btn-ghost h-9 w-9 p-0" :title="showSidebar ? 'Hide history' : 'Show history'" aria-label="Toggle history">
                    <Bars3Icon class="h-5 w-5" />
                </button>

                <Link :href="route('faculty.dashboard')" class="ui-btn-ghost h-9 px-2" title="Back to dashboard">
                    <ArrowLeftIcon class="h-5 w-5" />
                    <span class="hidden sm:inline">Back</span>
                </Link>

                <div class="ml-1 flex min-w-0 items-center gap-2.5">
                    <div class="ui-icon-tile h-9 w-9 flex-shrink-0 bg-primary text-white">
                        <SparklesIcon class="h-5 w-5" />
                    </div>
                    <div class="hidden min-w-0 sm:block">
                        <p class="truncate text-sm font-bold leading-tight text-content">AI Teaching Assistant</p>
                        <p class="truncate text-xs text-content-faint">
                            {{ facultyContext.name }}<span v-if="facultyContext.department"> • {{ facultyContext.department }}</span>
                        </p>
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                    <button
                        @click="showToolsPanel = !showToolsPanel"
                        :class="['ui-btn-ghost h-9 px-2', showToolsPanel ? 'text-primary' : '']"
                        title="Toggle teaching tools"
                        aria-label="Toggle teaching tools"
                    >
                        <BeakerIcon class="h-5 w-5" />
                        <span class="hidden lg:inline">Tools</span>
                    </button>
                    <button @click="newChat" class="ui-btn-primary h-9">
                        <PlusIcon class="h-4 w-4" />
                        <span class="hidden sm:inline">New Chat</span>
                    </button>
                </div>
            </header>

            <!-- Body: history · chat · tools -->
            <div class="relative flex min-h-0 flex-1 overflow-hidden">

                <!-- Mobile scrim for history -->
                <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
                    <div v-if="showSidebar" class="absolute inset-0 z-20 bg-content/30 backdrop-blur-sm lg:hidden" @click="showSidebar = false"></div>
                </Transition>

                <!-- Left Sidebar - Chat History -->
                <aside
                    class="absolute inset-y-0 left-0 z-30 flex flex-col overflow-hidden border-r border-line bg-surface transition-all duration-300 ease-in-out lg:static lg:z-auto"
                    :class="showSidebar ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:w-0 lg:translate-x-0 lg:border-r-0'"
                >
                    <div class="flex h-full w-72 flex-shrink-0 flex-col">
                        <div class="border-b border-line p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h2 class="flex items-center gap-2 text-sm font-semibold text-content">
                                    <ChatBubbleLeftRightIcon class="h-4 w-4 text-content-muted" />
                                    History
                                </h2>
                                <button @click="newChat" class="ui-btn-ghost h-8 px-2" aria-label="New chat">
                                    <PlusIcon class="h-4 w-4" />
                                    <span class="hidden xl:inline">New</span>
                                </button>
                            </div>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-content-faint" />
                                <input v-model="searchQuery" type="text" placeholder="Search conversations..." class="ui-input pl-9" aria-label="Search conversations" />
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
                            <p v-if="pinnedChats.length" class="px-2 pb-0.5 pt-1 text-[11px] font-semibold uppercase tracking-wide text-content-faint">Pinned</p>

                            <template v-for="(chat, idx) in orderedChats" :key="chat.id">
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
                                            <p :class="['truncate text-sm font-medium', selectedChatHistory === chat.id ? 'text-primary' : 'text-content']">{{ chat.title }}</p>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-content-faint">
                                                <span>{{ formatRelativeTime(chat.timestamp) }}</span>
                                                <span>•</span>
                                                <span>{{ chat.messageCount }} msgs</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div v-if="renamingId !== chat.id" class="flex flex-shrink-0 items-center gap-0.5">
                                        <button
                                            @click.stop="togglePin(chat)"
                                            :class="['rounded-control p-1 transition-all', chat.pinned ? 'text-primary' : 'text-content-faint opacity-0 hover:text-primary focus:opacity-100 group-hover:opacity-100']"
                                            :title="chat.pinned ? 'Unpin' : 'Pin'"
                                        >
                                            <component :is="chat.pinned ? MapPinSolidIcon : MapPinIcon" class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click.stop="toggleMenu(chat, $event)"
                                            :class="['rounded-control p-1 text-content-faint transition-all hover:text-content focus:opacity-100', (openMenuChat && openMenuChat.id === chat.id) ? 'opacity-100 text-content' : 'opacity-0 group-hover:opacity-100']"
                                            title="More options"
                                        >
                                            <EllipsisHorizontalIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div v-if="filteredChatHistory.length === 0" class="pt-6">
                                <EmptyState title="No conversations" description="Start a new chat to see it here." :icon="InboxIcon" />
                            </div>
                        </div>

                        <div class="flex-shrink-0 border-t border-line p-2">
                            <Link :href="route('faculty.ai-assistant.archived')" class="flex items-center gap-2 rounded-control px-2.5 py-2 text-sm font-medium text-content-muted transition-colors hover:bg-primary-soft hover:text-primary">
                                <ArchiveBoxIcon class="h-4 w-4" />
                                Archived chats
                            </Link>
                        </div>
                    </div>
                </aside>

                <!-- Main Chat Area -->
                <main class="flex min-w-0 flex-1 flex-col bg-bg">
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto" style="scroll-behavior: smooth;">
                        <div class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6 sm:px-6">
                            <div v-for="message in messages" :key="message.id" class="space-y-4">
                                <!-- User -->
                                <div v-if="message.role === 'user'" class="flex justify-end">
                                    <div class="max-w-[80%] bg-primary text-white rounded-2xl rounded-br-md px-5 py-3.5 shadow-card">
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                        <div class="mt-2 text-right text-xs text-white/70">{{ formatTime(message.timestamp) }}</div>
                                    </div>
                                </div>

                                <!-- Assistant -->
                                <div v-else class="flex justify-start">
                                    <div class="w-full ui-card p-0 overflow-hidden">
                                        <div class="flex items-center justify-between gap-3 p-4 border-b border-line">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                                    <SparklesIcon class="w-5 h-5" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-content text-sm flex items-center gap-2">
                                                        AI Teaching Assistant
                                                        <span class="ui-badge bg-neutral-bg text-neutral-fg">AI</span>
                                                    </p>
                                                    <p class="text-xs text-content-faint">{{ formatTime(message.timestamp) }}</p>
                                                </div>
                                            </div>
                                            <button @click="copyMessage(message.content)" class="p-1.5 rounded-control text-content-faint hover:text-content hover:bg-neutral-bg transition-colors" title="Copy message" aria-label="Copy message">
                                                <ClipboardDocumentCheckIcon class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <div class="p-5">
                                            <div class="prose prose-sm max-w-none">
                                                <div v-html="parseMessageContent(message.content)" class="text-content-muted leading-relaxed"></div>
                                                <span v-if="message.streaming" class="inline-block w-2 text-primary animate-pulse" aria-hidden="true">▍</span>
                                            </div>

                                            <div v-if="message.followUpSuggestions && message.followUpSuggestions.length > 0" class="mt-5">
                                                <p class="text-sm font-semibold text-content mb-3 flex items-center gap-2">
                                                    <LightBulbIcon class="w-4 h-4 text-primary" />
                                                    Try asking
                                                </p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <button
                                                        v-for="suggestion in message.followUpSuggestions"
                                                        :key="suggestion"
                                                        @click="askFollowUp(suggestion)"
                                                        class="text-left px-4 py-2.5 bg-neutral-bg text-content text-sm rounded-control hover:bg-primary-soft hover:text-primary transition-all duration-200 font-medium"
                                                    >
                                                        {{ suggestion }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Typing indicator (until the first streamed token renders) -->
                            <div v-if="isTyping && !streamingDraft" class="flex justify-start">
                                <div class="ui-card px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="ui-icon-tile h-8 w-8 bg-primary-soft text-primary flex-shrink-0">
                                            <SparklesIcon class="w-4 h-4" />
                                        </div>
                                        <div class="flex space-x-1">
                                            <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                            <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                            <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                        </div>
                                        <span class="text-sm text-content-muted font-medium">Thinking…</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composer -->
                    <div class="flex-shrink-0 border-t border-line bg-surface">
                        <div class="mx-auto w-full max-w-3xl px-4 py-3 sm:px-6 sm:py-4">
                            <form @submit.prevent="sendMessage" class="space-y-2">
                                <div class="flex items-end gap-2.5">
                                    <textarea
                                        id="message-input"
                                        v-model="messageInput"
                                        :disabled="isTyping"
                                        placeholder="Ask about teaching strategies, content, rubrics, or student engagement…"
                                        rows="1"
                                        class="ui-input flex-1 resize-none"
                                        @keydown.enter.exact.prevent="sendMessage"
                                        @input="autoResize"
                                    ></textarea>
                                    <button type="submit" :disabled="!messageInput.trim() || isTyping" class="ui-btn-primary h-11 w-11 p-0 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Send message">
                                        <PaperAirplaneIcon class="w-5 h-5" />
                                    </button>
                                </div>
                                <p class="text-xs text-content-faint">
                                    <kbd class="px-1 py-0.5 bg-neutral-bg rounded">Enter</kbd> to send · <kbd class="px-1 py-0.5 bg-neutral-bg rounded">Shift+Enter</kbd> for a new line
                                </p>
                            </form>
                        </div>
                    </div>
                </main>

                <!-- Scrim for tools (below xl) -->
                <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
                    <div v-if="showToolsPanel" class="absolute inset-0 z-20 bg-content/30 backdrop-blur-sm xl:hidden" @click="showToolsPanel = false"></div>
                </Transition>

                <!-- Right Panel - Teaching Tools -->
                <aside
                    class="absolute inset-y-0 right-0 z-30 flex flex-col overflow-hidden border-l border-line bg-surface transition-all duration-300 ease-in-out xl:static xl:z-auto"
                    :class="showToolsPanel ? 'w-80 translate-x-0' : 'w-80 translate-x-full xl:w-0 xl:translate-x-0 xl:border-l-0'"
                >
                    <div class="flex h-full w-80 flex-shrink-0 flex-col">
                        <div class="flex items-center justify-between p-4 border-b border-line">
                            <h3 class="text-sm font-semibold text-content flex items-center gap-2">
                                <BeakerIcon class="w-4 h-4 text-content-muted" />
                                Teaching Tools
                            </h3>
                            <button @click="showToolsPanel = false" class="ui-btn-ghost h-8 w-8 p-0" aria-label="Close tools panel">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Tool switch -->
                        <div class="flex gap-1 p-2 border-b border-line">
                            <button
                                @click="activeTool = 'quiz'"
                                :class="['flex-1 flex items-center justify-center gap-1.5 rounded-control px-3 py-2 text-sm font-medium transition-colors', activeTool === 'quiz' ? 'bg-primary text-white' : 'text-content-muted hover:bg-neutral-bg']"
                            >
                                <ClipboardDocumentListIcon class="w-4 h-4" /> Quiz
                            </button>
                            <button
                                @click="activeTool = 'assignment'"
                                :class="['flex-1 flex items-center justify-center gap-1.5 rounded-control px-3 py-2 text-sm font-medium transition-colors', activeTool === 'assignment' ? 'bg-primary text-white' : 'text-content-muted hover:bg-neutral-bg']"
                            >
                                <DocumentTextIcon class="w-4 h-4" /> Assignment
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-4">
                            <!-- Quiz Generator -->
                            <div v-if="activeTool === 'quiz'" class="space-y-3">
                                <div>
                                    <label class="ui-label">Course</label>
                                    <select v-model="quizForm.course" class="ui-input">
                                        <option value="">Select course</option>
                                        <option v-for="course in facultyContext.courses" :key="course.id || course.name" :value="course.name">{{ course.code }} — {{ course.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ui-label">Topic</label>
                                    <input v-model="quizForm.topic" type="text" :disabled="!quizForm.course" placeholder="e.g. Normalization" class="ui-input disabled:opacity-50" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Difficulty</label>
                                        <select v-model="quizForm.difficulty" class="ui-input">
                                            <option v-for="d in difficultyOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Questions</label>
                                        <input v-model.number="quizForm.questionCount" type="number" min="1" max="20" class="ui-input" />
                                    </div>
                                </div>
                                <div>
                                    <label class="ui-label">Time limit (min)</label>
                                    <input v-model.number="quizForm.timeLimit" type="number" min="5" max="180" class="ui-input" />
                                </div>
                                <div>
                                    <label class="ui-label">Question types</label>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <label
                                            v-for="qt in questionTypeOptions"
                                            :key="qt.value"
                                            class="flex items-center gap-2 rounded-control border border-line px-2.5 py-1.5 text-xs cursor-pointer hover:bg-neutral-bg"
                                            :class="quizForm.questionTypes.includes(qt.value) ? 'bg-primary-soft border-primary text-primary' : 'text-content-muted'"
                                        >
                                            <input v-model="quizForm.questionTypes" type="checkbox" :value="qt.value" class="rounded border-line text-primary focus:ring-primary" />
                                            {{ qt.label }}
                                        </label>
                                    </div>
                                    <p v-if="quizForm.questionTypes.length === 0" class="mt-1 text-xs text-danger-fg">Select at least one question type.</p>
                                </div>
                                <div>
                                    <label class="ui-label">Bloom's level</label>
                                    <select v-model="quizForm.bloomLevel" class="ui-input">
                                        <option v-for="b in bloomLevels" :key="b.value" :value="b.value">{{ b.label }}</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-content">
                                    <input v-model="quizForm.includeExplanations" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                    Include answer explanations
                                </label>
                                <button @click="generateQuiz" :disabled="!isQuizFormValid || isGenerating" class="ui-btn-primary w-full disabled:opacity-50">
                                    <SparklesIcon class="w-4 h-4" />
                                    {{ isGenerating ? 'Generating…' : 'Generate Quiz' }}
                                </button>
                            </div>

                            <!-- Assignment Creator -->
                            <div v-else class="space-y-3">
                                <div>
                                    <label class="ui-label">Title</label>
                                    <input v-model="assignmentForm.title" type="text" placeholder="e.g. Database Design Project" class="ui-input" />
                                </div>
                                <div>
                                    <label class="ui-label">Course</label>
                                    <select v-model="assignmentForm.course" class="ui-input">
                                        <option value="">Select course</option>
                                        <option v-for="course in facultyContext.courses" :key="course.id || course.name" :value="course.name">{{ course.code }} — {{ course.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ui-label">Type</label>
                                    <select v-model="assignmentForm.type" class="ui-input">
                                        <option v-for="t in assignmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ui-label">Topics</label>
                                    <div v-if="assignmentForm.course">
                                        <div class="flex gap-2">
                                            <input v-model="newTopic" type="text" placeholder="Add a topic…" class="ui-input flex-1" @keydown.enter.prevent="addTopicFromInput" />
                                            <button type="button" @click="addTopicFromInput" :disabled="!newTopic.trim()" class="ui-btn-secondary disabled:opacity-50">Add</button>
                                        </div>
                                        <div v-if="assignmentForm.topics.length" class="mt-2 flex flex-wrap gap-1.5">
                                            <button v-for="topic in assignmentForm.topics" :key="topic" type="button" @click="removeTopic(topic)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-pill bg-primary-soft text-primary text-xs font-medium hover:bg-primary-soft/70">
                                                {{ topic }} <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs text-content-muted">Select a course first, then add topics.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="ui-label">Duration (days)</label>
                                        <input v-model="assignmentForm.duration" type="number" min="1" max="90" class="ui-input" />
                                    </div>
                                    <div>
                                        <label class="ui-label">Points</label>
                                        <input v-model="assignmentForm.points" type="number" min="10" max="500" class="ui-input" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-content">
                                        <input v-model="assignmentForm.allowGroup" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                        Allow group work
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-content">
                                        <input v-model="assignmentForm.includeRubric" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                        Include grading rubric
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-content">
                                        <input v-model="assignmentForm.includeResources" type="checkbox" class="rounded border-line text-primary focus:ring-primary" />
                                        Include recommended resources
                                    </label>
                                </div>
                                <button @click="generateAssignment" :disabled="!isAssignmentFormValid || isGenerating" class="ui-btn-primary w-full disabled:opacity-50">
                                    <SparklesIcon class="w-4 h-4" />
                                    {{ isGenerating ? 'Generating…' : 'Generate Assignment' }}
                                </button>
                            </div>

                            <button v-if="generatedContent" @click="showPreview = true" class="ui-btn-secondary w-full">
                                View last generated {{ generatedContent.type }}
                            </button>

                            <!-- Your courses -->
                            <div class="mt-2 p-4 bg-primary-soft rounded-control">
                                <h4 class="font-semibold text-primary text-sm mb-3 flex items-center gap-2">
                                    <AcademicCapIcon class="w-4 h-4" /> Your Courses
                                </h4>
                                <div v-if="facultyContext.courses.length" class="space-y-2">
                                    <div v-for="course in facultyContext.courses" :key="course.id || course.name" class="flex items-center gap-2 text-sm text-content bg-surface px-3 py-2 rounded-control">
                                        <span class="w-2 h-2 bg-primary rounded-full flex-shrink-0"></span>
                                        <span class="truncate"><span class="font-semibold">{{ course.code }}</span> <span class="text-content-muted">{{ course.name }}</span></span>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-content-muted">You are not teaching any courses yet.</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Floating tab to reopen tools panel -->
                <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-x-2" leave-active-class="transition duration-150" leave-to-class="opacity-0 translate-x-2">
                    <button
                        v-if="!showToolsPanel"
                        @click="showToolsPanel = true"
                        class="absolute right-0 top-1/2 z-20 flex -translate-y-1/2 items-center justify-center rounded-l-control border border-r-0 border-line bg-surface px-2 py-4 text-content-muted shadow-card transition-colors hover:bg-primary-soft hover:text-primary"
                        title="Show teaching tools"
                        aria-label="Show teaching tools"
                    >
                        <BeakerIcon class="h-5 w-5" />
                    </button>
                </Transition>
            </div>

            <!-- Per-session context menu -->
            <Teleport to="body">
                <div v-if="openMenuChat">
                    <div class="fixed inset-0 z-40" @click="closeMenu" @contextmenu.prevent="closeMenu"></div>
                    <div class="fixed z-50 w-44 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-card-hover" :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }">
                        <button @click="startRename(openMenuChat)" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary">
                            <PencilSquareIcon class="h-4 w-4" /> Rename
                        </button>
                        <button @click="togglePin(openMenuChat)" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary">
                            <component :is="openMenuChat.pinned ? MapPinSolidIcon : MapPinIcon" class="h-4 w-4" />
                            {{ openMenuChat.pinned ? 'Unpin chat' : 'Pin chat' }}
                        </button>
                        <button @click="archiveChat(openMenuChat)" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-content-muted transition-colors hover:bg-primary-soft hover:text-primary">
                            <ArchiveBoxIcon class="h-4 w-4" /> Archive
                        </button>
                        <div class="my-1 border-t border-line"></div>
                        <button @click="deleteChat(openMenuChat)" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-danger-fg transition-colors hover:bg-danger-bg">
                            <TrashIcon class="h-4 w-4" /> Delete
                        </button>
                    </div>
                </div>
            </Teleport>

            <!-- Generated content preview modal -->
            <Teleport to="body">
                <div v-if="showPreview && generatedContent" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPreview = false"></div>
                        <div class="relative w-full max-w-3xl bg-surface rounded-card border border-line shadow-card max-h-[90vh] overflow-y-auto">
                            <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-line bg-surface p-5">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-content truncate">{{ generatedContent.title }}</h3>
                                    <p class="text-xs text-content-muted">
                                        {{ generatedContent.course }} ·
                                        <span v-if="generatedContent.type === 'quiz'">{{ generatedContent.totalQuestions }} questions · {{ generatedContent.timeLimit }} min</span>
                                        <span v-else>{{ generatedContent.totalPoints }} points · due {{ generatedContent.dueDate }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-2">
                                    <button @click="editGenerated" class="ui-btn-ghost" title="Edit inputs"><PencilSquareIcon class="w-4 h-4" /> <span class="hidden sm:inline">Edit</span></button>
                                    <button @click="regenerateContent" :disabled="isGenerating" class="ui-btn-ghost disabled:opacity-50" title="Regenerate">
                                        <ArrowPathIcon class="w-4 h-4" :class="isGenerating ? 'animate-spin' : ''" /> <span class="hidden sm:inline">{{ isGenerating ? 'Regenerating…' : 'Regenerate' }}</span>
                                    </button>
                                    <button @click="exportGenerated" class="ui-btn-secondary"><ArrowDownTrayIcon class="w-4 h-4" /> <span class="hidden sm:inline">Export PDF</span></button>
                                    <button @click="publishContent" :disabled="isPublishing" class="ui-btn-primary disabled:opacity-50">
                                        <CheckIcon class="w-4 h-4" /> {{ isPublishing ? 'Publishing…' : 'Publish' }}
                                    </button>
                                    <button @click="showPreview = false" class="ui-btn-ghost h-9 w-9 p-0" aria-label="Close preview"><XMarkIcon class="w-5 h-5" /></button>
                                </div>
                            </div>

                            <div class="p-5 space-y-4">
                                <!-- Quiz -->
                                <template v-if="generatedContent.type === 'quiz'">
                                    <div class="bg-primary-soft rounded-control p-4 text-sm text-primary">{{ generatedContent.instructions }}</div>

                                    <!-- Publish target: a plain text handout (Assignment) or the
                                         interactive, auto-graded Class Test engine. This preview always
                                         shows the answer key to faculty regardless of the choice. -->
                                    <div class="rounded-card border border-line bg-bg p-3 space-y-3">
                                        <div>
                                            <span class="text-sm font-medium text-content">Publish as</span>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <button
                                                    type="button"
                                                    @click="publishTarget = 'assignment'"
                                                    :class="['rounded-control border px-3 py-2 text-left text-sm font-medium transition-colors', publishTarget === 'assignment' ? 'border-primary bg-primary-soft text-primary' : 'border-line text-content-muted hover:bg-neutral-bg']"
                                                >
                                                    Assignment
                                                    <span class="mt-0.5 block text-xs font-normal">Text handout · manual grading</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="publishTarget = 'class_test'"
                                                    :class="['rounded-control border px-3 py-2 text-left text-sm font-medium transition-colors', publishTarget === 'class_test' ? 'border-primary bg-primary-soft text-primary' : 'border-line text-content-muted hover:bg-neutral-bg']"
                                                >
                                                    Class Test
                                                    <span class="mt-0.5 block text-xs font-normal">Interactive · auto-graded</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Assignment sub-option: whether the handout text reveals answers. -->
                                        <label v-if="publishTarget === 'assignment'" class="flex items-start gap-3 border-t border-line pt-3">
                                            <input v-model="includeAnswers" type="checkbox" class="mt-0.5 rounded border-line text-primary focus:ring-primary" />
                                            <span class="text-sm">
                                                <span class="font-medium text-content">Include answers for students</span>
                                                <span class="mt-0.5 block text-xs text-content-muted">
                                                    {{ includeAnswers
                                                        ? 'Students will see the correct answers and explanations in the published quiz.'
                                                        : 'Students will see only the questions. Correct answers and explanations are hidden.' }}
                                                </span>
                                            </span>
                                        </label>

                                        <!-- Class test note: answers are handled by the engine automatically. -->
                                        <p v-else class="border-t border-line pt-3 text-xs text-content-muted">
                                            Students take this in-browser. Answers are hidden during the test and shown on the results page after they submit; grading is automatic.
                                            <span class="mt-1 block font-medium" :class="classTestEligibleCount === 0 ? 'text-danger-fg' : 'text-content'">
                                                {{ classTestEligibleCount }} of {{ generatedContent.questions.length }} question(s) will be included — only multiple-choice &amp; true/false are auto-gradable.
                                            </span>
                                        </p>
                                    </div>
                                    <div v-if="generatedContent.questions.length === 0" class="text-sm text-content-muted">All questions removed. Regenerate to create more.</div>
                                    <div v-for="question in generatedContent.questions" :key="question.id" class="border border-line rounded-card p-4">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-semibold text-content">Question {{ question.id }}</span>
                                                <span class="ui-badge bg-neutral-bg text-neutral-fg capitalize">{{ questionTypeLabel(question.type) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-content-muted">{{ question.points }} pt</span>
                                                <button @click="deleteQuestion(question.id)" class="rounded-control p-1 text-content-faint hover:text-danger-fg" title="Delete question" aria-label="Delete question">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-content mb-3">{{ question.question }}</p>
                                        <div v-if="question.type === 'multiple-choice'" class="space-y-2">
                                            <div v-for="(option, idx) in question.options" :key="idx" :class="['flex items-center gap-2 p-2 rounded-control border text-sm', idx === question.correctAnswer ? 'border-success-fg/30 bg-success-bg text-success-fg' : 'border-line']">
                                                <span class="font-semibold">{{ String.fromCharCode(65 + idx) }}.</span>
                                                <span>{{ option }}</span>
                                                <CheckIcon v-if="idx === question.correctAnswer" class="w-4 h-4 ml-auto text-success-fg" />
                                            </div>
                                        </div>
                                        <div v-else-if="question.type === 'true-false'" class="text-sm text-content">Answer: <strong>{{ question.correctAnswer ? 'True' : 'False' }}</strong></div>
                                        <div v-else class="text-sm rounded-control border border-line bg-neutral-bg p-3">
                                            <span class="text-content-muted">Expected answer:</span> <span class="text-content">{{ question.answerText || '—' }}</span>
                                        </div>
                                        <div v-if="question.explanation" class="mt-3 p-3 bg-neutral-bg rounded-control text-sm text-content-muted">
                                            <strong>Explanation:</strong> {{ question.explanation }}
                                        </div>
                                    </div>
                                </template>

                                <!-- Assignment -->
                                <template v-else>
                                    <p class="text-content-muted">{{ generatedContent.description }}</p>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="ui-badge bg-neutral-bg text-neutral-fg">{{ generatedContent.category }}</span>
                                        <span class="ui-badge bg-neutral-bg text-neutral-fg">{{ generatedContent.duration }} days</span>
                                        <span class="ui-badge bg-neutral-bg text-neutral-fg">{{ generatedContent.groupWork ? 'Group work' : 'Individual' }}</span>
                                    </div>
                                    <div v-for="section in generatedContent.sections" :key="section.title" class="border border-line rounded-card p-4">
                                        <div class="flex items-center justify-between mb-1">
                                            <h4 class="font-semibold text-content">{{ section.title }}</h4>
                                            <span class="text-xs text-content-muted">{{ section.points }} pts</span>
                                        </div>
                                        <p class="text-sm text-content-muted mb-2">{{ section.description }}</p>
                                        <ul v-if="section.requirements && section.requirements.length" class="text-xs text-content-muted space-y-1">
                                            <li v-for="req in section.requirements" :key="req" class="flex items-start gap-2"><span class="text-primary mt-0.5">•</span> {{ req }}</li>
                                        </ul>
                                    </div>
                                    <div v-if="generatedContent.rubric && generatedContent.rubric.length" class="bg-warning-bg rounded-card p-4">
                                        <h4 class="font-semibold text-content mb-2">Grading Rubric</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div v-for="c in generatedContent.rubric" :key="c.category" class="bg-surface rounded-control p-3 border border-line">
                                                <p class="font-medium text-content text-sm">{{ c.category }}</p>
                                                <p class="text-xs text-warning-fg font-medium">{{ c.weight }}% of grade</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="flex justify-end pt-2">
                                    <button @click="clearForms" class="ui-btn-ghost"><TrashIcon class="w-4 h-4" /> Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <ConfirmDialog />
        </div>
    </div>
</template>

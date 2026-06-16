<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import {
    ChatBubbleLeftRightIcon,
    SparklesIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ClipboardDocumentListIcon,
    ArrowPathIcon,
    ArrowDownTrayIcon,
    CheckIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    StarIcon,
    ArrowUpTrayIcon,
    CheckCircleIcon,
    BookOpenIcon,
    ClockIcon,
    UserIcon,
    InformationCircleIcon,
    LightBulbIcon,
    BeakerIcon,
    PaperAirplaneIcon,
    ListBulletIcon,
    CheckCircleIcon as CheckCircleSolidIcon,
    PencilSquareIcon,
    DocumentIcon,
    LanguageIcon,
    LinkIcon
} from '@heroicons/vue/24/outline';

// Component state
const activeTab = ref('chat');
const isTyping = ref(false);
const isGenerating = ref(false);
const currentMessage = ref('');
const messagesContainer = ref(null);
const showAllSections = ref(false);

// Server-provided props
const props = defineProps({
    facultyContext: {
        type: Object,
        default: () => ({})
    }
});

// Faculty context — the template renders each course as a string (select value
// and sidebar label) and `courseTopics` is keyed by course name, so map the
// server course objects ({ id, code, name }) down to their display names.
const facultyContext = computed(() => ({
    name: props.facultyContext.name ?? '',
    department: props.facultyContext.department ?? '',
    courses: (props.facultyContext.courses ?? []).map((course) =>
        typeof course === 'string' ? course : course.name
    )
}));

// Chat messages
const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content: `Hello **${facultyContext.value.name || 'Professor'}**! 👋

I'm your **AI Teaching Assistant**, specialized in helping faculty create engaging educational content and improve teaching effectiveness.

**What I can help you with:**
• **Quiz Generation:** Create comprehensive quizzes with multiple question types
• **Assignment Design:** Develop detailed assignments with rubrics and guidelines
• **Teaching Strategies:** Suggest pedagogical approaches for complex topics
• **Content Creation:** Generate lecture materials, examples, and activities
• **Assessment Tools:** Design evaluation criteria and grading rubrics

**Quick Start:**
• Switch to the **Quiz Generator** tab to create instant quizzes
• Use **Assignment Creator** for comprehensive project design
• Ask me specific questions about teaching methodologies

How can I assist with your teaching today?`,
        timestamp: new Date().toISOString(),
        suggestions: [
            'Generate a quiz for Database Normalization',
            'Create an assignment for Machine Learning project',
            'Help me explain complex algorithms',
            'Design interactive learning activities'
        ]
    }
]);

// Quiz form data
const quizForm = ref({
    topic: '',
    course: '',
    difficulty: 'intermediate',
    questionCount: 10,
    questionTypes: ['multiple-choice', 'true-false'],
    timeLimit: 30,
    includeExplanations: true,
    bloomLevel: 'application'
});

// Assignment form data
const assignmentForm = ref({
    title: '',
    course: '',
    type: 'Project',
    topics: [],
    difficulty: 'intermediate',
    duration: '14', // days
    points: '100',
    allowGroup: false,
    includeRubric: true,
    includeResources: true
});

// Generated content
const generatedContent = ref(null);

// Form options
const difficultyOptions = [
    { value: 'beginner', label: 'Beginner', description: 'Basic concepts and fundamentals' },
    { value: 'intermediate', label: 'Intermediate', description: 'Applied knowledge and problem-solving' },
    { value: 'advanced', label: 'Advanced', description: 'Complex analysis and synthesis' }
];

const questionTypeOptions = [
    { value: 'multiple-choice', label: 'Multiple Choice', icon: ListBulletIcon },
    { value: 'true-false', label: 'True/False', icon: CheckCircleSolidIcon },
    { value: 'short-answer', label: 'Short Answer', icon: PencilSquareIcon },
    { value: 'essay', label: 'Essay', icon: DocumentIcon },
    { value: 'fill-in-blank', label: 'Fill in the Blank', icon: LanguageIcon },
    { value: 'matching', label: 'Matching', icon: LinkIcon }
];

const bloomLevels = [
    { value: 'remember', label: 'Remember', description: 'Recall facts and basic concepts' },
    { value: 'understand', label: 'Understand', description: 'Explain ideas and concepts' },
    { value: 'apply', label: 'Apply', description: 'Use information in new situations' },
    { value: 'analyze', label: 'Analyze', description: 'Draw connections among ideas' },
    { value: 'evaluate', label: 'Evaluate', description: 'Justify decisions or judgments' },
    { value: 'create', label: 'Create', description: 'Produce new or original work' }
];

const assignmentTypes = [
    { value: 'Project', label: 'Project Assignment', description: 'Comprehensive project work' },
    { value: 'Research', label: 'Research Paper', description: 'Academic research and writing' },
    { value: 'Lab', label: 'Laboratory Exercise', description: 'Hands-on practical work' },
    { value: 'Case Study', label: 'Case Study Analysis', description: 'Real-world scenario analysis' },
    { value: 'Presentation', label: 'Presentation Assignment', description: 'Oral presentation and slides' }
];

// Available topics by course
const courseTopics = {
    'Database Systems': [
        'Relational Model', 'SQL Queries', 'Normalization', 'Indexing', 'Transactions',
        'Concurrency Control', 'Recovery Systems', 'NoSQL Databases', 'Query Optimization'
    ],
    'Machine Learning': [
        'Supervised Learning', 'Unsupervised Learning', 'Neural Networks', 'Deep Learning',
        'Feature Selection', 'Model Evaluation', 'Classification', 'Regression', 'Clustering'
    ],
    'Software Engineering': [
        'SDLC Models', 'Requirements Engineering', 'System Design', 'Testing Strategies',
        'Agile Methodologies', 'Version Control', 'Code Review', 'DevOps', 'Software Architecture'
    ]
};

// Computed properties
const availableTopics = computed(() => {
    const course = quizForm.value.course || assignmentForm.value.course;
    return courseTopics[course] || [];
});

const isQuizFormValid = computed(() => {
    return quizForm.value.topic && quizForm.value.course && quizForm.value.questionCount > 0;
});

const isAssignmentFormValid = computed(() => {
    return assignmentForm.value.title &&
           assignmentForm.value.course &&
           assignmentForm.value.topics.length > 0;
});

// Utility functions
const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

// Chat actions
const sendMessage = async () => {
    if (!currentMessage.value.trim() || isTyping.value) return;

    const userMessage = {
        id: Date.now(),
        role: 'user',
        content: currentMessage.value.trim(),
        timestamp: new Date().toISOString()
    };

    messages.value.push(userMessage);
    const userInput = currentMessage.value.trim();
    currentMessage.value = '';

    isTyping.value = true;
    scrollToBottom();

    try {
        const { data } = await axios.post(route('faculty.ai-assistant.chat'), {
            message: userInput
        });
        const reply = data.reply ?? {};

        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: reply.content ?? '',
            timestamp: new Date().toISOString(),
            suggestions: reply.follow_ups ?? []
        });
    } catch (error) {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: 'Sorry, I could not process that request. Please try again.',
            timestamp: new Date().toISOString()
        });
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

// Maps an API quiz question ({ id, question, options[], answer, explanation,
// points }) into the field names the template renders: it needs `type` and
// `correctAnswer` (MC = option index, true/false = boolean).
const mapQuizQuestion = (question, index) => {
    const hasOptions = Array.isArray(question.options) && question.options.length > 0;
    let correctAnswer;

    if (hasOptions) {
        correctAnswer = question.options.findIndex((option) => option === question.answer);
        if (correctAnswer === -1) {
            correctAnswer = typeof question.answer === 'number' ? question.answer : 0;
        }
    } else {
        correctAnswer = question.answer === true
            || String(question.answer).toLowerCase() === 'true';
    }

    return {
        id: question.id ?? index + 1,
        type: hasOptions ? 'multiple-choice' : 'true-false',
        question: question.question,
        options: question.options ?? [],
        correctAnswer,
        explanation: question.explanation ?? null,
        points: question.points ?? 1
    };
};

// Quiz generation
const generateQuiz = async () => {
    if (!isQuizFormValid.value) return;

    isGenerating.value = true;

    try {
        const { data } = await axios.post(route('faculty.ai-assistant.quiz'), {
            topic: quizForm.value.topic,
            course: quizForm.value.course,
            difficulty: quizForm.value.difficulty,
            questionCount: quizForm.value.questionCount
        });
        const quiz = data.quiz ?? {};
        const questions = (quiz.questions ?? []).map(mapQuizQuestion);

        generatedContent.value = {
            type: 'quiz',
            title: quiz.title ?? `${quizForm.value.topic} Quiz - ${quizForm.value.course}`,
            course: quizForm.value.course,
            topic: quiz.topic ?? quizForm.value.topic,
            difficulty: quiz.difficulty ?? quizForm.value.difficulty,
            totalQuestions: questions.length,
            timeLimit: quizForm.value.timeLimit,
            instructions: `This quiz covers ${quiz.topic ?? quizForm.value.topic} concepts for ${quizForm.value.course}. You have ${quizForm.value.timeLimit} minutes to complete ${questions.length} questions.`,
            questions
        };

        activeTab.value = 'preview';
    } finally {
        isGenerating.value = false;
    }
};

// Maps an API assignment task into a template "section". Tasks may be plain
// strings or objects; the template section needs title/description/points/
// requirements/deliverables.
const mapAssignmentTask = (task, index, sectionPoints) => {
    if (typeof task === 'string') {
        return {
            title: `Task ${index + 1}`,
            description: task,
            points: sectionPoints,
            requirements: [task],
            deliverables: []
        };
    }

    return {
        title: task.title ?? `Task ${index + 1}`,
        description: task.description ?? '',
        points: task.points ?? sectionPoints,
        requirements: task.requirements ?? (task.description ? [task.description] : []),
        deliverables: task.deliverables ?? []
    };
};

// Maps an API rubric entry ({ criterion, points }) to the template's rubric
// item ({ category, description, weight }) — weight is the criterion's share
// of the total rubric points expressed as a percentage.
const mapRubric = (rubric) => {
    const totalPoints = (rubric ?? []).reduce((sum, item) => sum + (item.points ?? 0), 0);

    return (rubric ?? []).map((item) => ({
        category: item.criterion,
        description: `Worth ${item.points} points`,
        weight: totalPoints > 0 ? Math.round(((item.points ?? 0) / totalPoints) * 100) : 0
    }));
};

// Assignment generation
const generateAssignment = async () => {
    if (!isAssignmentFormValid.value) return;

    isGenerating.value = true;

    try {
        const { data } = await axios.post(route('faculty.ai-assistant.assignment'), {
            title: assignmentForm.value.title,
            topics: assignmentForm.value.topics,
            points: parseInt(assignmentForm.value.points)
        });
        const assignment = data.assignment ?? {};
        const totalPoints = assignment.points ?? parseInt(assignmentForm.value.points);
        const tasks = assignment.tasks ?? [];
        const perTaskPoints = tasks.length > 0 ? Math.round(totalPoints / tasks.length) : totalPoints;

        generatedContent.value = {
            type: 'assignment',
            title: assignment.title ?? `${assignmentForm.value.title} - ${assignmentForm.value.course}`,
            description: assignment.description
                ?? `A comprehensive ${assignmentForm.value.type.toLowerCase()} focusing on ${assignmentForm.value.topics.join(', ')}.`,
            course: assignmentForm.value.course,
            type: assignmentForm.value.type,
            topics: assignmentForm.value.topics,
            duration: assignmentForm.value.duration,
            totalPoints,
            dueDate: new Date(Date.now() + parseInt(assignmentForm.value.duration) * 24 * 60 * 60 * 1000).toLocaleDateString(),
            groupWork: assignmentForm.value.allowGroup,
            sections: tasks.map((task, index) => mapAssignmentTask(task, index, perTaskPoints)),
            rubric: assignmentForm.value.includeRubric ? mapRubric(assignment.rubric) : null,
            submissionGuidelines: [
                {
                    type: 'Format',
                    detail: 'Submit as a ZIP file containing all source code and documentation'
                },
                {
                    type: 'Naming',
                    detail: 'Name your file as: StudentID_AssignmentName.zip'
                },
                {
                    type: 'Platform',
                    detail: 'Upload through the course management system'
                },
                {
                    type: 'Late Policy',
                    detail: '10% deduction per day after due date'
                }
            ],
            resources: assignmentForm.value.includeResources ? [
                {
                    title: 'Official Documentation',
                    description: 'Language/framework official documentation',
                    type: 'Documentation',
                    url: '#'
                },
                {
                    title: 'Best Practices Guide',
                    description: 'Industry best practices for clean code',
                    type: 'Guide',
                    url: '#'
                },
                {
                    title: 'Tutorial Videos',
                    description: 'Step-by-step implementation tutorials',
                    type: 'Video',
                    url: '#'
                },
                {
                    title: 'Sample Projects',
                    description: 'Reference implementations and examples',
                    type: 'Examples',
                    url: '#'
                }
            ] : null
        };

        activeTab.value = 'preview';
    } finally {
        isGenerating.value = false;
    }
};

// Action functions
const editQuiz = () => {
    activeTab.value = 'quiz';
};

const editAssignment = () => {
    activeTab.value = 'assignment';
};

const regenerateContent = () => {
    if (generatedContent.value.type === 'quiz') {
        generateQuiz();
    } else {
        generateAssignment();
    }
};

const exportGenerated = () => {
    const type = generatedContent.value.type;
    alert(`Exporting ${type} as PDF...`);
};

const publishContent = () => {
    const type = generatedContent.value.type;
    alert(`Publishing ${type} to course...`);
};

const deleteQuestion = (questionId) => {
    if (confirm('Are you sure you want to delete this question?')) {
        generatedContent.value.questions = generatedContent.value.questions.filter(q => q.id !== questionId);
    }
};

const addTopic = (topic) => {
    if (!assignmentForm.value.topics.includes(topic)) {
        assignmentForm.value.topics.push(topic);
    }
};

const removeTopic = (topic) => {
    const index = assignmentForm.value.topics.indexOf(topic);
    if (index > -1) {
        assignmentForm.value.topics.splice(index, 1);
    }
};

const clearForms = () => {
    quizForm.value = {
        topic: '',
        course: '',
        difficulty: 'intermediate',
        questionCount: 10,
        questionTypes: ['multiple-choice', 'true-false'],
        timeLimit: 30,
        includeExplanations: true,
        bloomLevel: 'application'
    };

    assignmentForm.value = {
        title: '',
        course: '',
        type: 'Project',
        topics: [],
        difficulty: 'intermediate',
        duration: '14',
        points: '100',
        allowGroup: false,
        includeRubric: true,
        includeResources: true
    };

    generatedContent.value = null;
};

// Tab definitions for pill navigation
const tabs = computed(() => {
    const base = [
        { value: 'chat', label: 'AI Chat Assistant', icon: ChatBubbleLeftRightIcon },
        { value: 'quiz', label: 'Quiz Generator', icon: ClipboardDocumentListIcon },
        { value: 'assignment', label: 'Assignment Creator', icon: DocumentTextIcon }
    ];

    if (generatedContent.value) {
        base.push({ value: 'preview', label: 'Preview', icon: EyeIcon });
    }

    return base;
});

// Lifecycle
onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <div>
        <Head title="Faculty AI Teaching Assistant" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <!-- Page Header -->
                <PageHeader
                    title="AI Teaching Assistant"
                    subtitle="Generate quizzes, assignments, and get teaching insights powered by AI"
                    eyebrow="Faculty"
                    :icon="SparklesIcon"
                >
                    <template #actions>
                        <div class="hidden sm:flex flex-col items-end leading-tight mr-1">
                            <span class="text-sm font-semibold text-content">{{ facultyContext.name }}</span>
                            <span class="text-xs text-content-muted">{{ facultyContext.department }}</span>
                        </div>
                        <Link href="/faculty/dashboard" class="ui-btn-secondary">
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Tab Navigation (pill toggles) -->
                <div class="flex flex-wrap gap-2 rounded-card border border-line bg-surface p-1.5 shadow-card">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        @click="activeTab = tab.value"
                        :class="[
                            'flex items-center gap-2 rounded-control px-4 py-2.5 text-sm font-medium transition-colors',
                            activeTab === tab.value
                                ? 'bg-primary text-white shadow-sm'
                                : 'text-content-muted hover:text-content hover:bg-bg'
                        ]"
                    >
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Chat Assistant Tab -->
                <div v-if="activeTab === 'chat'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Chat Interface -->
                        <div class="lg:col-span-2">
                            <Card padding="p-0" class="h-[600px] flex flex-col overflow-hidden">
                                <!-- Chat Header -->
                                <div class="p-5 sm:p-6 border-b border-line">
                                    <h2 class="text-lg font-bold text-content flex items-center gap-2">
                                        <SparklesIcon class="w-6 h-6 text-primary" />
                                        AI Teaching Assistant
                                    </h2>
                                    <p class="text-sm text-content-muted mt-1">
                                        Ask questions about teaching strategies, content creation, and student engagement
                                    </p>
                                </div>

                                <!-- Messages -->
                                <div
                                    ref="messagesContainer"
                                    class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4"
                                >
                                    <div v-for="message in messages" :key="message.id" class="space-y-4">
                                        <!-- User Message -->
                                        <div v-if="message.role === 'user'" class="flex justify-end">
                                            <div class="max-w-[80%] bg-primary text-white rounded-card rounded-br-md px-5 py-3.5 shadow-sm">
                                                <p class="text-sm leading-relaxed">{{ message.content }}</p>
                                                <div class="text-xs text-white/80 mt-2">{{ formatTime(message.timestamp) }}</div>
                                            </div>
                                        </div>

                                        <!-- AI Assistant Message -->
                                        <div v-else class="flex justify-start">
                                            <div class="max-w-[90%] w-full bg-surface rounded-card rounded-bl-md border border-line overflow-hidden">
                                                <!-- AI Header -->
                                                <div class="p-4 bg-primary-soft border-b border-line">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                                            <SparklesIcon class="w-4 h-4 text-white" />
                                                        </div>
                                                        <div>
                                                            <p class="font-semibold text-primary text-sm">AI Teaching Assistant</p>
                                                            <p class="text-xs text-primary">{{ formatTime(message.timestamp) }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Message Content -->
                                                <div class="p-5 sm:p-6">
                                                    <div
                                                        class="prose prose-sm max-w-none text-content-muted"
                                                        v-html="message.content.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/•\s/g, '<span class=&quot;text-primary font-bold&quot;>•</span> ')"
                                                    ></div>

                                                    <!-- Follow-up Suggestions -->
                                                    <div v-if="message.suggestions && message.suggestions.length > 0" class="mt-6">
                                                        <p class="text-sm font-semibold text-content mb-3">Try asking:</p>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            <button
                                                                v-for="suggestion in message.suggestions"
                                                                :key="suggestion"
                                                                @click="currentMessage = suggestion; sendMessage()"
                                                                class="text-left px-4 py-3 bg-primary-soft text-primary text-sm rounded-control hover:bg-primary-soft/70 transition-colors border border-line"
                                                            >
                                                                {{ suggestion }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Typing Indicator -->
                                    <div v-if="isTyping" class="flex justify-start">
                                        <div class="bg-surface border border-line rounded-card rounded-bl-md px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                                    <SparklesIcon class="w-4 h-4 text-white" />
                                                </div>
                                                <div class="flex space-x-1">
                                                    <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                                    <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                                    <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                                </div>
                                                <span class="text-sm text-content-muted">AI is thinking...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div class="p-5 sm:p-6 border-t border-line">
                                    <form @submit.prevent="sendMessage" class="flex gap-3">
                                        <textarea
                                            v-model="currentMessage"
                                            :disabled="isTyping"
                                            placeholder="Ask about teaching strategies, content creation, or student engagement..."
                                            rows="1"
                                            class="ui-input flex-1 resize-none"
                                            @keydown.enter.exact.prevent="sendMessage"
                                        ></textarea>
                                        <button
                                            type="submit"
                                            :disabled="!currentMessage.trim() || isTyping"
                                            class="ui-btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                            aria-label="Send message"
                                        >
                                            <span v-if="isTyping">...</span>
                                            <PaperAirplaneIcon v-else class="w-5 h-5" />
                                        </button>
                                    </form>
                                </div>
                            </Card>
                        </div>

                        <!-- Quick Actions Sidebar -->
                        <div class="space-y-6">
                            <!-- Faculty Quick Stats -->
                            <Card title="Your Courses" :icon="AcademicCapIcon">
                                <div class="space-y-3">
                                    <div
                                        v-for="course in facultyContext.courses"
                                        :key="course"
                                        class="flex items-center justify-between p-3 bg-bg rounded-control border border-line"
                                    >
                                        <span class="text-sm font-medium text-content">{{ course }}</span>
                                        <AcademicCapIcon class="w-4 h-4 text-primary" />
                                    </div>
                                </div>
                            </Card>

                            <!-- Quick Actions -->
                            <Card title="Quick Actions" :icon="BeakerIcon">
                                <div class="space-y-3">
                                    <button
                                        @click="activeTab = 'quiz'"
                                        class="w-full flex items-center gap-3 p-3 bg-primary-soft text-primary rounded-control hover:bg-primary-soft/70 transition-colors"
                                    >
                                        <ClipboardDocumentListIcon class="w-5 h-5" />
                                        Generate Quiz
                                    </button>
                                    <button
                                        @click="activeTab = 'assignment'"
                                        class="w-full flex items-center gap-3 p-3 bg-primary-soft text-primary rounded-control hover:bg-primary-soft/70 transition-colors"
                                    >
                                        <DocumentTextIcon class="w-5 h-5" />
                                        Create Assignment
                                    </button>
                                    <button
                                        @click="clearForms"
                                        class="w-full flex items-center gap-3 p-3 bg-neutral-bg text-neutral-fg rounded-control hover:bg-bg transition-colors"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                        Clear All Forms
                                    </button>
                                </div>
                            </Card>

                            <!-- Teaching Tips -->
                            <Card>
                                <h3 class="text-base font-bold text-content mb-3 flex items-center gap-2">
                                    <LightBulbIcon class="w-5 h-5 text-warning-fg" />
                                    Teaching Tip
                                </h3>
                                <p class="text-sm text-content-muted mb-3">
                                    Use the Bloom's taxonomy levels when creating questions to ensure comprehensive assessment of student understanding.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge variant="warning">Remember</Badge>
                                    <Badge variant="warning">Apply</Badge>
                                    <Badge variant="warning">Analyze</Badge>
                                </div>
                            </Card>
                        </div>
                    </div>
                </div>

                <!-- Quiz Generator Tab -->
                <div v-if="activeTab === 'quiz'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Quiz Form -->
                        <Card title="Quiz Generator" :icon="ClipboardDocumentListIcon">
                            <div class="space-y-6">
                                <!-- Course and Topic -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label">Course</label>
                                        <select
                                            v-model="quizForm.course"
                                            class="ui-input"
                                        >
                                            <option value="">Select Course</option>
                                            <option v-for="course in facultyContext.courses" :key="course" :value="course">
                                                {{ course }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Topic</label>
                                        <select
                                            v-model="quizForm.topic"
                                            :disabled="!quizForm.course"
                                            class="ui-input disabled:opacity-50"
                                        >
                                            <option value="">Select Topic</option>
                                            <option v-for="topic in availableTopics" :key="topic" :value="topic">
                                                {{ topic }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Quiz Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="ui-label">Difficulty</label>
                                        <select
                                            v-model="quizForm.difficulty"
                                            class="ui-input"
                                        >
                                            <option v-for="level in difficultyOptions" :key="level.value" :value="level.value">
                                                {{ level.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Questions</label>
                                        <input
                                            v-model.number="quizForm.questionCount"
                                            type="number"
                                            min="1"
                                            max="50"
                                            class="ui-input"
                                        />
                                    </div>
                                    <div>
                                        <label class="ui-label">Time Limit (min)</label>
                                        <input
                                            v-model.number="quizForm.timeLimit"
                                            type="number"
                                            min="5"
                                            max="180"
                                            class="ui-input"
                                        />
                                    </div>
                                </div>

                                <!-- Question Types -->
                                <div>
                                    <label class="ui-label">Question Types</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        <label
                                            v-for="type in questionTypeOptions"
                                            :key="type.value"
                                            class="flex items-center p-3 border border-line rounded-control cursor-pointer hover:bg-bg transition-colors"
                                        >
                                            <input
                                                v-model="quizForm.questionTypes"
                                                type="checkbox"
                                                :value="type.value"
                                                class="rounded border-line text-primary focus:ring-primary"
                                            />
                                            <component :is="type.icon" class="ml-2 w-4 h-4 text-primary" />
                                            <span class="ml-2 text-sm text-content">{{ type.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Bloom's Taxonomy Level -->
                                <div>
                                    <label class="ui-label">Bloom's Taxonomy Level</label>
                                    <select
                                        v-model="quizForm.bloomLevel"
                                        class="ui-input"
                                    >
                                        <option v-for="level in bloomLevels" :key="level.value" :value="level.value">
                                            {{ level.label }} - {{ level.description }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Additional Options -->
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            v-model="quizForm.includeExplanations"
                                            type="checkbox"
                                            class="rounded border-line text-primary focus:ring-primary"
                                        />
                                        <span class="ml-3 text-sm text-content">Include answer explanations</span>
                                    </label>
                                </div>

                                <!-- Generate Button -->
                                <button
                                    @click="generateQuiz"
                                    :disabled="!isQuizFormValid || isGenerating"
                                    class="ui-btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <SparklesIcon class="w-5 h-5" />
                                    <span v-if="isGenerating">Generating Quiz...</span>
                                    <span v-else>Generate Quiz</span>
                                </button>
                            </div>
                        </Card>

                        <!-- Quiz Preview -->
                        <Card>
                            <EmptyState
                                v-if="!generatedContent || generatedContent.type !== 'quiz'"
                                title="Quiz Preview"
                                description="Fill out the form and generate a quiz to see the preview here"
                                :icon="ClipboardDocumentListIcon"
                            />

                            <div v-else>
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-content">Quiz Preview</h3>
                                    <div class="flex gap-2">
                                        <button
                                            @click="editQuiz"
                                            class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                            aria-label="Edit quiz"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="regenerateContent"
                                            class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                            aria-label="Regenerate quiz"
                                        >
                                            <ArrowPathIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Quiz Info -->
                                <div class="bg-bg border border-line rounded-control p-4 mb-6">
                                    <h4 class="font-semibold text-content mb-2">{{ generatedContent.title }}</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm text-content-muted">
                                        <div>Questions: {{ generatedContent.totalQuestions }}</div>
                                        <div>Time: {{ generatedContent.timeLimit }} minutes</div>
                                        <div>Difficulty: {{ generatedContent.difficulty }}</div>
                                        <div>Course: {{ generatedContent.course }}</div>
                                    </div>
                                </div>

                                <!-- Sample Questions -->
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div
                                        v-for="question in generatedContent.questions.slice(0, 3)"
                                        :key="question.id"
                                        class="border border-line rounded-control p-4"
                                    >
                                        <div class="flex items-start justify-between mb-3">
                                            <span class="text-sm font-medium text-content">Question {{ question.id }}</span>
                                            <span class="text-xs text-content-muted">{{ question.points }} points</span>
                                        </div>
                                        <p class="text-content mb-3">{{ question.question }}</p>

                                        <div v-if="question.type === 'multiple-choice'" class="space-y-2">
                                            <div
                                                v-for="(option, index) in question.options"
                                                :key="index"
                                                :class="`p-2 rounded-control border ${
                                                    index === question.correctAnswer
                                                        ? 'border-success-fg/30 bg-success-bg text-success-fg'
                                                        : 'border-line'
                                                }`"
                                            >
                                                <span class="text-sm">{{ option }}</span>
                                                <CheckIcon v-if="index === question.correctAnswer" class="inline-block ml-2 w-4 h-4 text-success-fg" />
                                            </div>
                                        </div>

                                        <div v-if="question.explanation" class="mt-3 p-3 bg-primary-soft rounded-control text-sm text-primary">
                                            <strong>Explanation:</strong> {{ question.explanation }}
                                        </div>
                                    </div>
                                </div>

                                <p v-if="generatedContent.questions.length > 3" class="text-center text-sm text-content-muted mt-4">
                                    +{{ generatedContent.questions.length - 3 }} more questions in full preview
                                </p>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Assignment Creator Tab -->
                <div v-if="activeTab === 'assignment'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Assignment Form -->
                        <Card title="Assignment Creator" :icon="DocumentTextIcon">
                            <div class="space-y-6">
                                <!-- Basic Info -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label">Assignment Title</label>
                                        <input
                                            v-model="assignmentForm.title"
                                            type="text"
                                            placeholder="e.g., Database Design Project"
                                            class="ui-input"
                                        />
                                    </div>
                                    <div>
                                        <label class="ui-label">Course</label>
                                        <select
                                            v-model="assignmentForm.course"
                                            class="ui-input"
                                        >
                                            <option value="">Select Course</option>
                                            <option v-for="course in facultyContext.courses" :key="course" :value="course">
                                                {{ course }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Assignment Type and Difficulty -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label">Assignment Type</label>
                                        <select
                                            v-model="assignmentForm.type"
                                            class="ui-input"
                                        >
                                            <option v-for="type in assignmentTypes" :key="type.value" :value="type.value">
                                                {{ type.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label">Difficulty Level</label>
                                        <select
                                            v-model="assignmentForm.difficulty"
                                            class="ui-input"
                                        >
                                            <option v-for="level in difficultyOptions" :key="level.value" :value="level.value">
                                                {{ level.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Topics Selection -->
                                <div>
                                    <label class="ui-label">Topics to Cover</label>
                                    <div v-if="assignmentForm.course" class="space-y-3">
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <button
                                                v-for="topic in availableTopics"
                                                :key="topic"
                                                @click="assignmentForm.topics.includes(topic) ? removeTopic(topic) : addTopic(topic)"
                                                :class="`flex items-center justify-center gap-1 p-2 text-sm rounded-control border transition-colors ${
                                                    assignmentForm.topics.includes(topic)
                                                        ? 'bg-primary-soft text-primary border-primary'
                                                        : 'bg-bg text-content-muted border-line hover:bg-neutral-bg'
                                                }`"
                                            >
                                                {{ topic }}
                                                <CheckIcon v-if="assignmentForm.topics.includes(topic)" class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                        <div v-if="assignmentForm.topics.length > 0" class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm text-content-muted">Selected:</span>
                                            <Badge
                                                v-for="topic in assignmentForm.topics"
                                                :key="topic"
                                                variant="success"
                                            >
                                                {{ topic }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-content-muted">Select a course first to see available topics</p>
                                </div>

                                <!-- Duration and Points -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label">Duration (days)</label>
                                        <input
                                            v-model="assignmentForm.duration"
                                            type="number"
                                            min="1"
                                            max="90"
                                            class="ui-input"
                                        />
                                    </div>
                                    <div>
                                        <label class="ui-label">Total Points</label>
                                        <input
                                            v-model="assignmentForm.points"
                                            type="number"
                                            min="10"
                                            max="500"
                                            class="ui-input"
                                        />
                                    </div>
                                </div>

                                <!-- Additional Options -->
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            v-model="assignmentForm.allowGroup"
                                            type="checkbox"
                                            class="rounded border-line text-primary focus:ring-primary"
                                        />
                                        <span class="ml-3 text-sm text-content">Allow group work</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="assignmentForm.includeRubric"
                                            type="checkbox"
                                            class="rounded border-line text-primary focus:ring-primary"
                                        />
                                        <span class="ml-3 text-sm text-content">Include grading rubric</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="assignmentForm.includeResources"
                                            type="checkbox"
                                            class="rounded border-line text-primary focus:ring-primary"
                                        />
                                        <span class="ml-3 text-sm text-content">Include recommended resources</span>
                                    </label>
                                </div>

                                <!-- Generate Button -->
                                <button
                                    @click="generateAssignment"
                                    :disabled="!isAssignmentFormValid || isGenerating"
                                    class="ui-btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <SparklesIcon class="w-5 h-5" />
                                    <span v-if="isGenerating">Creating Assignment...</span>
                                    <span v-else>Generate Assignment</span>
                                </button>
                            </div>
                        </Card>

                        <!-- Assignment Preview -->
                        <Card>
                            <EmptyState
                                v-if="!generatedContent || generatedContent.type !== 'assignment'"
                                title="Assignment Preview"
                                description="Create an assignment to see the structured preview here"
                                :icon="DocumentTextIcon"
                            />

                            <div v-else>
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-content">Assignment Preview</h3>
                                    <div class="flex gap-2">
                                        <button
                                            @click="editAssignment"
                                            class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                            aria-label="Edit assignment"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="regenerateContent"
                                            class="p-2 text-content-muted hover:text-primary hover:bg-primary-soft rounded-control transition-colors"
                                            aria-label="Regenerate assignment"
                                        >
                                            <ArrowPathIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Assignment Overview -->
                                <div class="bg-bg border border-line rounded-control p-4 mb-6">
                                    <h4 class="font-semibold text-content mb-2">{{ generatedContent.title }}</h4>
                                    <p class="text-sm text-content-muted mb-3">{{ generatedContent.description }}</p>
                                    <div class="grid grid-cols-2 gap-4 text-sm text-content">
                                        <div><strong>Due:</strong> {{ generatedContent.dueDate }}</div>
                                        <div><strong>Points:</strong> {{ generatedContent.totalPoints }}</div>
                                        <div><strong>Duration:</strong> {{ generatedContent.duration }} days</div>
                                        <div><strong>Group Work:</strong> {{ generatedContent.groupWork ? 'Allowed' : 'Individual' }}</div>
                                    </div>
                                </div>

                                <!-- Assignment Sections Preview -->
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div
                                        v-for="section in generatedContent.sections.slice(0, 2)"
                                        :key="section.title"
                                        class="border border-line rounded-control p-4"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h5 class="font-semibold text-content">{{ section.title }}</h5>
                                            <span class="text-sm text-content-muted">{{ section.points }} points</span>
                                        </div>
                                        <p class="text-sm text-content-muted mb-3">{{ section.description }}</p>

                                        <div class="space-y-2">
                                            <div class="text-xs text-content font-medium">Requirements:</div>
                                            <ul class="text-xs text-content-muted space-y-1">
                                                <li v-for="req in section.requirements.slice(0, 2)" :key="req" class="flex items-start gap-2">
                                                    <span class="text-primary mt-0.5">•</span>
                                                    {{ req }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="generatedContent.sections.length > 2" class="text-center text-sm text-content-muted mt-4">
                                    +{{ generatedContent.sections.length - 2 }} more sections in full preview
                                </p>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Full Preview Tab -->
                <div v-if="activeTab === 'preview' && generatedContent" class="space-y-8">
                    <Card>
                        <!-- Preview Header -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
                            <div>
                                <h2 class="text-2xl font-bold text-content mb-2">
                                    {{ generatedContent.title }}
                                </h2>
                                <div class="flex flex-wrap items-center gap-4 text-sm text-content-muted">
                                    <span class="flex items-center gap-1">
                                        <ClockIcon class="w-4 h-4" />
                                        {{ generatedContent.type === 'quiz' ? `${generatedContent.timeLimit} minutes` : `${generatedContent.duration} days` }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <UserIcon class="w-4 h-4" />
                                        {{ generatedContent.course }}
                                    </span>
                                    <span v-if="generatedContent.type === 'quiz'" class="flex items-center gap-1">
                                        <DocumentTextIcon class="w-4 h-4" />
                                        {{ generatedContent.totalQuestions }} questions
                                    </span>
                                    <span v-else class="flex items-center gap-1">
                                        <StarIcon class="w-4 h-4" />
                                        {{ generatedContent.totalPoints }} points
                                    </span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    @click="generatedContent.type === 'quiz' ? editQuiz() : editAssignment()"
                                    class="ui-btn-secondary"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                    Edit
                                </button>
                                <button
                                    @click="regenerateContent"
                                    class="ui-btn-ghost"
                                >
                                    <ArrowPathIcon class="w-4 h-4" />
                                    Regenerate
                                </button>
                                <button
                                    @click="exportGenerated"
                                    class="ui-btn-secondary"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4" />
                                    Export PDF
                                </button>
                                <button
                                    @click="publishContent"
                                    class="ui-btn-primary"
                                >
                                    <CheckIcon class="w-4 h-4" />
                                    Publish
                                </button>
                            </div>
                        </div>

                        <!-- Quiz Preview Content -->
                        <div v-if="generatedContent.type === 'quiz'">
                            <!-- Quiz Instructions -->
                            <div class="bg-primary-soft border border-line rounded-card p-6 mb-8">
                                <h3 class="text-lg font-semibold text-primary mb-3 flex items-center gap-2">
                                    <InformationCircleIcon class="w-5 h-5" />
                                    Instructions
                                </h3>
                                <p class="text-content-muted">{{ generatedContent.instructions }}</p>
                            </div>

                            <!-- All Questions -->
                            <div class="space-y-6">
                                <h3 class="text-xl font-bold text-content">Questions</h3>
                                <div
                                    v-for="question in generatedContent.questions"
                                    :key="question.id"
                                    class="border border-line rounded-card p-6"
                                >
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <span class="w-8 h-8 bg-primary-soft text-primary rounded-full flex items-center justify-center text-sm font-bold">
                                                {{ question.id }}
                                            </span>
                                            <div>
                                                <span class="text-sm text-content-muted uppercase">{{ question.type.replace('-', ' ') }}</span>
                                                <div class="text-sm text-content-muted">{{ question.points }} points</div>
                                            </div>
                                        </div>
                                        <button
                                            @click="deleteQuestion(question.id)"
                                            class="p-1 text-content-faint hover:text-danger-fg rounded-control"
                                            aria-label="Delete question"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <p class="text-lg text-content mb-4">{{ question.question }}</p>

                                    <!-- Multiple Choice Options -->
                                    <div v-if="question.type === 'multiple-choice'" class="space-y-3 mb-4">
                                        <div
                                            v-for="(option, index) in question.options"
                                            :key="index"
                                            :class="`flex items-center gap-3 p-3 rounded-control border transition-colors ${
                                                index === question.correctAnswer
                                                    ? 'border-success-fg/30 bg-success-bg'
                                                    : 'border-line bg-bg'
                                            }`"
                                        >
                                            <span :class="`w-6 h-6 rounded-full border-2 flex items-center justify-center text-sm font-bold ${
                                                index === question.correctAnswer
                                                    ? 'border-success-fg bg-success-fg text-white'
                                                    : 'border-line'
                                            }`">
                                                {{ String.fromCharCode(65 + index) }}
                                            </span>
                                            <span :class="index === question.correctAnswer ? 'text-success-fg font-medium' : 'text-content'">
                                                {{ option }}
                                            </span>
                                            <CheckIcon v-if="index === question.correctAnswer" class="w-5 h-5 text-success-fg ml-auto" />
                                        </div>
                                    </div>

                                    <!-- True/False Options -->
                                    <div v-if="question.type === 'true-false'" class="flex gap-4 mb-4">
                                        <div :class="`flex items-center gap-2 p-3 rounded-control border ${
                                            question.correctAnswer === true
                                                ? 'border-success-fg/30 bg-success-bg'
                                                : 'border-line'
                                        }`">
                                            <span class="font-medium text-content">True</span>
                                            <CheckIcon v-if="question.correctAnswer === true" class="w-4 h-4 text-success-fg" />
                                        </div>
                                        <div :class="`flex items-center gap-2 p-3 rounded-control border ${
                                            question.correctAnswer === false
                                                ? 'border-success-fg/30 bg-success-bg'
                                                : 'border-line'
                                        }`">
                                            <span class="font-medium text-content">False</span>
                                            <CheckIcon v-if="question.correctAnswer === false" class="w-4 h-4 text-success-fg" />
                                        </div>
                                    </div>

                                    <!-- Explanation -->
                                    <div v-if="question.explanation" class="bg-primary-soft border border-line rounded-control p-4">
                                        <h5 class="font-semibold text-primary mb-2">Explanation</h5>
                                        <p class="text-content-muted text-sm">{{ question.explanation }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Preview Content -->
                        <div v-if="generatedContent.type === 'assignment'">
                            <!-- Assignment Overview -->
                            <div class="bg-primary-soft border border-line rounded-card p-6 mb-8">
                                <h3 class="text-lg font-semibold text-primary mb-3">Assignment Overview</h3>
                                <p class="text-content-muted mb-4">{{ generatedContent.description }}</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div class="bg-surface p-3 rounded-control border border-line">
                                        <div class="font-semibold text-content">Due Date</div>
                                        <div class="text-content-muted">{{ generatedContent.dueDate }}</div>
                                    </div>
                                    <div class="bg-surface p-3 rounded-control border border-line">
                                        <div class="font-semibold text-content">Total Points</div>
                                        <div class="text-content-muted">{{ generatedContent.totalPoints }}</div>
                                    </div>
                                    <div class="bg-surface p-3 rounded-control border border-line">
                                        <div class="font-semibold text-content">Duration</div>
                                        <div class="text-content-muted">{{ generatedContent.duration }} days</div>
                                    </div>
                                    <div class="bg-surface p-3 rounded-control border border-line">
                                        <div class="font-semibold text-content">Work Type</div>
                                        <div class="text-content-muted">{{ generatedContent.groupWork ? 'Group' : 'Individual' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Sections -->
                            <div class="space-y-3">
                                <div
                                    v-for="section in generatedContent.sections.slice(0, 3)"
                                    :key="section.title"
                                    class="bg-surface rounded-control p-4 border border-line"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-content">{{ section.title }}</h4>
                                        <span class="text-xs text-content-muted">{{ section.points }} points</span>
                                    </div>
                                    <p class="text-sm text-content-muted mb-3">{{ section.description }}</p>

                                    <!-- Section Requirements -->
                                    <div v-if="section.requirements && section.requirements.length > 0" class="space-y-2">
                                        <p class="text-xs font-medium text-content">Requirements:</p>
                                        <ul class="text-xs text-content-muted space-y-1">
                                            <li v-for="req in section.requirements" :key="req" class="flex items-start gap-2">
                                                <span class="text-primary mt-0.5">•</span>
                                                {{ req }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Deliverables -->
                                    <div v-if="section.deliverables && section.deliverables.length > 0" class="mt-3 space-y-2">
                                        <p class="text-xs font-medium text-content">Deliverables:</p>
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="deliverable in section.deliverables"
                                                :key="deliverable"
                                                class="px-2 py-1 bg-success-bg text-success-fg rounded-pill text-xs"
                                            >
                                                {{ deliverable }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Show More/Less Button for Sections -->
                            <button
                                v-if="generatedContent.sections.length > 3"
                                @click="showAllSections = !showAllSections"
                                class="w-full py-2 text-sm text-primary hover:bg-primary-soft rounded-control transition-colors mt-3"
                            >
                                {{ showAllSections ? 'Show Less' : `Show ${generatedContent.sections.length - 3} More Sections` }}
                            </button>

                            <!-- Additional Sections (when expanded) -->
                            <div v-if="showAllSections && generatedContent.sections.length > 3" class="space-y-3 mt-3">
                                <div
                                    v-for="section in generatedContent.sections.slice(3)"
                                    :key="section.title"
                                    class="bg-surface rounded-control p-4 border border-line"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-content">{{ section.title }}</h4>
                                        <span class="text-xs text-content-muted">{{ section.points }} points</span>
                                    </div>
                                    <p class="text-sm text-content-muted mb-3">{{ section.description }}</p>

                                    <!-- Requirements -->
                                    <div v-if="section.requirements && section.requirements.length > 0" class="space-y-2">
                                        <p class="text-xs font-medium text-content">Requirements:</p>
                                        <ul class="text-xs text-content-muted space-y-1">
                                            <li v-for="req in section.requirements" :key="req" class="flex items-start gap-2">
                                                <span class="text-primary mt-0.5">•</span>
                                                {{ req }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Deliverables -->
                                    <div v-if="section.deliverables && section.deliverables.length > 0" class="mt-3 space-y-2">
                                        <p class="text-xs font-medium text-content">Deliverables:</p>
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="deliverable in section.deliverables"
                                                :key="deliverable"
                                                class="px-2 py-1 bg-success-bg text-success-fg rounded-pill text-xs"
                                            >
                                                {{ deliverable }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grading Rubric -->
                            <div v-if="generatedContent.rubric" class="mt-6 bg-warning-bg border border-line rounded-card p-4">
                                <h4 class="font-semibold text-content mb-3 flex items-center gap-2">
                                    <StarIcon class="w-4 h-4 text-warning-fg" />
                                    Grading Rubric
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div
                                        v-for="criteria in generatedContent.rubric"
                                        :key="criteria.category"
                                        class="bg-surface rounded-control p-3 border border-line"
                                    >
                                        <h5 class="font-medium text-content text-sm mb-1">{{ criteria.category }}</h5>
                                        <p class="text-xs text-content-muted mb-2">{{ criteria.description }}</p>
                                        <div class="text-xs font-medium text-warning-fg">{{ criteria.weight }}% of grade</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submission Guidelines -->
                            <div v-if="generatedContent.submissionGuidelines" class="mt-6 bg-primary-soft border border-line rounded-card p-4">
                                <h4 class="font-semibold text-content mb-3 flex items-center gap-2">
                                    <ArrowUpTrayIcon class="w-4 h-4 text-primary" />
                                    Submission Guidelines
                                </h4>
                                <div class="space-y-2">
                                    <div v-for="guideline in generatedContent.submissionGuidelines" :key="guideline.type" class="flex items-start gap-2">
                                        <CheckCircleIcon class="w-4 h-4 text-success-fg mt-0.5 flex-shrink-0" />
                                        <div>
                                            <span class="font-medium text-content text-sm">{{ guideline.type }}:</span>
                                            <span class="text-sm text-content-muted ml-1">{{ guideline.detail }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommended Resources -->
                            <div v-if="generatedContent.resources && generatedContent.resources.length > 0" class="mt-6 bg-primary-soft border border-line rounded-card p-4">
                                <h4 class="font-semibold text-content mb-3 flex items-center gap-2">
                                    <BookOpenIcon class="w-4 h-4 text-primary" />
                                    Recommended Resources
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div
                                        v-for="resource in generatedContent.resources"
                                        :key="resource.title"
                                        class="bg-surface rounded-control p-3 border border-line"
                                    >
                                        <h5 class="font-medium text-content text-sm mb-1">{{ resource.title }}</h5>
                                        <p class="text-xs text-content-muted mb-2">{{ resource.description }}</p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-primary font-medium">{{ resource.type }}</span>
                                            <button
                                                v-if="resource.url"
                                                @click="window.open(resource.url, '_blank')"
                                                class="text-xs text-primary hover:underline"
                                            >
                                                View →
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-6 mt-6 border-t border-line">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button
                                        @click="editAssignment"
                                        class="ui-btn-secondary"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                        Edit Assignment
                                    </button>
                                    <button
                                        @click="regenerateContent"
                                        class="ui-btn-ghost"
                                    >
                                        <ArrowPathIcon class="w-4 h-4" />
                                        Regenerate
                                    </button>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button
                                        @click="exportGenerated"
                                        class="ui-btn-secondary"
                                    >
                                        <ArrowDownTrayIcon class="w-4 h-4" />
                                        Export PDF
                                    </button>
                                    <button
                                        @click="publishContent"
                                        class="ui-btn-primary"
                                    >
                                        <CheckIcon class="w-4 h-4" />
                                        Publish Assignment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
/* Custom scrollbar for messages container */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

/* Typing indicator bounce */
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
</style>

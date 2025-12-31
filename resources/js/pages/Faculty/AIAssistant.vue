<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChatBubbleLeftRightIcon,
    SparklesIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ClipboardDocumentListIcon,
    PlusIcon,
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
    Cog6ToothIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    LightBulbIcon,
    BeakerIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

// Component state
const activeTab = ref('chat');
const isTyping = ref(false);
const isGenerating = ref(false);
const currentMessage = ref('');
const messagesContainer = ref(null);
const showAllSections = ref(false);

// Faculty context
const facultyContext = ref({
    name: 'Dr. Sarah Smith',
    department: 'Computer Science Engineering',
    courses: ['Database Systems', 'Machine Learning', 'Software Engineering'],
    experience: '8 years'
});

// Chat messages
const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content: `Hello **Dr. ${facultyContext.value.name.split(' ')[1]}**! 👋

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
    { value: 'multiple-choice', label: 'Multiple Choice', icon: '⚪' },
    { value: 'true-false', label: 'True/False', icon: '✓' },
    { value: 'short-answer', label: 'Short Answer', icon: '📝' },
    { value: 'essay', label: 'Essay', icon: '📄' },
    { value: 'fill-in-blank', label: 'Fill in the Blank', icon: '🔤' },
    { value: 'matching', label: 'Matching', icon: '🔗' }
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
    const userInput = currentMessage.value;
    currentMessage.value = '';

    isTyping.value = true;
    scrollToBottom();

    setTimeout(() => {
        const aiResponse = generateTeachingResponse(userInput);
        messages.value.push(aiResponse);
        isTyping.value = false;
        scrollToBottom();
    }, 1500);
};

const generateTeachingResponse = (userInput) => {
    const responseId = Date.now() + 1;
    let content;

    if (userInput.toLowerCase().includes('quiz') || userInput.toLowerCase().includes('questions')) {
        content = `**Quiz Creation Assistant:**

I can help you create a comprehensive quiz! Here are some options:

**For "${userInput}":**
• **Auto-Generate:** Use the Quiz Generator tab for instant quiz creation
• **Question Types:** Multiple choice, true/false, short answer, essay questions
• **Difficulty Levels:** Adjust complexity based on your students' level
• **Bloom's Taxonomy:** Align questions with specific learning objectives

**Quick Quiz Ideas:**
• **Conceptual Questions:** Test understanding of key principles
• **Application Problems:** Real-world scenarios and case studies
• **Analysis Questions:** Compare and contrast different approaches
• **Synthesis Tasks:** Combine concepts to solve complex problems

**Pro Tips:**
• Start with 5-10 questions for shorter quizzes
• Mix question types to assess different skills
• Include explanations to enhance learning
• Set appropriate time limits based on complexity

Would you like me to generate a specific quiz, or do you need help with question design strategies?`;
    } else if (userInput.toLowerCase().includes('assignment') || userInput.toLowerCase().includes('project')) {
        content = `**Assignment Design Helper:**

Creating effective assignments is key to student learning! Let me help you design something impactful.

**For "${userInput}":**
• **Project-Based:** Hands-on application of course concepts
• **Research-Oriented:** Deep dive into specific topics
• **Collaborative:** Team-based learning experiences
• **Portfolio:** Cumulative skill demonstration

**Assignment Structure:**
• **Clear Objectives:** What students will learn/demonstrate
• **Detailed Instructions:** Step-by-step guidance
• **Grading Rubric:** Transparent evaluation criteria
• **Timeline:** Manageable milestones and deadlines

**Best Practices:**
• Align with course learning outcomes
• Provide examples and resources
• Include self-assessment opportunities
• Offer multiple submission formats when appropriate

**Assessment Ideas:**
• Real-world problem solving
• Case study analysis
• Creative presentations
• Peer review processes

Head to the Assignment Creator tab to build a complete assignment with rubrics and guidelines!`;
    } else if (userInput.toLowerCase().includes('explain') || userInput.toLowerCase().includes('teach')) {
        content = `**Teaching Strategy Assistant:**

Explaining complex concepts effectively is an art! Let me suggest some proven approaches for "${userInput}":

**Pedagogical Strategies:**
• **Analogies & Metaphors:** Connect new concepts to familiar experiences
• **Scaffolding:** Break complex topics into manageable chunks
• **Visual Learning:** Use diagrams, flowcharts, and mind maps
• **Active Learning:** Engage students through hands-on activities

**Explanation Techniques:**
• **Tell-Show-Do:** Demonstrate, then let students practice
• **Think-Pair-Share:** Collaborative concept exploration
• **Real-World Examples:** Concrete applications of abstract ideas
• **Progressive Disclosure:** Reveal complexity gradually

**For Different Learning Styles:**
• **Visual Learners:** Charts, graphs, color coding
• **Auditory Learners:** Discussion, verbal explanations, music
• **Kinesthetic Learners:** Hands-on activities, movement
• **Reading/Writing:** Note-taking, written exercises

**Engagement Boosters:**
• Start with a hook or interesting question
• Use storytelling to make content memorable
• Encourage questions and curiosity
• Provide immediate feedback and reinforcement

What specific concept would you like help explaining to your students?`;
    } else {
        content = `**Teaching Insight:**

Thank you for your question about "${userInput}". As your AI Teaching Assistant, I'm here to help you create engaging and effective learning experiences.

**How I can assist you:**
• **Curriculum Planning:** Develop course outlines and learning sequences
• **Content Creation:** Generate quizzes, assignments, and activities
• **Teaching Strategies:** Suggest pedagogical approaches for specific topics
• **Assessment Design:** Create rubrics and evaluation criteria
• **Student Engagement:** Recommend interactive and collaborative techniques

**Quick Actions:**
• Use specific prompts like "How do I teach [concept] to [level] students?"
• Ask for "Create a quiz about [topic]" or "Design an assignment for [subject]"
• Request "Explain [difficult concept] using analogies"
• Say "Help me assess [skill] in my students"

**Pro Tips:**
• Be specific about your course level and student background
• Mention any particular challenges you're facing
• Let me know your preferred teaching style or constraints

What specific teaching challenge can I help you with today?`;
    }

    return {
        id: responseId,
        role: 'assistant',
        content,
        timestamp: new Date().toISOString(),
        suggestions: [
            'Generate a quiz for my next class',
            'Create an assignment rubric',
            'Explain a complex concept simply',
            'Design interactive learning activities'
        ]
    };
};

// Quiz generation
const generateQuiz = () => {
    if (!isQuizFormValid.value) return;

    isGenerating.value = true;

    setTimeout(() => {
        generatedContent.value = {
            type: 'quiz',
            title: `${quizForm.value.topic} Quiz - ${quizForm.value.course}`,
            course: quizForm.value.course,
            topic: quizForm.value.topic,
            difficulty: quizForm.value.difficulty,
            totalQuestions: quizForm.value.questionCount,
            timeLimit: quizForm.value.timeLimit,
            instructions: `This quiz covers ${quizForm.value.topic} concepts for ${quizForm.value.course}. You have ${quizForm.value.timeLimit} minutes to complete ${quizForm.value.questionCount} questions.`,

            questions: generateMockQuestions()
        };

        isGenerating.value = false;
        activeTab.value = 'preview';
    }, 2000);
};

const generateMockQuestions = () => {
    const questions = [];
    const questionCount = Math.min(quizForm.value.questionCount, 15);

    for (let i = 1; i <= questionCount; i++) {
        if (quizForm.value.questionTypes.includes('multiple-choice') && i <= questionCount / 2) {
            questions.push({
                id: i,
                type: 'multiple-choice',
                question: `Which of the following best describes ${quizForm.value.topic}?`,
                options: [
                    'A fundamental concept in database design',
                    'A method for optimizing queries',
                    'A technique for data storage',
                    'A security protocol'
                ],
                correctAnswer: 0,
                explanation: quizForm.value.includeExplanations ? `This question tests understanding of ${quizForm.value.topic} fundamentals.` : null,
                points: 2,
                difficulty: quizForm.value.difficulty
            });
        } else if (quizForm.value.questionTypes.includes('true-false')) {
            questions.push({
                id: i,
                type: 'true-false',
                question: `${quizForm.value.topic} is essential for database integrity.`,
                correctAnswer: true,
                explanation: quizForm.value.includeExplanations ? `This statement is correct because ${quizForm.value.topic} ensures data consistency.` : null,
                points: 1,
                difficulty: quizForm.value.difficulty
            });
        }
    }

    return questions;
};

// Assignment generation
const generateAssignment = () => {
    if (!isAssignmentFormValid.value) return;

    isGenerating.value = true;

    setTimeout(() => {
        generatedContent.value = {
            type: 'assignment',
            title: `${assignmentForm.value.title} - ${assignmentForm.value.course}`,
            description: `A comprehensive ${assignmentForm.value.type.toLowerCase()} focusing on ${assignmentForm.value.topics.join(', ')} with ${assignmentForm.value.difficulty} difficulty level.`,
            course: assignmentForm.value.course,
            type: assignmentForm.value.type,
            topics: assignmentForm.value.topics,
            duration: assignmentForm.value.duration,
            totalPoints: parseInt(assignmentForm.value.points),
            dueDate: new Date(Date.now() + parseInt(assignmentForm.value.duration) * 24 * 60 * 60 * 1000).toLocaleDateString(),
            groupWork: assignmentForm.value.allowGroup,

            sections: [
                {
                    title: 'Problem Analysis & Planning',
                    description: 'Analyze the given problem statement and create a comprehensive solution plan.',
                    points: 25,
                    requirements: [
                        'Identify key requirements and constraints',
                        'Develop a step-by-step approach',
                        'Consider edge cases and potential challenges'
                    ],
                    deliverables: ['Problem analysis document', 'Solution strategy']
                },
                {
                    title: 'Implementation',
                    description: 'Implement the solution using appropriate technologies and best practices.',
                    points: 40,
                    requirements: [
                        'Follow coding standards and conventions',
                        'Implement efficient algorithms',
                        'Add appropriate comments and documentation'
                    ],
                    deliverables: ['Source code', 'Documentation', 'Unit tests']
                },
                {
                    title: 'Testing & Validation',
                    description: 'Thoroughly test your implementation and validate results.',
                    points: 20,
                    requirements: [
                        'Create comprehensive test cases',
                        'Validate output correctness',
                        'Test edge cases and error handling'
                    ],
                    deliverables: ['Test cases', 'Test results', 'Error handling documentation']
                },
                {
                    title: 'Report & Documentation',
                    description: 'Create a detailed report explaining your approach and findings.',
                    points: 15,
                    requirements: [
                        'Explain design decisions',
                        'Discuss challenges and solutions',
                        'Include performance analysis'
                    ],
                    deliverables: ['Technical report', 'User documentation']
                }
            ],

            rubric: assignmentForm.value.includeRubric ? [
                {
                    category: 'Technical Excellence',
                    description: 'Code quality, efficiency, and correctness',
                    weight: 40
                },
                {
                    category: 'Problem Solving',
                    description: 'Approach to problem analysis and solution design',
                    weight: 30
                },
                {
                    category: 'Documentation',
                    description: 'Quality of comments, reports, and documentation',
                    weight: 20
                },
                {
                    category: 'Presentation',
                    description: 'Code organization and readability',
                    weight: 10
                }
            ] : null,

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

        isGenerating.value = false;
        activeTab.value = 'preview';
    }, 2000);
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

// Lifecycle
onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <div>
        <Head title="Faculty AI Teaching Assistant" />

        <AppLayout>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-900 dark:via-emerald-900 dark:to-teal-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                                    <SparklesIcon class="w-10 h-10" />
                                    AI Teaching Assistant
                                </h1>
                                <p class="text-xl text-white/90 mb-2">
                                    Intelligent content creation and teaching support
                                </p>
                                <p class="text-white/80">
                                    Generate quizzes, assignments, and get teaching insights powered by AI
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Faculty Context -->
                                <div class="bg-white/20 backdrop-blur-lg rounded-xl px-6 py-4 text-white text-center">
                                    <div class="text-lg font-bold">{{ facultyContext.name }}</div>
                                    <div class="text-xs text-white/80">{{ facultyContext.department }}</div>
                                </div>

                                <Link
                                    href="/faculty/dashboard"
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
                                    @click="activeTab = 'chat'"
                                    :class="`px-6 py-4 text-sm font-medium transition-colors ${
                                        activeTab === 'chat'
                                            ? 'bg-green-50 text-green-700 border-b-2 border-green-600 dark:bg-green-900/20 dark:text-green-400'
                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`"
                                >
                                    <ChatBubbleLeftRightIcon class="w-5 h-5 inline mr-2" />
                                    AI Chat Assistant
                                </button>
                                <button
                                    @click="activeTab = 'quiz'"
                                    :class="`px-6 py-4 text-sm font-medium transition-colors ${
                                        activeTab === 'quiz'
                                            ? 'bg-green-50 text-green-700 border-b-2 border-green-600 dark:bg-green-900/20 dark:text-green-400'
                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`"
                                >
                                    <ClipboardDocumentListIcon class="w-5 h-5 inline mr-2" />
                                    Quiz Generator
                                </button>
                                <button
                                    @click="activeTab = 'assignment'"
                                    :class="`px-6 py-4 text-sm font-medium transition-colors ${
                                        activeTab === 'assignment'
                                            ? 'bg-green-50 text-green-700 border-b-2 border-green-600 dark:bg-green-900/20 dark:text-green-400'
                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`"
                                >
                                    <DocumentTextIcon class="w-5 h-5 inline mr-2" />
                                    Assignment Creator
                                </button>
                                <button
                                    v-if="generatedContent"
                                    @click="activeTab = 'preview'"
                                    :class="`px-6 py-4 text-sm font-medium transition-colors ${
                                        activeTab === 'preview'
                                            ? 'bg-green-50 text-green-700 border-b-2 border-green-600 dark:bg-green-900/20 dark:text-green-400'
                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`"
                                >
                                    <EyeIcon class="w-5 h-5 inline mr-2" />
                                    Preview
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Chat Assistant Tab -->
                    <div v-if="activeTab === 'chat'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Chat Interface -->
                            <div class="lg:col-span-2">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg h-[600px] flex flex-col">
                                    <!-- Chat Header -->
                                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <SparklesIcon class="w-6 h-6 text-green-600" />
                                            AI Teaching Assistant
                                        </h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Ask questions about teaching strategies, content creation, and student engagement
                                        </p>
                                    </div>

                                    <!-- Messages -->
                                    <div
                                        ref="messagesContainer"
                                        class="flex-1 overflow-y-auto p-6 space-y-4"
                                    >
                                        <div v-for="message in messages" :key="message.id" class="space-y-4">
                                            <!-- User Message -->
                                            <div v-if="message.role === 'user'" class="flex justify-end">
                                                <div class="max-w-[80%] bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl rounded-br-md px-6 py-4 shadow-lg">
                                                    <p class="text-sm leading-relaxed">{{ message.content }}</p>
                                                    <div class="text-xs text-white/80 mt-2">{{ formatTime(message.timestamp) }}</div>
                                                </div>
                                            </div>

                                            <!-- AI Assistant Message -->
                                            <div v-else class="flex justify-start">
                                                <div class="max-w-[90%] bg-gray-50 dark:bg-gray-900 rounded-2xl rounded-bl-md shadow-lg overflow-hidden">
                                                    <!-- AI Header -->
                                                    <div class="p-4 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-b border-green-200 dark:border-green-800">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                                                <SparklesIcon class="w-4 h-4 text-white" />
                                                            </div>
                                                            <div>
                                                                <p class="font-semibold text-green-800 dark:text-green-200 text-sm">AI Teaching Assistant</p>
                                                                <p class="text-xs text-green-600 dark:text-green-400">{{ formatTime(message.timestamp) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Message Content -->
                                                    <div class="p-6">
                                                        <div
                                                            class="prose prose-sm dark:prose-invert max-w-none"
                                                            v-html="message.content.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/•\s/g, '<span class=&quot;text-green-600 dark:text-green-400 font-bold&quot;>•</span> ')"
                                                        ></div>

                                                        <!-- Follow-up Suggestions -->
                                                        <div v-if="message.suggestions && message.suggestions.length > 0" class="mt-6">
                                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Try asking:</p>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                <button
                                                                    v-for="suggestion in message.suggestions"
                                                                    :key="suggestion"
                                                                    @click="currentMessage = suggestion; sendMessage()"
                                                                    class="text-left px-4 py-3 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm rounded-xl hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors border border-green-200 dark:border-green-800"
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
                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl rounded-bl-md px-6 py-4 shadow-lg">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                                        <SparklesIcon class="w-4 h-4 text-white" />
                                                    </div>
                                                    <div class="flex space-x-1">
                                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">AI is thinking...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Input -->
                                    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                                        <form @submit.prevent="sendMessage" class="flex gap-3">
                                            <textarea
                                                v-model="currentMessage"
                                                :disabled="isTyping"
                                                placeholder="Ask about teaching strategies, content creation, or student engagement..."
                                                rows="1"
                                                class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"
                                                @keydown.enter.exact.prevent="sendMessage"
                                            ></textarea>
                                            <button
                                                type="submit"
                                                :disabled="!currentMessage.trim() || isTyping"
                                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                            >
                                                <span v-if="isTyping">...</span>
                                                <span v-else>Send</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions Sidebar -->
                            <div class="space-y-6">
                                <!-- Faculty Quick Stats -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Your Courses</h3>
                                    <div class="space-y-3">
                                        <div
                                            v-for="course in facultyContext.courses"
                                            :key="course"
                                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
                                        >
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ course }}</span>
                                            <AcademicCapIcon class="w-4 h-4 text-gray-400" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                                    <div class="space-y-3">
                                        <button
                                            @click="activeTab = 'quiz'"
                                            class="w-full flex items-center gap-3 p-3 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                                        >
                                            <ClipboardDocumentListIcon class="w-5 h-5" />
                                            Generate Quiz
                                        </button>
                                        <button
                                            @click="activeTab = 'assignment'"
                                            class="w-full flex items-center gap-3 p-3 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors"
                                        >
                                            <DocumentTextIcon class="w-5 h-5" />
                                            Create Assignment
                                        </button>
                                        <button
                                            @click="clearForms"
                                            class="w-full flex items-center gap-3 p-3 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                            Clear All Forms
                                        </button>
                                    </div>
                                </div>

                                <!-- Teaching Tips -->
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <LightBulbIcon class="w-5 h-5 text-yellow-600" />
                                        Teaching Tip
                                    </h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                        Use the Bloom's taxonomy levels when creating questions to ensure comprehensive assessment of student understanding.
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs">Remember</span>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs">Apply</span>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs">Analyze</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quiz Generator Tab -->
                    <div v-if="activeTab === 'quiz'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Quiz Form -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
                                    Quiz Generator
                                </h2>

                                <div class="space-y-6">
                                    <!-- Course and Topic -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Course</label>
                                            <select
                                                v-model="quizForm.course"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="">Select Course</option>
                                                <option v-for="course in facultyContext.courses" :key="course" :value="course">
                                                    {{ course }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Topic</label>
                                            <select
                                                v-model="quizForm.topic"
                                                :disabled="!quizForm.course"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
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
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Difficulty</label>
                                            <select
                                                v-model="quizForm.difficulty"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option v-for="level in difficultyOptions" :key="level.value" :value="level.value">
                                                    {{ level.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Questions</label>
                                            <input
                                                v-model.number="quizForm.questionCount"
                                                type="number"
                                                min="1"
                                                max="50"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Limit (min)</label>
                                            <input
                                                v-model.number="quizForm.timeLimit"
                                                type="number"
                                                min="5"
                                                max="180"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>

                                    <!-- Question Types -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Question Types</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <label
                                                v-for="type in questionTypeOptions"
                                                :key="type.value"
                                                class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900"
                                            >
                                                <input
                                                    v-model="quizForm.questionTypes"
                                                    type="checkbox"
                                                    :value="type.value"
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="ml-2 text-lg">{{ type.icon }}</span>
                                                <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ type.label }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Bloom's Taxonomy Level -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bloom's Taxonomy Level</label>
                                        <select
                                            v-model="quizForm.bloomLevel"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Include answer explanations</span>
                                        </label>
                                    </div>

                                    <!-- Generate Button -->
                                    <button
                                        @click="generateQuiz"
                                        :disabled="!isQuizFormValid || isGenerating"
                                        class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-medium"
                                    >
                                        <SparklesIcon class="w-5 h-5" />
                                        <span v-if="isGenerating">Generating Quiz...</span>
                                        <span v-else>Generate Quiz</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Quiz Preview -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                                <div v-if="!generatedContent || generatedContent.type !== 'quiz'" class="text-center py-12">
                                    <ClipboardDocumentListIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Quiz Preview</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        Fill out the form and generate a quiz to see the preview here
                                    </p>
                                </div>

                                <div v-else>
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Quiz Preview</h3>
                                        <div class="flex gap-2">
                                            <button
                                                @click="editQuiz"
                                                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            >
                                                <PencilIcon class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="regenerateContent"
                                                class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            >
                                                <ArrowPathIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Quiz Info -->
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ generatedContent.title }}</h4>
                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
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
                                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                                        >
                                            <div class="flex items-start justify-between mb-3">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">Question {{ question.id }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ question.points }} points</span>
                                            </div>
                                            <p class="text-gray-800 dark:text-gray-200 mb-3">{{ question.question }}</p>

                                            <div v-if="question.type === 'multiple-choice'" class="space-y-2">
                                                <div
                                                    v-for="(option, index) in question.options"
                                                    :key="index"
                                                    :class="`p-2 rounded border ${
                                                        index === question.correctAnswer
                                                            ? 'border-green-300 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200'
                                                            : 'border-gray-200 dark:border-gray-700'
                                                    }`"
                                                >
                                                    <span class="text-sm">{{ option }}</span>
                                                    <span v-if="index === question.correctAnswer" class="ml-2 text-green-600">✓</span>
                                                </div>
                                            </div>

                                            <div v-if="question.explanation" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-blue-800 dark:text-blue-200">
                                                <strong>Explanation:</strong> {{ question.explanation }}
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="generatedContent.questions.length > 3" class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
                                        +{{ generatedContent.questions.length - 3 }} more questions in full preview
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Creator Tab -->
                    <div v-if="activeTab === 'assignment'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Assignment Form -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <DocumentTextIcon class="w-6 h-6 text-green-600" />
                                    Assignment Creator
                                </h2>

                                <div class="space-y-6">
                                    <!-- Basic Info -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assignment Title</label>
                                            <input
                                                v-model="assignmentForm.title"
                                                type="text"
                                                placeholder="e.g., Database Design Project"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Course</label>
                                            <select
                                                v-model="assignmentForm.course"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
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
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assignment Type</label>
                                            <select
                                                v-model="assignmentForm.type"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                            >
                                                <option v-for="type in assignmentTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Difficulty Level</label>
                                            <select
                                                v-model="assignmentForm.difficulty"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                            >
                                                <option v-for="level in difficultyOptions" :key="level.value" :value="level.value">
                                                    {{ level.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Topics Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Topics to Cover</label>
                                        <div v-if="assignmentForm.course" class="space-y-3">
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <button
                                                    v-for="topic in availableTopics"
                                                    :key="topic"
                                                    @click="assignmentForm.topics.includes(topic) ? removeTopic(topic) : addTopic(topic)"
                                                    :class="`p-2 text-sm rounded-lg border transition-colors ${
                                                        assignmentForm.topics.includes(topic)
                                                            ? 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/30 dark:text-green-400 dark:border-green-600'
                                                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'
                                                    }`"
                                                >
                                                    {{ topic }}
                                                    <span v-if="assignmentForm.topics.includes(topic)" class="ml-1">✓</span>
                                                </button>
                                            </div>
                                            <div v-if="assignmentForm.topics.length > 0" class="flex flex-wrap gap-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Selected:</span>
                                                <span
                                                    v-for="topic in assignmentForm.topics"
                                                    :key="topic"
                                                    class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded text-xs"
                                                >
                                                    {{ topic }}
                                                </span>
                                            </div>
                                        </div>
                                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">Select a course first to see available topics</p>
                                    </div>

                                    <!-- Duration and Points -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (days)</label>
                                            <input
                                                v-model="assignmentForm.duration"
                                                type="number"
                                                min="1"
                                                max="90"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Points</label>
                                            <input
                                                v-model="assignmentForm.points"
                                                type="number"
                                                min="10"
                                                max="500"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                            />
                                        </div>
                                    </div>

                                    <!-- Additional Options -->
                                    <div class="space-y-3">
                                        <label class="flex items-center">
                                            <input
                                                v-model="assignmentForm.allowGroup"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            />
                                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Allow group work</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                v-model="assignmentForm.includeRubric"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            />
                                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Include grading rubric</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                v-model="assignmentForm.includeResources"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            />
                                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Include recommended resources</span>
                                        </label>
                                    </div>

                                    <!-- Generate Button -->
                                    <button
                                        @click="generateAssignment"
                                        :disabled="!isAssignmentFormValid || isGenerating"
                                        class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-medium"
                                    >
                                        <SparklesIcon class="w-5 h-5" />
                                        <span v-if="isGenerating">Creating Assignment...</span>
                                        <span v-else>Generate Assignment</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Assignment Preview -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                                <div v-if="!generatedContent || generatedContent.type !== 'assignment'" class="text-center py-12">
                                    <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Assignment Preview</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        Create an assignment to see the structured preview here
                                    </p>
                                </div>

                                <div v-else>
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Assignment Preview</h3>
                                        <div class="flex gap-2">
                                            <button
                                                @click="editAssignment"
                                                class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            >
                                                <PencilIcon class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="regenerateContent"
                                                class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            >
                                                <ArrowPathIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Assignment Overview -->
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ generatedContent.title }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ generatedContent.description }}</p>
                                        <div class="grid grid-cols-2 gap-4 text-sm">
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
                                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h5>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ section.points }} points</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ section.description }}</p>

                                            <div class="space-y-2">
                                                <div class="text-xs text-gray-700 dark:text-gray-300 font-medium">Requirements:</div>
                                                <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                    <li v-for="req in section.requirements.slice(0, 2)" :key="req" class="flex items-start gap-2">
                                                        <span class="text-green-600 dark:text-green-400 mt-0.5">•</span>
                                                        {{ req }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="generatedContent.sections.length > 2" class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
                                        +{{ generatedContent.sections.length - 2 }} more sections in full preview
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Full Preview Tab -->
                    <div v-if="activeTab === 'preview' && generatedContent" class="space-y-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                            <!-- Preview Header -->
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ generatedContent.title }}
                                    </h2>
                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
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
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="generatedContent.type === 'quiz' ? editQuiz() : editAssignment()"
                                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                        Edit
                                    </button>
                                    <button
                                        @click="regenerateContent"
                                        class="flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                    >
                                        <ArrowPathIcon class="w-4 h-4" />
                                        Regenerate
                                    </button>
                                    <button
                                        @click="exportGenerated"
                                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                    >
                                        <ArrowDownTrayIcon class="w-4 h-4" />
                                        Export PDF
                                    </button>
                                    <button
                                        @click="publishContent"
                                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                                    >
                                        <CheckIcon class="w-4 h-4" />
                                        Publish
                                    </button>
                                </div>
                            </div>

                            <!-- Quiz Preview Content -->
                            <div v-if="generatedContent.type === 'quiz'">
                                <!-- Quiz Instructions -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-8">
                                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-3">Instructions</h3>
                                    <p class="text-blue-800 dark:text-blue-300">{{ generatedContent.instructions }}</p>
                                </div>

                                <!-- All Questions -->
                                <div class="space-y-6">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Questions</h3>
                                    <div
                                        v-for="question in generatedContent.questions"
                                        :key="question.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-xl p-6"
                                    >
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <span class="w-8 h-8 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full flex items-center justify-center text-sm font-bold">
                                                    {{ question.id }}
                                                </span>
                                                <div>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400 uppercase">{{ question.type.replace('-', ' ') }}</span>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ question.points }} points</div>
                                                </div>
                                            </div>
                                            <button
                                                @click="deleteQuestion(question.id)"
                                                class="p-1 text-gray-400 hover:text-red-500 rounded"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <p class="text-lg text-gray-900 dark:text-white mb-4">{{ question.question }}</p>

                                        <!-- Multiple Choice Options -->
                                        <div v-if="question.type === 'multiple-choice'" class="space-y-3 mb-4">
                                            <div
                                                v-for="(option, index) in question.options"
                                                :key="index"
                                                :class="`flex items-center gap-3 p-3 rounded-lg border transition-colors ${
                                                    index === question.correctAnswer
                                                        ? 'border-green-300 bg-green-50 dark:bg-green-900/20'
                                                        : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900'
                                                }`"
                                            >
                                                <span :class="`w-6 h-6 rounded-full border-2 flex items-center justify-center text-sm font-bold ${
                                                    index === question.correctAnswer
                                                        ? 'border-green-500 bg-green-500 text-white'
                                                        : 'border-gray-300 dark:border-gray-600'
                                                }`">
                                                    {{ String.fromCharCode(65 + index) }}
                                                </span>
                                                <span :class="index === question.correctAnswer ? 'text-green-800 dark:text-green-200 font-medium' : 'text-gray-800 dark:text-gray-200'">
                                                    {{ option }}
                                                </span>
                                                <CheckIcon v-if="index === question.correctAnswer" class="w-5 h-5 text-green-500 ml-auto" />
                                            </div>
                                        </div>

                                        <!-- True/False Options -->
                                        <div v-if="question.type === 'true-false'" class="flex gap-4 mb-4">
                                            <div :class="`flex items-center gap-2 p-3 rounded-lg border ${
                                                question.correctAnswer === true
                                                    ? 'border-green-300 bg-green-50 dark:bg-green-900/20'
                                                    : 'border-gray-200 dark:border-gray-700'
                                            }`">
                                                <span class="font-medium">True</span>
                                                <CheckIcon v-if="question.correctAnswer === true" class="w-4 h-4 text-green-500" />
                                            </div>
                                            <div :class="`flex items-center gap-2 p-3 rounded-lg border ${
                                                question.correctAnswer === false
                                                    ? 'border-green-300 bg-green-50 dark:bg-green-900/20'
                                                    : 'border-gray-200 dark:border-gray-700'
                                            }`">
                                                <span class="font-medium">False</span>
                                                <CheckIcon v-if="question.correctAnswer === false" class="w-4 h-4 text-green-500" />
                                            </div>
                                        </div>

                                        <!-- Explanation -->
                                        <div v-if="question.explanation" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                            <h5 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Explanation</h5>
                                            <p class="text-blue-800 dark:text-blue-300 text-sm">{{ question.explanation }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Preview Content -->
                            <div v-if="generatedContent.type === 'assignment'">
                                <!-- Assignment Overview -->
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 mb-8">
                                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-200 mb-3">Assignment Overview</h3>
                                    <p class="text-green-800 dark:text-green-300 mb-4">{{ generatedContent.description }}</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg">
                                            <div class="font-semibold text-gray-900 dark:text-white">Due Date</div>
                                            <div class="text-gray-600 dark:text-gray-400">{{ generatedContent.dueDate }}</div>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg">
                                            <div class="font-semibold text-gray-900 dark:text-white">Total Points</div>
                                            <div class="text-gray-600 dark:text-gray-400">{{ generatedContent.totalPoints }}</div>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg">
                                            <div class="font-semibold text-gray-900 dark:text-white">Duration</div>
                                            <div class="text-gray-600 dark:text-gray-400">{{ generatedContent.duration }} days</div>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg">
                                            <div class="font-semibold text-gray-900 dark:text-white">Work Type</div>
                                            <div class="text-gray-600 dark:text-gray-400">{{ generatedContent.groupWork ? 'Group' : 'Individual' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assignment Sections -->
                                <div class="space-y-3">
                                    <div
                                        v-for="section in generatedContent.sections.slice(0, 3)"
                                        :key="section.title"
                                        class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h4>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ section.points }} points</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ section.description }}</p>

                                        <!-- Section Requirements -->
                                        <div v-if="section.requirements && section.requirements.length > 0" class="space-y-2">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Requirements:</p>
                                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                <li v-for="req in section.requirements" :key="req" class="flex items-start gap-2">
                                                    <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                                    {{ req }}
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Deliverables -->
                                        <div v-if="section.deliverables && section.deliverables.length > 0" class="mt-3 space-y-2">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Deliverables:</p>
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="deliverable in section.deliverables"
                                                    :key="deliverable"
                                                    class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs"
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
                                    class="w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                >
                                    {{ showAllSections ? 'Show Less' : `Show ${generatedContent.sections.length - 3} More Sections` }}
                                </button>

                                <!-- Additional Sections (when expanded) -->
                                <div v-if="showAllSections && generatedContent.sections.length > 3" class="space-y-3">
                                    <div
                                        v-for="section in generatedContent.sections.slice(3)"
                                        :key="section.title"
                                        class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h4>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ section.points }} points</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ section.description }}</p>

                                        <!-- Requirements -->
                                        <div v-if="section.requirements && section.requirements.length > 0" class="space-y-2">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Requirements:</p>
                                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                <li v-for="req in section.requirements" :key="req" class="flex items-start gap-2">
                                                    <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                                    {{ req }}
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Deliverables -->
                                        <div v-if="section.deliverables && section.deliverables.length > 0" class="mt-3 space-y-2">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Deliverables:</p>
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="deliverable in section.deliverables"
                                                    :key="deliverable"
                                                    class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs"
                                                >
                                                    {{ deliverable }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grading Rubric -->
                                <div v-if="generatedContent.rubric" class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <StarIcon class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        Grading Rubric
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                        <div
                                            v-for="criteria in generatedContent.rubric"
                                            :key="criteria.category"
                                            class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800"
                                        >
                                            <h5 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ criteria.category }}</h5>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ criteria.description }}</p>
                                            <div class="text-xs font-medium text-yellow-700 dark:text-yellow-400">{{ criteria.weight }}% of grade</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submission Guidelines -->
                                <div v-if="generatedContent.submissionGuidelines" class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <ArrowUpTrayIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                        Submission Guidelines
                                    </h4>
                                    <div class="space-y-2">
                                        <div v-for="guideline in generatedContent.submissionGuidelines" :key="guideline.type" class="flex items-start gap-2">
                                            <CheckCircleIcon class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <span class="font-medium text-gray-900 dark:text-white text-sm">{{ guideline.type }}:</span>
                                                <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">{{ guideline.detail }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recommended Resources -->
                                <div v-if="generatedContent.resources && generatedContent.resources.length > 0" class="mt-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <BookOpenIcon class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                        Recommended Resources
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div
                                            v-for="resource in generatedContent.resources"
                                            :key="resource.title"
                                            class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-purple-200 dark:border-purple-800"
                                        >
                                            <h5 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ resource.title }}</h5>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ resource.description }}</p>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">{{ resource.type }}</span>
                                                <button
                                                    v-if="resource.url"
                                                    @click="window.open(resource.url, '_blank')"
                                                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    View →
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="editAssignment"
                                            class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                            Edit Assignment
                                        </button>
                                        <button
                                            @click="regenerateContent"
                                            class="flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                        >
                                            <ArrowPathIcon class="w-4 h-4" />
                                            Regenerate
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="exportGenerated"
                                            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                        >
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                            Export PDF
                                        </button>
                                        <button
                                            @click="publishContent"
                                            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                                        >
                                            <CheckIcon class="w-4 h-4" />
                                            Publish Assignment
                                        </button>
                                    </div>
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

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Line clamp utilities for text truncation */
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

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Button hover effects */
button:hover {
    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
    transform: none;
}

/* Form input focus styles */
input:focus,
textarea:focus,
select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

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

/* Typing indicator dots */
.typing-indicator .dot:nth-child(1) {
    animation-delay: 0ms;
}

.typing-indicator .dot:nth-child(2) {
    animation-delay: 150ms;
}

.typing-indicator .dot:nth-child(3) {
    animation-delay: 300ms;
}

/* Tab active states */
.tab-active {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Form section backgrounds */
.form-section {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}

.dark .form-section {
    background: linear-gradient(135deg, #1e293b, #0f172a);
}

/* Preview section styling */
.preview-section {
    background: linear-gradient(135deg, #fefefe, #f9fafb);
    border: 1px solid #e5e7eb;
}

.dark .preview-section {
    background: linear-gradient(135deg, #1f2937, #111827);
    border: 1px solid #374151;
}

/* Question card hover effects */
.question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.dark .question-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

/* Assignment section cards */
.assignment-section-card {
    transition: all 0.3s ease;
}

.assignment-section-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Badge animations */
.badge-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .mobile-stack {
        grid-template-columns: 1fr;
    }

    .mobile-full-width {
        width: 100%;
    }

    .mobile-text-sm {
        font-size: 0.875rem;
    }
}

/* Focus visible for accessibility */
*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Custom checkbox and radio styles */
input[type="checkbox"]:checked,
input[type="radio"]:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

/* Message bubble animations */
.message-enter-active {
    transition: all 0.3s ease;
}

.message-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.message-enter-to {
    opacity: 1;
    transform: translateY(0);
}
</style>
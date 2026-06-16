<!-- resources/js/components/Chat/EnhancedChatBox.vue -->
<template>
  <div class="flex flex-col h-full ui-card p-0 overflow-hidden">
    <!-- Chat Header -->
    <div class="bg-primary px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-white/20 rounded-control flex items-center justify-center">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-white">UniGPT Assistant</h2>
            <p class="text-sm text-white/80">Your Academic Copilot</p>
          </div>
        </div>

        <!-- Chat Mode Selector -->
        <div class="flex items-center space-x-2 bg-white/10 rounded-control px-4 py-2">
          <button
            v-for="mode in chatModes"
            :key="mode.id"
            @click="currentMode = mode.id"
            :class="[
              'px-4 py-2 rounded-control font-medium text-sm transition-all duration-200',
              currentMode === mode.id
                ? 'bg-white text-primary shadow-card'
                : 'text-white/80 hover:bg-white/20'
            ]"
          >
            {{ mode.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Messages Area -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-bg scrollbar-thin">
      <div v-for="message in messages" :key="message.id" class="animate-fade-in">
        <!-- User Message -->
        <div v-if="message.role === 'user'" class="flex justify-end">
          <div class="max-w-[70%] bg-primary text-white rounded-2xl rounded-tr-none px-6 py-4 shadow-card">
            <p class="text-sm">{{ message.content }}</p>
            <span class="text-xs text-white/70 mt-2 block">{{ message.time }}</span>
          </div>
        </div>

        <!-- Assistant Message -->
        <div v-else class="flex justify-start">
          <div class="max-w-[75%]">
            <div class="bg-surface border border-line rounded-2xl rounded-tl-none px-6 py-4 shadow-card">
              <p class="text-content text-sm leading-relaxed">{{ message.content }}</p>

              <!-- Confidence Score -->
              <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4 text-success-fg" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-xs font-semibold text-success-fg">{{ message.confidence }}% Confident</span>
                  </div>
                  <span class="text-xs text-content-faint">{{ message.time }}</span>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-2">
                  <button @click="toggleSave(message.id)" class="p-2 hover:bg-neutral-bg rounded-control transition-colors">
                    <svg :class="['w-5 h-5', message.saved ? 'text-warning-fg fill-current' : 'text-content-faint']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                  </button>
                  <button @click="openCitations(message)" class="p-2 hover:bg-neutral-bg rounded-control transition-colors">
                    <svg class="w-5 h-5 text-content-faint hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Source Citations Preview -->
              <div v-if="message.sources" class="mt-3 pt-3 border-t border-line">
                <p class="text-xs font-semibold text-content-muted mb-2">📚 Sources:</p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="source in message.sources"
                    :key="source.id"
                    class="inline-flex items-center px-3 py-1 rounded-pill text-xs bg-primary-soft text-primary cursor-pointer hover:shadow-md transition-shadow"
                    @click="openCitations(message)"
                  >
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" />
                    </svg>
                    {{ source.name }} - p{{ source.page }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Follow-up Suggestions -->
            <div v-if="message.suggestions" class="mt-3 space-y-2">
              <p class="text-xs font-semibold text-content-muted">💡 Follow-up questions:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="(suggestion, index) in message.suggestions"
                  :key="index"
                  @click="sendMessage(suggestion)"
                  class="px-4 py-2 bg-surface border border-line text-sm text-content rounded-control hover:border-primary hover:bg-primary-soft hover:text-primary hover:shadow-card transform hover:-translate-y-0.5 transition-all duration-200"
                >
                  {{ suggestion }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Typing Indicator -->
      <div v-if="isTyping" class="flex justify-start animate-fade-in">
        <div class="bg-surface border border-line rounded-2xl rounded-tl-none px-6 py-4 shadow-card">
          <div class="flex space-x-2">
            <div class="w-3 h-3 bg-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-3 h-3 bg-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-3 h-3 bg-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div class="border-t border-line bg-surface px-6 py-4">
      <div class="flex items-end space-x-4">
        <!-- Voice Input Button -->
        <button
          @click="toggleVoiceInput"
          :class="[
            'p-3 rounded-control transition-all duration-200',
            isRecording
              ? 'bg-danger-fg text-white animate-pulse'
              : 'bg-primary-soft text-primary hover:shadow-card'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
        </button>

        <!-- Text Input -->
        <div class="flex-1 relative">
          <textarea
            v-model="inputMessage"
            @keydown.enter.exact.prevent="handleSend"
            rows="1"
            placeholder="Ask anything about your academics..."
            class="ui-input pr-12 resize-none"
            style="max-height: 120px;"
          ></textarea>

          <!-- Character Count / Language Selector -->
          <div class="absolute right-3 bottom-3 flex items-center space-x-2">
            <select v-model="selectedLanguage" class="ui-input w-auto text-xs px-2 py-1">
              <option value="en">🇬🇧 English</option>
              <option value="hi">🇮🇳 हिंदी</option>
              <option value="mixed">🌐 Hinglish</option>
            </select>
          </div>
        </div>

        <!-- Send Button -->
        <button
          @click="handleSend"
          :disabled="!inputMessage.trim()"
          :class="[
            'p-3 rounded-control transition-all duration-200',
            inputMessage.trim()
              ? 'ui-btn-primary'
              : 'bg-neutral-bg text-content-faint cursor-not-allowed'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </div>

      <!-- Quick Actions -->
      <div class="mt-3 flex items-center justify-between">
        <div class="flex items-center space-x-2 text-xs text-content-muted">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Press Enter to send, Shift+Enter for new line</span>
        </div>

        <button @click="clearChat" class="text-xs text-danger-fg hover:opacity-80 font-medium">
          Clear Chat
        </button>
      </div>
    </div>
  </div>

  <!-- Citations Modal -->
  <CitationsModal
    v-if="showCitationsModal"
    :message="selectedMessage"
    @close="showCitationsModal = false"
  />
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import CitationsModal from './CitationsModal.vue';

const chatModes = [
  { id: 'simple', label: '📝 Simple' },
  { id: 'detailed', label: '📚 Detailed' },
  { id: 'exam', label: '🎯 Exam-Focused' }
];

const currentMode = ref('simple');
const inputMessage = ref('');
const isTyping = ref(false);
const isRecording = ref(false);
const selectedLanguage = ref('en');
const messagesContainer = ref(null);
const showCitationsModal = ref(false);
const selectedMessage = ref(null);

// Mock messages with rich data
const messages = ref([
  {
    id: 1,
    role: 'assistant',
    content: 'Hello! I\'m your UniGPT Assistant. I can help you with questions about your courses, syllabus, exam schedules, and university policies. What would you like to know?',
    time: '10:30 AM',
    confidence: 100,
    saved: false,
    suggestions: [
      'What is my attendance percentage?',
      'Show me upcoming deadlines',
      'Explain Unit-3 syllabus'
    ]
  }
]);

const toggleSave = (messageId) => {
  const message = messages.value.find(m => m.id === messageId);
  if (message) {
    message.saved = !message.saved;
  }
};

const toggleVoiceInput = () => {
  isRecording.value = !isRecording.value;
  if (isRecording.value) {
    // Mock voice recording
    setTimeout(() => {
      isRecording.value = false;
      inputMessage.value = "What are the attendance requirements for my department?";
    }, 3000);
  }
};

const openCitations = (message) => {
  selectedMessage.value = message;
  showCitationsModal.value = true;
};

const handleSend = async () => {
  if (!inputMessage.value.trim()) return;

  const userMessage = {
    id: Date.now(),
    role: 'user',
    content: inputMessage.value,
    time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  };

  messages.value.push(userMessage);
  inputMessage.value = '';
  isTyping.value = true;

  await nextTick();
  scrollToBottom();

  // Mock AI response
  setTimeout(() => {
    const aiResponse = {
      id: Date.now() + 1,
      role: 'assistant',
      content: generateMockResponse(userMessage.content),
      time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
      confidence: Math.floor(Math.random() * 15 + 85),
      saved: false,
      sources: [
        { id: 1, name: 'Academic Handbook 2024', page: 23 },
        { id: 2, name: 'CSE Department Guidelines', page: 7 }
      ],
      suggestions: [
        'What happens if I fall below this percentage?',
        'How to calculate my current attendance?',
        'Show me attendance policy details'
      ]
    };

    messages.value.push(aiResponse);
    isTyping.value = false;
    nextTick(() => scrollToBottom());
  }, 1500);
};

const generateMockResponse = (question) => {
  const responses = {
    attendance: 'According to the Academic Handbook 2024, students in the Computer Science Engineering department must maintain a minimum attendance of 75% in all courses. This is calculated separately for theory and practical classes. Falling below this threshold may result in detention from semester examinations.',
    deadline: 'Here are your upcoming deadlines:\n\n1. Mid-term Exam Registration - Jan 15, 2024\n2. Project Report Submission - Jan 20, 2024\n3. Internship Application - Jan 25, 2024\n4. Fee Payment Last Date - Jan 31, 2024',
    syllabus: 'Unit-3 covers Advanced Data Structures including:\n\n• AVL Trees and Red-Black Trees\n• B-Trees and B+ Trees\n• Graph Algorithms (Dijkstra, Bellman-Ford)\n• Dynamic Programming concepts\n\nThis unit carries 20 marks in the final examination.',
    default: 'Based on the official university documents, I can provide you with accurate information. However, I need more specific details to give you the best answer. Could you please rephrase your question or provide more context?'
  };

  const lowerQuestion = question.toLowerCase();
  if (lowerQuestion.includes('attendance')) return responses.attendance;
  if (lowerQuestion.includes('deadline') || lowerQuestion.includes('upcoming')) return responses.deadline;
  if (lowerQuestion.includes('syllabus') || lowerQuestion.includes('unit')) return responses.syllabus;
  return responses.default;
};

const sendMessage = (text) => {
  inputMessage.value = text;
  handleSend();
};

const clearChat = () => {
  if (confirm('Are you sure you want to clear the chat history?')) {
    messages.value = messages.value.slice(0, 1); // Keep welcome message
  }
};

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

onMounted(() => {
  scrollToBottom();
});
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

/* Custom scrollbar */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

textarea {
  field-sizing: content;
}
</style>
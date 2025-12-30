<template>
    <div class="chat-box flex flex-col h-full">
        <!-- Messages Area -->
        <div class="messages-container h-96 overflow-y-auto mb-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
            <div v-for="(message, index) in messages" :key="index" class="message mb-4">
                <div 
                    :class="[
                        'flex',
                        message.type === 'user' ? 'justify-end' : 'justify-start'
                    ]"
                >
                    <div 
                        :class="[
                            'max-w-[80%] rounded-2xl px-4 py-3',
                            message.type === 'user' 
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white' 
                                : 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white border border-gray-200 dark:border-slate-700'
                        ]"
                    >
                        <p class="text-sm">{{ message.content }}</p>
                        <span class="text-xs opacity-70 mt-1 block">
                            {{ formatTime(message.timestamp) }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="isTyping" class="flex justify-start mb-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl px-4 py-3 border border-gray-200 dark:border-slate-700">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-100"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-200"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="flex gap-2">
            <input 
                v-model="currentMessage"
                @keyup.enter="sendMessage"
                type="text" 
                placeholder="Type your message..."
                class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
            />
            <button 
                @click="sendMessage"
                :disabled="!currentMessage.trim()"
                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const messages = ref([
    {
        type: 'assistant',
        content: 'Hello! I\'m your UniGPT assistant. How can I help you today?',
        timestamp: new Date()
    }
]);

const currentMessage = ref('');
const isTyping = ref(false);

const sendMessage = () => {
    if (!currentMessage.value.trim()) return;

    // Add user message
    messages.value.push({
        type: 'user',
        content: currentMessage.value,
        timestamp: new Date()
    });

    const userMsg = currentMessage.value;
    currentMessage.value = '';

    // Simulate AI response
    isTyping.value = true;
    setTimeout(() => {
        isTyping.value = false;
        messages.value.push({
            type: 'assistant',
            content: `You said: "${userMsg}". This is a demo response. Connect to your AI backend to get real responses!`,
            timestamp: new Date()
        });
    }, 1500);
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
};
</script>

<style scoped>
.delay-100 {
    animation-delay: 0.1s;
}

.delay-200 {
    animation-delay: 0.2s;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dark .messages-container::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>

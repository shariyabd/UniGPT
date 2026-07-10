<template>
    <div class="chat-box flex flex-col h-full">
        <!-- Messages Area -->
        <div class="messages-container h-96 overflow-y-auto mb-4 p-4 bg-bg rounded-card">
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
                                ? 'bg-primary text-white'
                                : 'bg-surface text-content border border-line'
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
                <div class="bg-surface rounded-2xl px-4 py-3 border border-line">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-content-faint rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-content-faint rounded-full animate-bounce delay-100"></div>
                        <div class="w-2 h-2 bg-content-faint rounded-full animate-bounce delay-200"></div>
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
                class="ui-input flex-1"
            />
            <button
                @click="sendMessage"
                :disabled="!currentMessage.trim()"
                class="ui-btn-primary px-6 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
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
        content: 'Hello! I\'m your UniNexus assistant. How can I help you today?',
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
</style>

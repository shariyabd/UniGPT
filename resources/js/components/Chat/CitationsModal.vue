<!-- resources/js/components/Chat/CitationsModal.vue -->
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="true" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
          @click="$emit('close')"
        ></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl transform transition-all">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
              <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                  <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Source Citations & Evidence
                </h3>
                <button
                  @click="$emit('close')"
                  class="text-white/80 hover:text-white transition-colors"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Content -->
            <div class="p-6 max-h-[70vh] overflow-y-auto">
              <!-- AI Response -->
              <div class="mb-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300 mb-2">AI Response:</h4>
                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                  {{ message?.content }}
                </p>
              </div>

              <!-- Sources -->
              <div class="space-y-4">
                <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                  </svg>
                  Referenced Documents ({{ mockSources.length }})
                </h4>

                <div
                  v-for="source in mockSources"
                  :key="source.id"
                  class="bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-indigo-400 hover:shadow-lg transition-all duration-200"
                >
                  <!-- Document Info -->
                  <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start space-x-4">
                      <div class="w-12 h-16 bg-gradient-to-br from-red-500 to-pink-500 rounded-lg flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div>
                        <h5 class="font-bold text-gray-900 dark:text-gray-100">{{ source.document }}</h5>
                        <div class="flex items-center space-x-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                          <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Page {{ source.page }}
                          </span>
                          <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ source.date }}
                          </span>
                          <span :class="[
                            'px-2 py-1 rounded-full text-xs font-semibold',
                            source.relevance >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                            source.relevance >= 70 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                          ]">
                            {{ source.relevance }}% Relevant
                          </span>
                        </div>
                      </div>
                    </div>

                    <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                      View Full Document
                    </button>
                  </div>

                  <!-- Excerpt -->
                  <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-lg p-4 border-l-4 border-indigo-500">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">EXCERPT:</p>
                    <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                      {{ source.excerpt }}
                    </p>
                  </div>

                  <!-- Highlight Match -->
                  <div class="mt-3 flex items-center text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-1 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    <span>Matched keywords: <strong class="text-indigo-600 dark:text-indigo-400">{{ source.keywords.join(', ') }}</strong></span>
                  </div>
                </div>
              </div>

              <!-- Verification Notice -->
              <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-start">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <div class="ml-3">
                    <p class="text-sm font-semibold text-green-900 dark:text-green-300">✅ Verified Information</p>
                    <p class="text-xs text-green-700 dark:text-green-400 mt-1">
                      All information is sourced from official university documents. No external or unverified sources used.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-2xl flex items-center justify-between">
              <div class="text-xs text-gray-600 dark:text-gray-400">
                <span class="font-semibold">Confidence Score:</span>
                <span class="ml-2 text-green-600 dark:text-green-400 font-bold">{{ message?.confidence }}%</span>
              </div>
              <button
                @click="$emit('close')"
                class="px-6 py-2 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  message: Object
});

const emit = defineEmits(['close']);

const mockSources = [
  {
    id: 1,
    document: 'Academic Handbook 2024',
    page: 23,
    date: 'Jan 2024',
    relevance: 95,
    excerpt: 'Students enrolled in undergraduate programs must maintain a minimum attendance of 75% in all registered courses, calculated separately for theory and laboratory sessions. Failure to meet this requirement may result in detention from the end-semester examination.',
    keywords: ['attendance', '75%', 'undergraduate', 'detention']
  },
  {
    id: 2,
    document: 'CSE Department Guidelines',
    page: 7,
    date: 'Aug 2023',
    relevance: 88,
    excerpt: 'The Computer Science and Engineering department follows the university-wide attendance policy with additional requirements for practical sessions. Students must complete all assigned laboratory exercises irrespective of attendance percentage.',
    keywords: ['CSE', 'attendance', 'laboratory', 'practical']
  },
  {
    id: 3,
    document: 'Examination Rules & Regulations',
    page: 15,
    date: 'Dec 2023',
    relevance: 76,
    excerpt: 'Candidates with less than 75% attendance in any course shall not be permitted to appear in the semester-end examination for that course, unless granted special permission by the Dean of Academics based on valid medical or compassionate grounds.',
    keywords: ['75% attendance', 'examination', 'Dean', 'permission']
  }
];
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}
</style>
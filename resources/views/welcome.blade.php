<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-950">
        <div class="container mx-auto px-4 py-16">
            <!-- Header Section -->
            <div class="text-center mb-16">
                <div class="mb-8 flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-500 blur-2xl opacity-30 rounded-full"></div>
                        <svg class="relative w-32 h-32 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                </div>

                <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 mb-4">
                    UniNexus
                </h1>
                <p class="text-2xl text-gray-700 dark:text-gray-300 mb-2">
                    University Academic Copilot
                </p>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    Powered by Advanced AI & RAG Technology
                </p>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <!-- Vue 3 Card -->
                <div class="group relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-400 to-emerald-500 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="flex items-center justify-center w-16 h-16 mb-6 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl mx-auto">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-3">Vue 3</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center">Progressive JavaScript Framework</p>
                        <div class="mt-6 flex justify-center">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Vite Card -->
                <div class="group relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="flex items-center justify-center w-16 h-16 mb-6 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-xl mx-auto">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23 3.5L12 22 1 3.5 12 1z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-3">Vite</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center">Next Generation Frontend Tooling</p>
                        <div class="mt-6 flex justify-center">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tailwind Card -->
                <div class="group relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-pink-500 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="flex items-center justify-center w-16 h-16 mb-6 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl mx-auto">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 6c-2.67 0-4.33 1.33-5 4 1-1.33 2.17-1.83 3.5-1.5.76.19 1.31.74 1.91 1.35.98 1 2.09 2.15 4.59 2.15 2.67 0 4.33-1.33 5-4-1 1.33-2.17 1.83-3.5 1.5-.76-.19-1.3-.74-1.91-1.35C15.61 7.15 14.5 6 12 6zm-5 6c-2.67 0-4.33 1.33-5 4 1-1.33 2.17-1.83 3.5-1.5.76.19 1.3.74 1.91 1.35C8.39 16.85 9.5 18 12 18c2.67 0 4.33-1.33 5-4-1 1.33-2.17 1.83-3.5 1.5-.76-.19-1.3-.74-1.91-1.35C10.61 13.15 9.5 12 7 12z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-3">Tailwind CSS</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center">Utility-First CSS Framework</p>
                        <div class="mt-6 flex justify-center">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                <span class="w-2 h-2 bg-purple-500 rounded-full mr-2 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                    Architecture & Features
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Feature Cards -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Domain-Driven Design</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Clean architecture with separated concerns</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">RAG System</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Advanced retrieval-augmented generation</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multi-AI Support</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">OpenAI, Gemini, and Local LLM integration</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Role-Based Access</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Admin, Faculty, and Student permissions</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Document Processing</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Smart chunking and vector embeddings</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Analytics Dashboard</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Comprehensive usage metrics and insights</p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-16 text-center">
                <div class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-full shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 cursor-pointer">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Ready to Build
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

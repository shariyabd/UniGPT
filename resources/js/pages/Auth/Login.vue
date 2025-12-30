<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    UserIcon,
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    AcademicCapIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

// Component state
const isLogin = ref(true);
const showPassword = ref(false);
const selectedRole = ref('student');
const isLoading = ref(false);
const emailDomain = ref('');

// Form data
const loginForm = useForm({
    email: '',
    password: '',
    role: 'student',
    remember: false
});

const signupForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student',
    department: '',
    student_id: '',
    employee_id: '',
    terms: false
});

// Role configuration
const roles = [
    {
        value: 'student',
        label: 'Student',
        description: 'Access course materials, chat with AI tutor, track learning progress',
        icon: AcademicCapIcon,
        gradient: 'from-blue-500 to-cyan-600',
        bgGradient: 'from-blue-50 to-cyan-50 dark:from-blue-950/30 dark:to-cyan-950/30',
        features: ['AI Study Assistant', 'Course Materials', 'Learning Roadmap', 'Progress Tracking']
    },
    {
        value: 'faculty',
        label: 'Faculty',
        description: 'Manage courses, upload materials, use AI teaching assistant, view analytics',
        icon: UserGroupIcon,
        gradient: 'from-green-500 to-emerald-600',
        bgGradient: 'from-green-50 to-emerald-50 dark:from-green-950/30 dark:to-emerald-950/30',
        features: ['AI Teaching Assistant', 'Course Management', 'Student Analytics', 'Grading Tools']
    },
    {
        value: 'admin',
        label: 'Administrator',
        description: 'System administration, user management, analytics dashboard, AI configuration',
        icon: ShieldCheckIcon,
        gradient: 'from-purple-500 to-pink-600',
        bgGradient: 'from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30',
        features: ['User Management', 'System Analytics', 'AI Configuration', 'Content Moderation']
    }
];

// University departments
const departments = [
    'Computer Science Engineering',
    'Electrical Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Electronics & Communication',
    'Information Technology',
    'Chemical Engineering',
    'Biotechnology',
    'Mathematics',
    'Physics',
    'Chemistry',
    'Administration',
    'Library Sciences',
    'Academic Affairs'
];

// Computed properties
const currentRole = computed(() => {
    return roles.find(role => role.value === selectedRole.value) || roles[0];
});

const currentForm = computed(() => {
    return isLogin.value ? loginForm : signupForm;
});

const emailValidation = computed(() => {
    const email = isLogin.value ? loginForm.email : signupForm.email;
    if (!email) return { valid: null, message: '' };

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValidEmail = emailRegex.test(email);

    if (!isValidEmail) {
        return { valid: false, message: 'Please enter a valid email address' };
    }

    // Check university domain (relaxed for demo)
    const domain = email.split('@')[1]?.toLowerCase();
    if (domain && !domain.includes('edu') && !domain.includes('university') && !domain.includes('ac.') && !domain.includes('gmail')) {
        return { valid: false, message: 'Please use your university email address' };
    }

    return { valid: true, message: 'Valid email address' };
});

const passwordStrength = computed(() => {
    const password = signupForm.password;
    if (!password || isLogin.value) return { strength: 0, message: '', color: '' };

    let score = 0;
    let feedback = [];

    if (password.length >= 8) score++; else feedback.push('at least 8 characters');
    if (/[A-Z]/.test(password)) score++; else feedback.push('uppercase letter');
    if (/[a-z]/.test(password)) score++; else feedback.push('lowercase letter');
    if (/\d/.test(password)) score++; else feedback.push('number');
    if (/[^A-Za-z0-9]/.test(password)) score++; else feedback.push('special character');

    const strength = (score / 5) * 100;
    let message = '';
    let color = '';

    if (strength <= 40) {
        message = feedback.length ? `Missing: ${feedback.join(', ')}` : 'Weak password';
        color = 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
    } else if (strength <= 70) {
        message = 'Good password strength';
        color = 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
    } else {
        message = 'Strong password';
        color = 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    }

    return { strength, message, color };
});

// Auto-detect role from email domain
watch(() => isLogin.value ? loginForm.email : signupForm.email, (email) => {
    if (email) {
        const domain = email.split('@')[1]?.toLowerCase();
        emailDomain.value = domain || '';

        // Auto-detect role based on email patterns
        if (email.includes('student.') || email.includes('.student@')) {
            selectedRole.value = 'student';
            if (isLogin.value) loginForm.role = 'student';
            else signupForm.role = 'student';
        } else if (email.includes('admin') || email.includes('it.') || domain === 'admin.university.edu') {
            selectedRole.value = 'admin';
            if (isLogin.value) loginForm.role = 'admin';
            else signupForm.role = 'admin';
        } else if (email.includes('prof.') || email.includes('faculty.') || email.includes('dr.')) {
            selectedRole.value = 'faculty';
            if (isLogin.value) loginForm.role = 'faculty';
            else signupForm.role = 'faculty';
        }
    }
});

// Actions
const toggleMode = () => {
    isLogin.value = !isLogin.value;
    // Reset forms
    loginForm.reset();
    signupForm.reset();
    selectedRole.value = 'student';
};

const selectRole = (role) => {
    selectedRole.value = role;
    if (isLogin.value) {
        loginForm.role = role;
    } else {
        signupForm.role = role;
    }
};

const handleSubmit = () => {
    if (isLoading.value) return;

    isLoading.value = true;

    // Simulate authentication
    setTimeout(() => {
        if (isLogin.value) {
            // Handle login
            console.log('Login attempt:', loginForm.data());

            // Redirect based on role
           switch (loginForm.role) {
                case 'admin':
                    router.visit('/admin/dashboard');
                    break;
                case 'faculty':
                    router.visit('/faculty/dashboard');
                    break;
                case 'student':
                default:
                    router.visit('/dashboard');
            }
        } else {
            // Handle signup
            console.log('Signup attempt:', signupForm.data());

            // Show success message and redirect to login
            alert('Account created successfully! Please log in.');
            isLogin.value = true;
        }

        isLoading.value = false;
    }, 2000);
};

const handleDemoLogin = (role) => {
    loginForm.email = role === 'admin' ? 'admin@university.edu' :
                     role === 'faculty' ? 'prof.smith@university.edu' :
                     'student@university.edu';
    loginForm.password = 'demo123';
    loginForm.role = role;
    selectedRole.value = role;

    // Use Inertia router for demo login too
    isLoading.value = true;
    setTimeout(() => {
        switch (role) {
            case 'admin':
                router.visit('/admin/dashboard');
                break;
            case 'faculty':
                router.visit('/faculty/dashboard');
                break;
            case 'student':
            default:
                router.visit('/dashboard');
        }
        isLoading.value = false;
    }, 1000);
};
</script>

<template>
    <div>
        <Head :title="isLogin ? 'Login' : 'Sign Up'" />

        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-blue-950 dark:to-indigo-950 flex">
            <!-- Left Panel - Branding & Features -->
            <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center lg:px-12 xl:px-16">
                <div class="mx-auto max-w-lg">
                    <!-- Logo & Title -->
                    <div class="mb-12">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-2xl">
                                <span class="text-2xl font-bold text-white">🎓</span>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">UniGPT</h1>
                                <p class="text-gray-600 dark:text-gray-400">AI Academic Assistant</p>
                            </div>
                        </div>
                        <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                            Your intelligent companion for academic excellence. Get instant answers,
                            personalized learning paths, and 24/7 study support.
                        </p>
                    </div>

                    <!-- Current Role Features -->
                    <div :class="`rounded-2xl p-6 ${currentRole.bgGradient} border border-white/20 shadow-lg backdrop-blur-sm`">
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${currentRole.gradient} flex items-center justify-center shadow-lg`">
                                <component :is="currentRole.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ currentRole.label }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ currentRole.description }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="feature in currentRole.features"
                                :key="feature"
                                class="flex items-center gap-2"
                            >
                                <CheckCircleIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ feature }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Login Buttons -->
                    <div class="mt-8">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Try demo accounts:</p>
                        <div class="flex gap-2">
                            <button
                                @click="handleDemoLogin('student')"
                                class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-400 text-xs rounded-lg transition-colors"
                            >
                                Student Demo
                            </button>
                            <button
                                @click="handleDemoLogin('faculty')"
                                class="px-3 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-800 dark:text-green-400 text-xs rounded-lg transition-colors"
                            >
                                Faculty Demo
                            </button>
                            <button
                                @click="handleDemoLogin('admin')"
                                class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 text-xs rounded-lg transition-colors"
                            >
                                Admin Demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Login/Signup Form -->
            <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-8">
                <div class="mx-auto w-full max-w-md">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden text-center mb-8">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-4">
                            <span class="text-2xl font-bold text-white">🎓</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">UniGPT</h1>
                        <p class="text-gray-600 dark:text-gray-400">AI Academic Assistant</p>
                    </div>

                    <!-- Form Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ isLogin ? 'Welcome Back' : 'Create Account' }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ isLogin ? 'Sign in to your account' : 'Join the academic community' }}
                        </p>
                    </div>

                    <!-- Role Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Select Your Role
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="role in roles"
                                :key="role.value"
                                @click="selectRole(role.value)"
                                :class="`relative p-3 rounded-xl border-2 transition-all ${
                                    selectedRole === role.value
                                        ? `border-transparent bg-gradient-to-br ${role.gradient} text-white shadow-lg`
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'
                                }`"
                            >
                                <component
                                    :is="role.icon"
                                    class="w-6 h-6 mx-auto mb-2"
                                    :class="selectedRole === role.value ? 'text-white' : 'text-gray-600 dark:text-gray-400'"
                                />
                                <span :class="`text-xs font-medium ${
                                    selectedRole === role.value ? 'text-white' : 'text-gray-900 dark:text-white'
                                }`">
                                    {{ role.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Login/Signup Form -->
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Name (Signup only) -->
                        <div v-if="!isLogin">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Full Name *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <UserIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-model="signupForm.name"
                                    type="text"
                                    required
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Enter your full name"
                                />
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email Address *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <EnvelopeIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <!-- Fixed v-model bindings -->
                                <input
                                    v-if="isLogin"
                                    v-model="loginForm.email"
                                    type="email"
                                    required
                                    :class="`block w-full pl-10 pr-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white ${
                                        emailValidation.valid === false ? 'border-red-300 focus:ring-red-500' :
                                        emailValidation.valid === true ? 'border-green-300 focus:ring-green-500' :
                                        'border-gray-300 dark:border-gray-600 focus:ring-blue-500'
                                    }`"
                                    placeholder="your.email@university.edu"
                                />
                                <input
                                    v-else
                                    v-model="signupForm.email"
                                    type="email"
                                    required
                                    :class="`block w-full pl-10 pr-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white ${
                                        emailValidation.valid === false ? 'border-red-300 focus:ring-red-500' :
                                        emailValidation.valid === true ? 'border-green-300 focus:ring-green-500' :
                                        'border-gray-300 dark:border-gray-600 focus:ring-blue-500'
                                    }`"
                                    placeholder="your.email@university.edu"
                                />
                                <div v-if="emailValidation.valid !== null" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <CheckCircleIcon v-if="emailValidation.valid" class="h-5 w-5 text-green-500" />
                                    <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-500" />
                                </div>
                            </div>
                            <p v-if="emailValidation.message" :class="`mt-1 text-sm ${emailValidation.valid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`">
                                {{ emailValidation.message }}
                            </p>
                        </div>

                        <!-- Department (Signup only) -->
                        <div v-if="!isLogin">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Department *
                            </label>
                            <select
                                v-model="signupForm.department"
                                required
                                class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Select your department</option>
                                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                            </select>
                        </div>

                        <!-- Student/Employee ID (Signup only) -->
                        <div v-if="!isLogin" class="grid grid-cols-1 gap-6">
                            <div v-if="selectedRole === 'student'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Student ID
                                </label>
                                <input
                                    v-model="signupForm.student_id"
                                    type="text"
                                    class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Enter your student ID"
                                />
                            </div>
                            <div v-else>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Employee ID
                                </label>
                                <input
                                    v-model="signupForm.employee_id"
                                    type="text"
                                    class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Enter your employee ID"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <!-- Fixed v-model bindings -->
                                <input
                                    v-if="isLogin"
                                    v-model="loginForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Enter your password"
                                />
                                <input
                                    v-else
                                    v-model="signupForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Create a strong password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <EyeIcon v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                                    <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                                </button>
                            </div>

                            <!-- Password Strength (Signup only) -->
                            <div v-if="!isLogin && signupForm.password" class="mt-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full transition-all duration-300"
                                            :class="passwordStrength.strength <= 40 ? 'bg-red-500' : passwordStrength.strength <= 70 ? 'bg-yellow-500' : 'bg-green-500'"
                                            :style="{ width: passwordStrength.strength + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-medium">{{ Math.round(passwordStrength.strength) }}%</span>
                                </div>
                                <p :class="`text-xs px-2 py-1 rounded ${passwordStrength.color}`">
                                    {{ passwordStrength.message }}
                                </p>
                            </div>
                        </div>

                        <!-- Confirm Password (Signup only) -->
                        <div v-if="!isLogin">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Confirm Password *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-model="signupForm.password_confirmation"
                                    type="password"
                                    required
                                    :class="`block w-full pl-10 pr-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white ${
                                        signupForm.password_confirmation && signupForm.password !== signupForm.password_confirmation
                                            ? 'border-red-300 focus:ring-red-500'
                                            : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'
                                    }`"
                                    placeholder="Confirm your password"
                                />
                            </div>
                            <p v-if="signupForm.password_confirmation && signupForm.password !== signupForm.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                Passwords do not match
                            </p>
                        </div>

                        <!-- Remember Me / Terms -->
                        <div class="flex items-center justify-between">
                            <label v-if="isLogin" class="flex items-center">
                                <input
                                    v-model="loginForm.remember"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                            <label v-else class="flex items-start">
                                <input
                                    v-model="signupForm.terms"
                                    type="checkbox"
                                    required
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mt-1"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                    I agree to the <Link href="/terms" class="text-blue-600 dark:text-blue-400 hover:underline">Terms of Service</Link>
                                    and <Link href="/privacy" class="text-blue-600 dark:text-blue-400 hover:underline">Privacy Policy</Link>
                                </span>
                            </label>

                            <Link v-if="isLogin" href="/forgot-password" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                Forgot password?
                            </Link>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="isLoading"
                            :class="`group relative w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all bg-gradient-to-br ${currentRole.gradient} ${
                                isLoading
                                    ? 'opacity-75 cursor-not-allowed'
                                    : 'hover:shadow-lg hover:-translate-y-0.5 focus:ring-blue-500'
                            }`"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <ArrowRightIcon v-if="!isLoading" class="h-5 w-5 group-hover:translate-x-1 transition-transform" />
                                <div v-else class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                            </span>
                            {{ isLoading ? 'Please wait...' : isLogin ? 'Sign In' : 'Create Account' }}
                        </button>
                    </form>

                    <!-- Toggle Login/Signup -->
                    <div class="mt-8 text-center">
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ isLogin ? "Don't have an account?" : "Already have an account?" }}
                            <button
                                @click="toggleMode"
                                class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                            >
                                {{ isLogin ? 'Sign up' : 'Sign in' }}
                            </button>
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400">
                        © 2024 UniGPT. Empowering education through AI.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom focus styles */
input:focus, select:focus, button:focus {
    outline: none;
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

/* Hover effects */
.group:hover .group-hover\:translate-x-1 {
    transform: translateX(0.25rem);
}
</style>
<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router,usePage } from '@inertiajs/vue3';
import { useToast } from "vue-toastification";
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

const toast = useToast();
const page = usePage();
const isLogin = ref(true);
const showPassword = ref(false);
const selectedRole = ref('admin');
const isLoading = ref(false);
const emailDomain = ref('');

const loginForm = useForm({
    email: '',
    password: '',
    role: 'admin',
    remember: false
});

const signupForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'admin',
    department_id: '',
    student_id: '',
    employee_id: '',
    terms: false
});

const props = defineProps({
    roles: Array,
    departments: Array
});

const roleUIMap = {
    student: {
        icon: AcademicCapIcon,
        gradient: 'from-blue-500 to-cyan-600',
        bgGradient: 'from-blue-50 to-cyan-50 dark:from-blue-950/30 dark:to-cyan-950/30',
    },
    faculty: {
        icon: UserGroupIcon,
        gradient: 'from-green-500 to-emerald-600',
        bgGradient: 'from-green-50 to-emerald-50 dark:from-green-950/30 dark:to-emerald-950/30',
    },
    admin: {
        icon: ShieldCheckIcon,
        gradient: 'from-purple-500 to-pink-600',
        bgGradient: 'from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30',
    }
};


const roles = computed(() =>
    props.roles.map(r => ({
        ...r,
        value: r.slug.toLowerCase(),
        label: r.name,
        ...roleUIMap[r.slug.toLowerCase()]
    }))
);



const departments = computed(() => props.departments || []);


const currentRole = computed(() => {
    return roles.value.find(r => r.value === selectedRole.value) || roles.value[0];
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


watch(() => isLogin.value ? loginForm.email : signupForm.email, (email) => {
    if (email) {
        const domain = email.split('@')[1]?.toLowerCase();
        emailDomain.value = domain || '';

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

const toggleMode = () => {
    isLogin.value = !isLogin.value;
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
    console.log(4364);
    const form = isLogin.value ? loginForm : signupForm;
    const routeName = isLogin.value ? 'login.store' : 'register';

    form.post(route(routeName), {
        onStart: () => isLoading.value = true,
        onFinish: () => isLoading.value = false,
           onSuccess: (page) => {
            const action = isLogin.value ? 'signed in' : 'registered';
            toast.success(` Successfully ${action}! Redirecting to your dashboard...`, {
                timeout: 3000,
            });
        },
          onError: (errors) => {
            console.error('Form validation errors:', errors);
            handleFormErrors(errors);
        }
    });
};



const handleDemoLogin = (role) => {
    loginForm.email = role === 'admin' ? 'admin@university.edu' :
                     role === 'faculty' ? 'prof.smith@university.edu' :
                     'student@university.edu';
    loginForm.password = 'demo123';
    loginForm.role = role;
    selectedRole.value = role;

    isLoading.value = true;
    setTimeout(() => {
        switch (role) {
            case 'admin':
                router.visit('/admin/dashboard');
                break;
            case 'faculty':
                console.log('Navigating to faculty dashboard');
                router.visit('/faculty/dashboard');
                break;
            case 'student':
            default:
                router.visit('/dashboard');
        }
        isLoading.value = false;
    }, 1000);
};

const handleFormErrors = (errors) => {
    const keys = Object.keys(errors);

    keys.forEach(field => {
        const messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];

        messages.forEach(message => {
            if (message.includes('Too Many Attempts')) {
                toast.error('Too many login attempts. Please wait before trying again.', { timeout: 8000 });
                return;
            }

            toast.error(`${formatField(field)}: ${message}`, { timeout: 6000 });
        });
    });

    if (keys.length > 1) {
        toast.error(`Please fix ${keys.length} validation errors and try again.`, { timeout: 5000 });
    }
};

const formatField = (field) => {
    return field
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        toast.success(flash.success, {
            timeout: 5000,
        });
    }

    if (flash?.error) {
        toast.error(flash.error, {
            timeout: 7000,
        });
    }

    if (flash?.warning) {
        toast.warning(flash.warning, {
            timeout: 6000,
        });
    }

    if (flash?.info) {
        toast.info(flash.info, {
            timeout: 5000,
        });
    }
}, { deep: true, immediate: true });
</script>




<template>
    <div>
        <Head :title="isLogin ? 'Login' : 'Sign Up'" />

        <!-- Animated Background -->
        <div class="animated-particles">
            <div class="particle particle-1"></div>
            <div class="particle particle-2"></div>
            <div class="particle particle-3"></div>
            <div class="particle particle-4"></div>
            <div class="particle particle-5"></div>
            <div class="particle particle-6"></div>
            <div class="particle particle-7"></div>
            <div class="particle particle-8"></div>
        </div>

        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-blue-950 dark:to-indigo-950 flex animated-bg">
            <!-- Left Panel - Branding & Features -->
            <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center lg:px-12 xl:px-16 fade-in-left">
                <div class="mx-auto max-w-lg">
                    <!-- Logo & Title -->
                    <div class="mb-12">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-2xl floating-icon">
                                <span class="text-2xl font-bold text-white">🎓</span>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white gradient-text">UniGPT</h1>
                                <p class="text-gray-600 dark:text-gray-400 glow-text">AI Academic Assistant</p>
                            </div>
                        </div>
                        <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed fade-in-up">
                            Your intelligent companion for academic excellence. Get instant answers,
                            personalized learning paths, and 24/7 study support.
                        </p>
                    </div>

                    <!-- Current Role Features -->
                    <div :class="`rounded-2xl p-6 ${currentRole.bgGradient} border border-white/20 shadow-lg backdrop-blur-sm floating-card`">
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${currentRole.gradient} flex items-center justify-center shadow-lg pulse-icon`">
                                <component :is="currentRole.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ currentRole.label }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ currentRole.description }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(feature, index) in currentRole.features"
                                :key="feature"
                                class="flex items-center gap-2 feature-slide"
                                :style="{ animationDelay: `${index * 0.1}s` }"
                            >
                                <CheckCircleIcon class="w-4 h-4 text-green-600 dark:text-green-400 bounce-icon" />
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
                                class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-400 text-xs rounded-lg transition-all hover-lift demo-btn"
                            >
                                Student Demo
                            </button>
                            <button
                                @click="handleDemoLogin('faculty')"
                                class="px-3 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-800 dark:text-green-400 text-xs rounded-lg transition-all hover-lift demo-btn"
                            >
                                Faculty Demo
                            </button>
                            <button
                                @click="handleDemoLogin('admin')"
                                class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 text-xs rounded-lg transition-all hover-lift demo-btn"
                            >
                                Admin Demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Login/Signup Form -->
            <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-8 fade-in-right">
                <div class="mx-auto w-full max-w-md form-container">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden text-center mb-8">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-4 floating-icon">
                            <span class="text-2xl font-bold text-white">🎓</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white gradient-text">UniGPT</h1>
                        <p class="text-gray-600 dark:text-gray-400">AI Academic Assistant</p>
                    </div>

                    <!-- Form Header -->
                    <div class="text-center mb-8 header-fade">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 title-bounce">
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
                                :class="`relative p-3 rounded-xl border-2 transition-all role-card hover-scale ${
                                    selectedRole === role.value
                                        ? `border-transparent bg-gradient-to-br ${role.gradient} text-white shadow-lg selected-glow`
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'
                                }`"
                            >
                                <component
                                    :is="role.icon"
                                    class="w-6 h-6 mx-auto mb-2 icon-bounce"
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
                    <form @submit.prevent="handleSubmit" class="space-y-6 form-animation">

                        <!-- Name (Signup only) -->
                        <div v-if="!isLogin" class="input-group">
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
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                    placeholder="Enter your full name"
                                />
                            </div>
                              <p v-if="signupForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                <ExclamationTriangleIcon class="h-4 w-4" />
                                {{ signupForm.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email Address *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <EnvelopeIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-if="isLogin"
                                    v-model="loginForm.email"
                                    type="email"
                                    required
                                    :class="`block w-full pl-10 pr-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow ${
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
                                    :class="`block w-full pl-10 pr-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow ${
                                        emailValidation.valid === false ? 'border-red-300 focus:ring-red-500' :
                                        emailValidation.valid === true ? 'border-green-300 focus:ring-green-500' :
                                        'border-gray-300 dark:border-gray-600 focus:ring-blue-500'
                                    }`"
                                    placeholder="your.email@university.edu"
                                />
                                <div v-if="emailValidation.valid !== null" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <CheckCircleIcon v-if="emailValidation.valid" class="h-5 w-5 text-green-500 success-pulse" />
                                    <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-500 error-shake" />
                                </div>
                            </div>
                            <p v-if="emailValidation.message" :class="`mt-1 text-sm ${emailValidation.valid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'} message-fade`">
                                {{ emailValidation.message }}
                            </p>
                              <p v-if="signupForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                <ExclamationTriangleIcon class="h-4 w-4" />
                                {{ signupForm.errors.name }}
                            </p>
                        </div>

                        <!-- Department (Signup only) -->
                        <div v-if="!isLogin" class="input-group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Department *
                            </label>
                            <select
                                v-model="signupForm.department_id"
                                required
                                class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                            >
                                <option value="">Select your department</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Student/Employee ID (Signup only) -->
                        <div v-if="!isLogin" class="input-group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ selectedRole === 'student' ? 'Student ID' : 'Employee ID' }} *
                            </label>
                            <input
                                v-if="selectedRole === 'student'"
                                v-model="signupForm.student_id"
                                type="text"
                                required
                                class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                placeholder="Enter your student ID"
                            />
                            <input
                                v-else
                                v-model="signupForm.employee_id"
                                type="text"
                                required
                                class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                placeholder="Enter your employee ID"
                            />
                        </div>

                        <!-- Password -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-if="isLogin"
                                    v-model="loginForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                    placeholder="Enter your password"
                                />
                                <input
                                    v-else
                                    v-model="signupForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                    placeholder="Create a secure password"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                                    >
                                        <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                                        <EyeSlashIcon v-else class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Password Strength Indicator (Signup only) -->
                            <div v-if="!isLogin && signupForm.password" class="mt-2">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Password strength</span>
                                    <span :class="`text-xs px-2 py-1 rounded ${passwordStrength.color}`">
                                        {{ passwordStrength.message }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="passwordStrength.strength <= 40 ? 'bg-red-500' : passwordStrength.strength <= 70 ? 'bg-yellow-500' : 'bg-green-500'"
                                        :style="{ width: passwordStrength.strength + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm Password (Signup only) -->
                        <div v-if="!isLogin" class="input-group">
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
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-glow"
                                    placeholder="Confirm your password"
                                />
                                <div v-if="signupForm.password_confirmation && signupForm.password" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <CheckCircleIcon v-if="signupForm.password === signupForm.password_confirmation" class="h-5 w-5 text-green-500 success-pulse" />
                                    <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-500 error-shake" />
                                </div>
                            </div>
                            <p v-if="signupForm.password_confirmation && signupForm.password !== signupForm.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400 message-fade">
                                Passwords do not match
                            </p>
                        </div>

                        <!-- Remember Me & Forgot Password (Login only) -->
                        <div v-if="isLogin" class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer hover-lift">
                                <input
                                    v-model="loginForm.remember"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition-all"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                            <Link
                                href="/password/reset"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 link-glow"
                            >
                                Forgot your password?
                            </Link>
                        </div>

                        <!-- Terms & Conditions (Signup only) -->
                        <div v-if="!isLogin" class="input-group">
                            <label class="flex items-start cursor-pointer">
                                <input
                                    v-model="signupForm.terms"
                                    type="checkbox"
                                    required
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1 transition-all"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                    I agree to the
                                    <Link href="/terms" class="text-blue-600 dark:text-blue-400 hover:underline">Terms of Service</Link>
                                    and
                                    <Link href="/privacy" class="text-blue-600 dark:text-blue-400 hover:underline">Privacy Policy</Link>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="isLoading"
                            :class="`group relative w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl text-white font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all bg-gradient-to-br ${currentRole.gradient} submit-enhanced ${
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
                    <div class="mt-8 text-center toggle-fade">
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ isLogin ? "Don't have an account?" : "Already have an account?" }}
                            <button
                                @click="toggleMode"
                                class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 link-glow"
                            >
                                {{ isLogin ? 'Sign up' : 'Sign in' }}
                            </button>
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400 footer-fade">
                        © 2024 UniGPT. Empowering education through AI.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Keep your existing styles */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

input:focus, select:focus, button:focus {
    outline: none;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.group:hover .group-hover\:translate-x-1 {
    transform: translateX(0.25rem);
}

/* NEW ANIMATED STYLES - ONLY ADDITIONS */

/* Animated Background */
.animated-bg {
    position: relative;
    overflow: hidden;
}

.animated-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(147, 51, 234, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 40% 70%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
    animation: backgroundFloat 25s ease-in-out infinite;
    pointer-events: none;
    z-index: 1;
}

@keyframes backgroundFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 1; }
    33% { transform: translateY(-15px) rotate(1deg); opacity: 0.8; }
    66% { transform: translateY(10px) rotate(-0.5deg); opacity: 0.9; }
}

/* Floating Particles */
.animated-particles {
    position: fixed;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
    z-index: 2;
}

.particle {
    position: absolute;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 50%;
    animation: floatUp 20s infinite linear;
}

.particle-1 { left: 10%; width: 4px; height: 4px; animation-delay: 0s; animation-duration: 15s; }
.particle-2 { left: 25%; width: 2px; height: 2px; animation-delay: 3s; animation-duration: 18s; }
.particle-3 { left: 40%; width: 3px; height: 3px; animation-delay: 6s; animation-duration: 16s; }
.particle-4 { left: 55%; width: 2px; height: 2px; animation-delay: 9s; animation-duration: 20s; }
.particle-5 { left: 70%; width: 4px; height: 4px; animation-delay: 12s; animation-duration: 17s; }
.particle-6 { left: 85%; width: 3px; height: 3px; animation-delay: 15s; animation-duration: 19s; }
.particle-7 { left: 15%; width: 2px; height: 2px; animation-delay: 18s; animation-duration: 14s; }
.particle-8 { left: 90%; width: 3px; height: 3px; animation-delay: 21s; animation-duration: 21s; }

@keyframes floatUp {
    0% { transform: translateY(100vh) translateX(0px) rotate(0deg); opacity: 0; }
    5% { opacity: 1; }
    95% { opacity: 1; }
    100% { transform: translateY(-100px) translateX(20px) rotate(180deg); opacity: 0; }
}

/* Panel Animations */
.fade-in-left {
    animation: fadeInLeft 0.8s ease-out;
}

.fade-in-right {
    animation: fadeInRight 0.8s ease-out;
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-50px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Text Effects */
.gradient-text {
    background: linear-gradient(-45deg, #3b82f6, #8b5cf6, #06b6d4, #10b981);
    background-size: 400% 400%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 4s ease infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.glow-text {
    text-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
}

.title-bounce {
    animation: titleBounce 2s ease-in-out infinite;
}

@keyframes titleBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2px); }
}

/* Icon Animations */
.floating-icon {
    animation: floatingIcon 3s ease-in-out infinite;
}

@keyframes floatingIcon {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

.pulse-icon {
    animation: pulseIcon 2s ease-in-out infinite;
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.icon-bounce {
    animation: iconBounce 1s ease-in-out infinite;
}

@keyframes iconBounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-3px); }
    60% { transform: translateY(-1px); }
}

.bounce-icon {
    animation: bounceIcon 2s ease-in-out infinite;
}

@keyframes bounceIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Card Animations */
.floating-card {
    animation: floatingCard 4s ease-in-out infinite;
}

@keyframes floatingCard {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.role-card {
    animation: slideUp 0.5s ease-out both;
    position: relative;
    overflow: hidden;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.selected-glow {
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
    animation: selectedPulse 1s ease-in-out;
}

@keyframes selectedPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Form Animations */
.form-container {
    animation: formSlideUp 0.8s ease-out;
    position: relative;
    z-index: 10;
}

@keyframes formSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.input-group {
    animation: inputSlide 0.6s ease-out both;
}

@keyframes inputSlide {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.input-glow:focus {
    box-shadow:
        0 0 0 3px rgba(59, 130, 246, 0.1),
        0 4px 20px rgba(59, 130, 246, 0.15);
    transform: translateY(-1px);
}

/* Button Enhancements */
.submit-enhanced {
    position: relative;
    overflow: hidden;
}

.submit-enhanced::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.submit-enhanced:active::after {
    width: 300px;
    height: 300px;
}

.hover-lift:hover {
    transform: translateY(-2px) scale(1.02);
}

.hover-scale:hover {
    transform: scale(1.05);
}

.demo-btn {
    animation: demoSlide 0.5s ease-out both;
}

@keyframes demoSlide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Feature List Animation */
.feature-slide {
    animation: featureSlide 0.5s ease-out both;
}

@keyframes featureSlide {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Status Icons */
.success-pulse {
    animation: successPulse 1s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.error-shake {
    animation: errorShake 0.5s ease-in-out;
}

@keyframes errorShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

/* Text Animations */
.header-fade {
    animation: headerFade 0.8s ease-out 0.2s both;
}

.toggle-fade {
    animation: fadeIn 0.6s ease-out 0.8s both;
}

.footer-fade {
    animation: fadeIn 0.6s ease-out 1s both;
}

.message-fade {
    animation: messageFade 0.3s ease-out;
}

@keyframes headerFade {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes messageFade {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out 0.4s both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Link Effects */
.link-glow:hover {
    text-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
    transition: text-shadow 0.3s ease;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .floating-card,
    .floating-icon {
        animation: none;
    }

    .fade-in-left,
    .fade-in-right {
        animation: fadeInUp 0.6s ease-out;
    }

    .particle {
        display: none;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>


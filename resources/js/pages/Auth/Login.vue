<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
    ExclamationTriangleIcon,
    SparklesIcon,
    ChatBubbleLeftRightIcon,
    ChartBarIcon,
    BoltIcon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';
import { useTheme } from '@/composables/useTheme';

const toast = useToast();
const { isDark, initTheme, toggleTheme } = useTheme();
onMounted(() => initTheme());
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
    },
    faculty: {
        icon: UserGroupIcon,
    },
    admin: {
        icon: ShieldCheckIcon,
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
        color = 'text-danger-fg bg-danger-bg';
    } else if (strength <= 70) {
        message = 'Good password strength';
        color = 'text-warning-fg bg-warning-bg';
    } else {
        message = 'Strong password';
        color = 'text-success-fg bg-success-bg';
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
        if(role == "admin"){
            loginForm.email = "admin@university.edu";
            loginForm.password = "demo123";
        }else if(role == "faculty"){
            loginForm.email = "prof.smith@university.edu";
            loginForm.password = "demo123";
        }else if(role == "student"){
            loginForm.email = "student@university.edu";
            loginForm.password = "demo123";
        }
    } else {
        signupForm.role = role;
    }
};


const handleSubmit = () => {
    if (isLoading.value) return;

    const form = isLogin.value ? loginForm : signupForm;
    const routeName = isLogin.value ? 'login.store' : 'register';

    // On success the server redirects to the dashboard; no toast is fired here so
    // a "redirecting…" message can't linger on the already-loaded destination page.
    form.post(route(routeName), {
        onStart: () => isLoading.value = true,
        onFinish: () => isLoading.value = false,
        onError: (errors) => handleFormErrors(errors),
    });
};



const handleDemoLogin = (role) => {
    if (isLoading.value) return;

    // Keep the form UI in sync for visual feedback.
    selectedRole.value = role;
    loginForm.role = role;
    loginForm.email = role === 'admin' ? 'admin@university.edu' :
                     role === 'faculty' ? 'prof.smith@university.edu' :
                     'student@university.edu';
    loginForm.password = 'demo123';

    // Authenticate via the dedicated demo-login endpoint, which logs the demo
    // user in server-side and redirects to the role's dashboard. (Previously
    // this only client-side navigated to the dashboard without authenticating,
    // so the guest was bounced straight back to the login page.)
    isLoading.value = true;
    router.post(route('demo.login'), { role }, {
        onError: (errors) => handleFormErrors(errors),
        onFinish: () => { isLoading.value = false; },
    });
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

// Flash messages (incl. logout status) are handled by the centralized pipeline in app.js.

// Static feature bullets for the branded panel (display-only)
const brandFeatures = [
    { icon: ChatBubbleLeftRightIcon, text: 'Instant answers from an AI copilot trained on your courses' },
    { icon: SparklesIcon, text: 'Personalized learning paths tailored to every role' },
    { icon: ChartBarIcon, text: 'Track progress, grades and deadlines in one place' },
    { icon: BoltIcon, text: '24/7 study support that never sleeps' },
];
</script>


<template>
    <div>
        <Head :title="isLogin ? 'Login' : 'Sign Up'" />

        <div class="relative min-h-screen flex bg-bg">
            <!-- Floating theme toggle (works on every breakpoint) -->
            <button
                type="button"
                class="fixed right-4 top-4 z-50 flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface/80 text-content-muted shadow-card backdrop-blur transition-colors hover:text-primary"
                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                @click="toggleTheme"
            >
                <SunIcon v-if="isDark" class="h-5 w-5" />
                <MoonIcon v-else class="h-5 w-5" />
            </button>

            <!-- Left Panel - Gradient brand -->
            <div class="bg-brand-gradient relative hidden lg:flex lg:w-1/2 overflow-hidden">
                <!-- Animated aurora glow -->
                <div class="aurora animate-aurora -top-24 -left-24 h-96 w-96 bg-white/25"></div>
                <div class="aurora animate-aurora bottom-0 right-0 h-96 w-96" style="background:#22d3ee66; animation-delay:-6s"></div>
                <div class="aurora animate-aurora left-1/3 top-1/2 h-72 w-72 bg-white/15" style="animation-delay:-11s"></div>
                <!-- Fine dotted texture -->
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.12]"
                    style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"
                ></div>

                <!-- Floating glass chips echoing the landing hero -->
                <div class="absolute right-12 top-28 hidden animate-float rounded-control border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white backdrop-blur-md xl:block">
                    🔒 Cited from your docs
                </div>
                <div class="absolute right-20 bottom-36 hidden animate-float-slow rounded-control border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white backdrop-blur-md xl:block">
                    ⚡ Answers in ~1.2s
                </div>

                <div class="relative z-10 mx-auto flex w-full max-w-lg flex-col justify-center gap-12 p-10 xl:p-14 text-white">
                    <!-- Logo & name -->
                    <div v-reveal class="reveal flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-control bg-white/95 text-primary shadow-lg">
                            <AcademicCapIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight">UniGPT</h1>
                            <p class="text-sm text-white/60">AI Academic Assistant</p>
                        </div>
                    </div>

                    <!-- Headline + features -->
                    <div>
                        <h2 v-reveal="80" class="reveal text-4xl font-bold leading-tight tracking-tight xl:text-[2.75rem]">
                            Your intelligent companion for
                            <span class="bg-gradient-to-r from-white to-cyan-200 bg-clip-text text-transparent">academic excellence.</span>
                        </h2>
                        <p v-reveal="140" class="reveal mt-4 text-lg leading-relaxed text-white/75">
                            Get instant answers, personalized learning paths, and around-the-clock study support.
                        </p>

                        <ul class="mt-10 space-y-4">
                            <li
                                v-for="(feature, i) in brandFeatures"
                                :key="feature.text"
                                v-reveal="200 + i * 80"
                                class="reveal flex items-start gap-3"
                            >
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-control bg-white/15 ring-1 ring-white/20 backdrop-blur-sm">
                                    <component :is="feature.icon" class="h-5 w-5 text-white" />
                                </span>
                                <span class="text-sm leading-relaxed text-white/85">{{ feature.text }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Demo accounts -->
                    <div v-reveal="520" class="reveal">
                        <p class="mb-3 text-sm text-white/60">Try a demo account</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="handleDemoLogin('student')"
                                class="inline-flex items-center gap-1.5 rounded-control border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-primary"
                            >
                                <AcademicCapIcon class="h-4 w-4" /> Student
                            </button>
                            <button
                                type="button"
                                @click="handleDemoLogin('faculty')"
                                class="inline-flex items-center gap-1.5 rounded-control border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-primary"
                            >
                                <UserGroupIcon class="h-4 w-4" /> Faculty
                            </button>
                            <button
                                type="button"
                                @click="handleDemoLogin('admin')"
                                class="inline-flex items-center gap-1.5 rounded-control border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-primary"
                            >
                                <ShieldCheckIcon class="h-4 w-4" /> Admin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Auth form -->
            <div class="relative flex flex-1 flex-col justify-center overflow-hidden px-6 py-12 sm:px-10 lg:w-1/2 xl:w-[45%]">
                <!-- Ambient backdrop -->
                <div class="pointer-events-none absolute inset-0 grid-backdrop"></div>
                <div class="aurora animate-aurora -right-16 top-8 h-72 w-72 bg-primary/30"></div>

                <div class="relative z-10 mx-auto w-full max-w-md">
                    <!-- Mobile Logo -->
                    <div class="mb-8 flex flex-col items-center text-center lg:hidden">
                        <div class="bg-brand-gradient mb-3 flex h-14 w-14 items-center justify-center rounded-card shadow-card-hover">
                            <AcademicCapIcon class="h-8 w-8 text-white" />
                        </div>
                        <h1 class="text-2xl font-bold text-content">UniGPT</h1>
                        <p class="text-sm text-content-muted">AI Academic Assistant</p>
                    </div>

                    <!-- Card with soft gradient glow -->
                    <div v-reveal class="reveal relative">
                        <div class="absolute -inset-3 rounded-card bg-primary/15 blur-2xl"></div>
                        <div class="ui-card relative p-6 sm:p-8">
                        <!-- Form Header -->
                        <div class="mb-6 text-center">
                            <h2 class="text-2xl font-bold tracking-tight text-content">
                                <template v-if="isLogin">Welcome <span class="text-gradient">back</span></template>
                                <template v-else>Create your <span class="text-gradient">account</span></template>
                            </h2>
                            <p class="mt-1 text-sm text-content-muted">
                                {{ isLogin ? 'Sign in to continue to your dashboard' : 'Join the academic community' }}
                            </p>
                        </div>

                        <!-- Role Selection -->
                        <div class="mb-6">
                            <label class="ui-label">Select your role</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="role in roles"
                                    :key="role.value"
                                    type="button"
                                    @click="selectRole(role.value)"
                                    :class="[
                                        'group flex flex-col items-center gap-1.5 rounded-control px-2 py-3 text-center transition-all duration-200',
                                        selectedRole === role.value
                                            ? 'bg-brand-gradient text-white shadow-[0_8px_22px_-8px_rgba(124,92,252,0.6)] scale-[1.03]'
                                            : 'border border-line text-content-muted hover:-translate-y-0.5 hover:border-primary/40 hover:bg-primary-soft hover:text-primary'
                                    ]"
                                    :aria-pressed="selectedRole === role.value"
                                >
                                    <component :is="role.icon" class="h-5 w-5" />
                                    <span class="text-xs font-semibold">{{ role.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Login/Signup Form -->
                        <form @submit.prevent="handleSubmit" class="space-y-5">

                            <!-- Name (Signup only) -->
                            <div v-if="!isLogin">
                                <label class="ui-label">Full name</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <UserIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-model="signupForm.name"
                                        type="text"
                                        required
                                        class="ui-input pl-10"
                                        placeholder="Enter your full name"
                                    />
                                </div>
                                <p v-if="signupForm.errors.name" class="mt-1.5 flex items-center gap-1 text-sm text-danger-fg">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ signupForm.errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="ui-label">Email address</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <EnvelopeIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-if="isLogin"
                                        v-model="loginForm.email"
                                        type="email"
                                        required
                                        :class="[
                                            'ui-input pl-10 pr-10',
                                            emailValidation.valid === false ? 'border-danger-fg focus:border-danger-fg focus:ring-danger-fg/30' :
                                            emailValidation.valid === true ? 'border-success-fg focus:border-success-fg focus:ring-success-fg/30' : ''
                                        ]"
                                        placeholder="your.email@university.edu"
                                    />
                                    <input
                                        v-else
                                        v-model="signupForm.email"
                                        type="email"
                                        required
                                        :class="[
                                            'ui-input pl-10 pr-10',
                                            emailValidation.valid === false ? 'border-danger-fg focus:border-danger-fg focus:ring-danger-fg/30' :
                                            emailValidation.valid === true ? 'border-success-fg focus:border-success-fg focus:ring-success-fg/30' : ''
                                        ]"
                                        placeholder="your.email@university.edu"
                                    />
                                    <span v-if="emailValidation.valid !== null" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <CheckCircleIcon v-if="emailValidation.valid" class="h-5 w-5 text-success-fg" />
                                        <ExclamationTriangleIcon v-else class="h-5 w-5 text-danger-fg" />
                                    </span>
                                </div>
                                <p v-if="emailValidation.message" :class="['mt-1.5 text-sm', emailValidation.valid ? 'text-success-fg' : 'text-danger-fg']">
                                    {{ emailValidation.message }}
                                </p>
                                <p v-if="signupForm.errors.name" class="mt-1.5 flex items-center gap-1 text-sm text-danger-fg">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ signupForm.errors.name }}
                                </p>
                            </div>

                            <!-- Department (Signup only) -->
                            <div v-if="!isLogin">
                                <label class="ui-label">Department</label>
                                <select
                                    v-model="signupForm.department_id"
                                    required
                                    class="ui-input"
                                >
                                    <option value="">Select your department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Student/Employee ID (Signup only) -->
                            <div v-if="!isLogin">
                                <label class="ui-label">
                                    {{ selectedRole === 'student' ? 'Student ID' : 'Employee ID' }}
                                </label>
                                <input
                                    v-if="selectedRole === 'student'"
                                    v-model="signupForm.student_id"
                                    type="text"
                                    required
                                    class="ui-input"
                                    placeholder="Enter your student ID"
                                />
                                <input
                                    v-else
                                    v-model="signupForm.employee_id"
                                    type="text"
                                    required
                                    class="ui-input"
                                    placeholder="Enter your employee ID"
                                />
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="ui-label">Password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <LockClosedIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-if="isLogin"
                                        v-model="loginForm.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        class="ui-input pl-10 pr-10"
                                        placeholder="Enter your password"
                                    />
                                    <input
                                        v-else
                                        v-model="signupForm.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        class="ui-input pl-10 pr-10"
                                        placeholder="Create a secure password"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-content-faint transition-colors hover:text-content-muted focus:outline-none"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                                        <EyeSlashIcon v-else class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Password Strength Indicator (Signup only) -->
                                <div v-if="!isLogin && signupForm.password" class="mt-2">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-xs text-content-muted">Password strength</span>
                                        <span :class="['rounded px-2 py-0.5 text-xs font-medium', passwordStrength.color]">
                                            {{ passwordStrength.message }}
                                        </span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-neutral-bg">
                                        <div
                                            class="h-2 rounded-full transition-all duration-300"
                                            :class="passwordStrength.strength <= 40 ? 'bg-danger-fg' : passwordStrength.strength <= 70 ? 'bg-warning-fg' : 'bg-success-fg'"
                                            :style="{ width: passwordStrength.strength + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Password (Signup only) -->
                            <div v-if="!isLogin">
                                <label class="ui-label">Confirm password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <LockClosedIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-model="signupForm.password_confirmation"
                                        type="password"
                                        required
                                        class="ui-input pl-10 pr-10"
                                        placeholder="Confirm your password"
                                    />
                                    <span v-if="signupForm.password_confirmation && signupForm.password" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <CheckCircleIcon v-if="signupForm.password === signupForm.password_confirmation" class="h-5 w-5 text-success-fg" />
                                        <ExclamationTriangleIcon v-else class="h-5 w-5 text-danger-fg" />
                                    </span>
                                </div>
                                <p v-if="signupForm.password_confirmation && signupForm.password !== signupForm.password_confirmation" class="mt-1.5 text-sm text-danger-fg">
                                    Passwords do not match
                                </p>
                            </div>

                            <!-- Remember Me & Forgot Password (Login only) -->
                            <div v-if="isLogin" class="flex items-center justify-between">
                                <label class="flex cursor-pointer items-center">
                                    <input
                                        v-model="loginForm.remember"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-line text-primary focus:ring-primary"
                                    />
                                    <span class="ml-2 text-sm text-content-muted">Remember me</span>
                                </label>
                                <Link
                                    href="/password/reset"
                                    class="text-sm font-semibold text-content-muted transition-colors hover:text-content"
                                >
                                    Forgot password?
                                </Link>
                            </div>

                            <!-- Terms & Conditions (Signup only) -->
                            <div v-if="!isLogin">
                                <label class="flex cursor-pointer items-start">
                                    <input
                                        v-model="signupForm.terms"
                                        type="checkbox"
                                        required
                                        class="mt-0.5 h-4 w-4 rounded border-line text-primary focus:ring-primary"
                                    />
                                    <span class="ml-2 text-sm text-content-muted">
                                        I agree to the
                                        <Link href="/terms" class="font-semibold text-content hover:underline">Terms of Service</Link>
                                        and
                                        <Link href="/privacy" class="font-semibold text-content hover:underline">Privacy Policy</Link>
                                    </span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="isLoading"
                                class="ui-btn-primary w-full"
                            >
                                <template v-if="isLoading">
                                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                    Please wait...
                                </template>
                                <template v-else>
                                    {{ isLogin ? 'Sign in' : 'Create account' }}
                                    <ArrowRightIcon class="h-5 w-5" />
                                </template>
                            </button>
                        </form>

                        <!-- Toggle Login/Signup -->
                        <div class="mt-6 text-center">
                            <p class="text-sm text-content-muted">
                                {{ isLogin ? "Don't have an account?" : "Already have an account?" }}
                                <button
                                    type="button"
                                    @click="toggleMode"
                                    class="font-semibold text-primary transition-colors hover:text-primary-hover"
                                >
                                    {{ isLogin ? 'Sign up' : 'Sign in' }}
                                </button>
                            </p>
                        </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <p class="mt-6 text-center text-xs text-content-faint">
                        © {{ new Date().getFullYear() }} UniGPT. Empowering education through AI.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

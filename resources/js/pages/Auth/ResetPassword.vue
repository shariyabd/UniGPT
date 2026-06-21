<script setup>
import { onMounted, ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AcademicCapIcon,
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';
import { useTheme } from '@/composables/useTheme';

const { isDark, initTheme, toggleTheme } = useTheme();
onMounted(() => initTheme());

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const showPassword = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const passwordsMatch = computed(
    () => form.password && form.password === form.password_confirmation
);

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div>
        <Head title="Set a new password" />

        <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-bg px-6 py-10">
            <!-- Theme toggle -->
            <button
                type="button"
                class="fixed right-4 top-4 z-50 flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface/80 text-content-muted shadow-card backdrop-blur transition-colors hover:text-primary"
                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                @click="toggleTheme"
            >
                <SunIcon v-if="isDark" class="h-5 w-5" />
                <MoonIcon v-else class="h-5 w-5" />
            </button>

            <!-- Ambient backdrop -->
            <div class="pointer-events-none absolute inset-0 grid-backdrop"></div>
            <div class="aurora animate-aurora -right-16 top-8 h-72 w-72 bg-primary/30"></div>

            <div class="relative z-10 w-full max-w-md">
                <!-- Brand -->
                <div class="mb-8 flex flex-col items-center text-center">
                    <div class="bg-brand-gradient mb-3 flex h-14 w-14 items-center justify-center rounded-card shadow-card-hover">
                        <AcademicCapIcon class="h-8 w-8 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-content">UniGPT</h1>
                    <p class="text-sm text-content-muted">AI Academic Assistant</p>
                </div>

                <div class="relative">
                    <div class="absolute -inset-3 rounded-card bg-primary/15 blur-2xl"></div>
                    <div class="ui-card relative p-6 sm:p-8">
                        <div class="mb-6 text-center">
                            <h2 class="text-2xl font-bold tracking-tight text-content">
                                Set a new <span class="text-gradient">password</span>
                            </h2>
                            <p class="mt-1 text-sm text-content-muted">
                                Choose a strong password for your account.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <!-- Email -->
                            <div>
                                <label class="ui-label">Email address</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <EnvelopeIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="ui-input pl-10"
                                        placeholder="your.email@university.edu"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="mt-1.5 flex items-center gap-1 text-sm text-danger-fg">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- New password -->
                            <div>
                                <label class="ui-label">New password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <LockClosedIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        autofocus
                                        class="ui-input pl-10 pr-10"
                                        placeholder="Create a secure password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-content-faint transition-colors hover:text-content-muted focus:outline-none"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                                        <EyeSlashIcon v-else class="h-5 w-5" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-1.5 flex items-center gap-1 text-sm text-danger-fg">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Confirm password -->
                            <div>
                                <label class="ui-label">Confirm password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <LockClosedIcon class="h-5 w-5 text-content-faint" />
                                    </span>
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        class="ui-input pl-10 pr-10"
                                        placeholder="Confirm your password"
                                    />
                                    <span v-if="form.password_confirmation && form.password" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <CheckCircleIcon v-if="passwordsMatch" class="h-5 w-5 text-success-fg" />
                                        <ExclamationTriangleIcon v-else class="h-5 w-5 text-danger-fg" />
                                    </span>
                                </div>
                                <p v-if="form.password_confirmation && !passwordsMatch" class="mt-1.5 text-sm text-danger-fg">
                                    Passwords do not match
                                </p>
                            </div>

                            <button type="submit" :disabled="form.processing" class="ui-btn-primary w-full">
                                <template v-if="form.processing">
                                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                    Resetting...
                                </template>
                                <template v-else>
                                    Reset password
                                    <ArrowRightIcon class="h-5 w-5" />
                                </template>
                            </button>
                        </form>

                        <div class="mt-6 text-center">
                            <Link :href="route('login')" class="inline-flex items-center gap-1.5 text-sm font-semibold text-content-muted transition-colors hover:text-content">
                                <ArrowLeftIcon class="h-4 w-4" /> Back to sign in
                            </Link>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-content-faint">
                    © {{ new Date().getFullYear() }} UniGPT. Empowering education through AI.
                </p>
            </div>
        </div>
    </div>
</template>

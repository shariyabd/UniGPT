<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { useTheme } from '@/composables/useTheme';
import { Cog6ToothIcon, SunIcon, MoonIcon, ComputerDesktopIcon, BellIcon, LanguageIcon, SwatchIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    preferences: { type: Object, default: () => ({ theme: 'light', notifications: true, language: 'en' }) },
    supportedLanguages: { type: Array, default: () => [{ code: 'en', name: 'English' }] },
});

const { setTheme } = useTheme();

const form = useForm({
    theme: props.preferences.theme ?? 'light',
    notifications: props.preferences.notifications ?? true,
    language: props.preferences.language ?? (props.supportedLanguages[0]?.code ?? 'en'),
});

const themes = [
    { value: 'light', label: 'Light', icon: SunIcon },
    { value: 'dark', label: 'Dark', icon: MoonIcon },
    { value: 'system', label: 'System', icon: ComputerDesktopIcon },
];

// Apply the chosen theme immediately for a live preview; it's persisted on save.
const chooseTheme = (value) => {
    form.theme = value;
    setTheme(value);
};

const submit = () => {
    form.patch(route('settings.update'), { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head title="Settings" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader title="Settings" subtitle="Customize your UniGPT experience" :icon="Cog6ToothIcon" />

                <form @submit.prevent="submit" class="space-y-6 sm:space-y-8">
                    <!-- Appearance -->
                    <Card title="Appearance" subtitle="Choose how UniGPT looks to you" :icon="SwatchIcon">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button v-for="theme in themes" :key="theme.value" type="button" @click="chooseTheme(theme.value)"
                                :aria-label="theme.label"
                                :class="[
                                    'flex flex-col items-center gap-2 p-5 rounded-card border transition-all',
                                    form.theme === theme.value
                                        ? 'border-transparent bg-primary text-white shadow-card'
                                        : 'border-line bg-bg text-content hover:bg-surface hover:shadow-card hover:-translate-y-0.5'
                                ]">
                                <component :is="theme.icon" class="w-7 h-7"
                                    :class="form.theme === theme.value ? 'text-white' : 'text-content-muted'" />
                                <span class="text-sm font-semibold"
                                    :class="form.theme === theme.value ? 'text-white' : 'text-content'">{{ theme.label }}</span>
                            </button>
                        </div>
                    </Card>

                    <!-- Notifications -->
                    <Card title="Notifications" subtitle="Manage what you get notified about" :icon="BellIcon">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="ui-icon-tile bg-primary-soft text-primary">
                                    <BellIcon class="w-5 h-5" />
                                </span>
                                <div>
                                    <p class="font-semibold text-content">Notifications</p>
                                    <p class="text-sm text-content-muted">Receive updates about deadlines and replies</p>
                                </div>
                            </div>
                            <button type="button" @click="form.notifications = !form.notifications"
                                role="switch" :aria-checked="form.notifications" aria-label="Toggle notifications"
                                :class="[
                                    'relative w-14 h-8 shrink-0 rounded-full transition-colors',
                                    form.notifications ? 'bg-primary' : 'bg-neutral-bg'
                                ]">
                                <span :class="[
                                    'absolute top-1 left-1 w-6 h-6 bg-white rounded-full shadow-sm transition-transform',
                                    form.notifications ? 'translate-x-6' : ''
                                ]"></span>
                            </button>
                        </div>
                    </Card>

                    <!-- Language -->
                    <Card title="Language" subtitle="Default language for AI chat responses" :icon="LanguageIcon">
                        <label for="settings-language" class="ui-label">Preferred language</label>
                        <select id="settings-language" v-model="form.language" class="ui-input w-full md:w-72">
                            <option v-for="lang in supportedLanguages" :key="lang.code" :value="lang.code">{{ lang.name }}</option>
                        </select>
                        <p class="mt-2 text-xs text-content-muted">
                            New chats with the AI assistant will default to this language. Available languages are managed by your administrator.
                        </p>
                        <p v-if="form.errors.language" class="mt-1 text-xs text-danger-fg">{{ form.errors.language }}</p>
                    </Card>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="ui-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Saving…' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
</template>

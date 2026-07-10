<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import {
    EnvelopeIcon,
    ServerStackIcon,
    IdentificationIcon,
    PaperAirplaneIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            mailer: 'smtp',
            host: '',
            port: 587,
            encryption: 'tls',
            username: '',
            from_address: '',
            from_name: '',
        }),
    },
    mailerOptions: {
        type: Array,
        default: () => [
            { value: 'smtp', label: 'SMTP' },
            { value: 'log', label: 'Log (write to laravel.log, no delivery)' },
        ],
    },
    encryptionOptions: {
        type: Array,
        default: () => [
            { value: 'tls', label: 'TLS' },
            { value: 'ssl', label: 'SSL' },
            { value: '', label: 'None' },
        ],
    },
    status: {
        type: Object,
        default: () => ({ passwordStored: false }),
    },
});

const form = useForm({
    mailer: props.settings.mailer ?? 'smtp',
    host: props.settings.host ?? '',
    port: props.settings.port ?? 587,
    encryption: props.settings.encryption ?? '',
    username: props.settings.username ?? '',
    password: '',
    remove_password: false,
    from_address: props.settings.from_address ?? '',
    from_name: props.settings.from_name ?? '',
});

const testTo = ref('');
const isTesting = ref(false);
const testResult = ref(null);

const save = () => {
    form.patch(route('admin.email-settings.update'), {
        preserveScroll: true,
        // Never keep the typed password in memory after a successful save.
        onSuccess: () => {
            form.password = '';
            form.remove_password = false;
        },
    });
};

const sendTest = async () => {
    isTesting.value = true;
    testResult.value = null;
    try {
        const { data } = await axios.post(route('admin.email-settings.test'), { to: testTo.value });
        testResult.value = { ok: data.available, ...data };
    } catch (e) {
        const message = e?.response?.data?.message ?? 'Test request failed.';
        testResult.value = { ok: false, message };
    } finally {
        isTesting.value = false;
    }
};
</script>

<template>
    <div>
        <Head title="Email Configuration" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Email Configuration"
                    subtitle="Configure the SMTP mailer used for password resets and notifications"
                    :icon="EnvelopeIcon"
                />

                <!-- Status & test -->
                <Card title="Active Mailer" subtitle="Current transport and a live delivery check" :icon="EnvelopeIcon">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="violet" class="uppercase">{{ settings.mailer }}</Badge>
                        <span
                            v-if="status.passwordStored"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-success-fg"
                        >
                            <CheckCircleIcon class="h-4 w-4" /> SMTP password stored
                        </span>
                        <span
                            v-else-if="settings.mailer === 'smtp'"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-warning-fg"
                        >
                            <ExclamationTriangleIcon class="h-4 w-4" /> No SMTP password configured
                        </span>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="ui-label">Send a test email to</label>
                            <input
                                v-model="testTo"
                                type="email"
                                placeholder="you@example.com"
                                class="ui-input"
                            />
                        </div>
                        <button
                            type="button"
                            @click="sendTest"
                            :disabled="isTesting || !testTo"
                            class="ui-btn-secondary disabled:opacity-50"
                        >
                            <PaperAirplaneIcon class="h-4 w-4" />
                            {{ isTesting ? 'Sending…' : 'Send Test Email' }}
                        </button>
                    </div>

                    <div
                        v-if="testResult"
                        :class="`mt-4 flex items-center gap-2 rounded-control p-3 text-sm ${testResult.ok ? 'bg-success-bg text-success-fg' : 'bg-warning-bg text-warning-fg'}`"
                    >
                        <component :is="testResult.ok ? CheckCircleIcon : ExclamationTriangleIcon" class="h-4 w-4 flex-shrink-0" />
                        <span>{{ testResult.message }}</span>
                    </div>

                    <p class="mt-3 text-xs text-content-muted">
                        Save your settings before sending a test — the test uses the currently stored configuration.
                    </p>
                </Card>

                <!-- Editable settings -->
                <form @submit.prevent="save" class="space-y-6 sm:space-y-8">
                    <Card title="SMTP Server" subtitle="Connection details for your mail provider" :icon="ServerStackIcon">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <label class="ui-label">Mailer</label>
                                <select v-model="form.mailer" class="ui-input">
                                    <option v-for="option in mailerOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-content-muted">
                                    Use SMTP to deliver real email. Log writes messages to the application log instead.
                                </p>
                                <p v-if="form.errors.mailer" class="mt-1 text-xs text-danger-fg">{{ form.errors.mailer }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Encryption</label>
                                <select v-model="form.encryption" class="ui-input">
                                    <option v-for="option in encryptionOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.encryption" class="mt-1 text-xs text-danger-fg">{{ form.errors.encryption }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Host</label>
                                <input
                                    v-model="form.host"
                                    type="text"
                                    placeholder="smtp.mailtrap.io"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.host" class="mt-1 text-xs text-danger-fg">{{ form.errors.host }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Port</label>
                                <input
                                    v-model.number="form.port"
                                    type="number" min="1" max="65535"
                                    placeholder="587"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.port" class="mt-1 text-xs text-danger-fg">{{ form.errors.port }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Username</label>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="SMTP username"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.username" class="mt-1 text-xs text-danger-fg">{{ form.errors.username }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Password</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="off"
                                    :placeholder="status.passwordStored ? '•••••••••• (stored — leave blank to keep)' : 'SMTP password'"
                                    class="ui-input font-mono"
                                />
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="text-xs text-content-muted">Stored encrypted; never shown again.</p>
                                    <label v-if="status.passwordStored" class="inline-flex items-center gap-1.5 text-xs text-danger-fg">
                                        <input v-model="form.remove_password" type="checkbox" class="rounded border-line text-danger-fg" />
                                        Remove stored password
                                    </label>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-danger-fg">{{ form.errors.password }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card title="From Address" subtitle="The sender shown on outgoing email" :icon="IdentificationIcon">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <label class="ui-label">From Email</label>
                                <input
                                    v-model="form.from_address"
                                    type="email"
                                    placeholder="no-reply@university.edu"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.from_address" class="mt-1 text-xs text-danger-fg">{{ form.errors.from_address }}</p>
                            </div>
                            <div>
                                <label class="ui-label">From Name</label>
                                <input
                                    v-model="form.from_name"
                                    type="text"
                                    placeholder="UniNexus"
                                    class="ui-input"
                                />
                                <p v-if="form.errors.from_name" class="mt-1 text-xs text-danger-fg">{{ form.errors.from_name }}</p>
                            </div>
                        </div>
                    </Card>

                    <div class="flex items-center justify-end gap-3">
                        <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm font-medium text-success-fg">
                            <CheckCircleIcon class="h-4 w-4" /> Saved.
                        </span>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="ui-btn-primary disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving…' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    </div>
</template>

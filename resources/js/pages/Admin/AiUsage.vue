<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { useConfirm } from '@/composables/useConfirm';
import {
    CpuChipIcon,
    BoltIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    NoSymbolIcon,
    LockClosedIcon,
    LockOpenIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    requests: { type: Object, default: () => ({ data: [] }) },
});

const { confirm } = useConfirm();

const summaryCards = computed(() => [
    { label: 'Total Tokens', value: formatNumber(props.summary.total_tokens ?? 0), icon: BoltIcon },
    { label: 'Total Requests', value: formatNumber(props.summary.total_requests ?? 0), icon: ChatBubbleLeftRightIcon },
    { label: 'Active Users', value: formatNumber(props.summary.active_users ?? 0), icon: UsersIcon },
    { label: 'Blocked Users', value: formatNumber(props.summary.blocked_users ?? 0), icon: NoSymbolIcon },
]);

const requestRows = computed(() => props.requests?.data ?? []);

function formatNumber(value) {
    return new Intl.NumberFormat('en-US').format(value ?? 0);
}

function formatDateTime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

// --- Block modal -------------------------------------------------------------
const blockTarget = ref(null);
const blockForm = ref({ reason: '', expires_in_days: '' });
const isSubmitting = ref(false);

// null expiry = permanent; otherwise the block auto-expires after N days.
const durationOptions = [
    { label: 'Permanent', value: '' },
    { label: '1 day', value: 1 },
    { label: '7 days', value: 7 },
    { label: '30 days', value: 30 },
];

function openBlockModal(user) {
    blockTarget.value = user;
    blockForm.value = { reason: '', expires_in_days: '' };
}

function closeBlockModal() {
    blockTarget.value = null;
}

function submitBlock() {
    if (!blockTarget.value || blockForm.value.reason.trim().length < 5) return;
    isSubmitting.value = true;
    router.post(route('admin.ai-usage.block', blockTarget.value.id), {
        reason: blockForm.value.reason.trim(),
        expires_in_days: blockForm.value.expires_in_days === '' ? null : Number(blockForm.value.expires_in_days),
    }, {
        preserveScroll: true,
        onSuccess: () => closeBlockModal(),
        onFinish: () => { isSubmitting.value = false; },
    });
}

async function unblock(user) {
    const ok = await confirm({
        title: 'Restore AI chat access',
        message: `Restore AI chat access for ${user.name}?`,
        confirmLabel: 'Restore',
    });
    if (!ok) return;
    router.post(route('admin.ai-usage.unblock', user.id), {}, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head title="AI Usage" />

        <AppLayout>
            <div class="page-container py-8 space-y-6">
                <PageHeader
                    title="AI Usage & Access"
                    subtitle="Monitor per-user token usage and control AI chat access to optimize API costs"
                    :icon="CpuChipIcon"
                />

                <!-- Summary cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card v-for="card in summaryCards" :key="card.label">
                        <div class="flex items-center gap-3">
                            <div class="ui-icon-tile bg-primary-soft text-primary flex-shrink-0">
                                <component :is="card.icon" class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-content-muted">{{ card.label }}</p>
                                <p class="text-xl font-bold text-content">{{ card.value }}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Per-user usage -->
                <Card title="Usage by User" :icon="UsersIcon">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line text-left text-content-muted">
                                    <th class="py-2 pr-4 font-medium">User</th>
                                    <th class="py-2 pr-4 font-medium">Role</th>
                                    <th class="py-2 pr-4 font-medium text-right">Requests</th>
                                    <th class="py-2 pr-4 font-medium text-right">Tokens</th>
                                    <th class="py-2 pr-4 font-medium">Last Used</th>
                                    <th class="py-2 pr-4 font-medium">Status</th>
                                    <th class="py-2 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-b border-line/60">
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-content">{{ user.name }}</p>
                                        <p class="text-xs text-content-faint">{{ user.email }}</p>
                                    </td>
                                    <td class="py-3 pr-4 capitalize text-content-muted">{{ user.role ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-right text-content">{{ formatNumber(user.request_count) }}</td>
                                    <td class="py-3 pr-4 text-right font-semibold text-content">{{ formatNumber(user.total_tokens) }}</td>
                                    <td class="py-3 pr-4 text-content-muted">{{ formatDateTime(user.last_used_at) }}</td>
                                    <td class="py-3 pr-4">
                                        <span
                                            v-if="user.is_blocked"
                                            class="ui-badge bg-danger-bg text-danger-fg"
                                            :title="user.block_reason"
                                        >
                                            Blocked{{ user.blocked_until ? ' (temp)' : '' }}
                                        </span>
                                        <span v-else class="ui-badge bg-success-bg text-success-fg">Active</span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <button
                                            v-if="user.is_blocked"
                                            @click="unblock(user)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-success-fg hover:underline"
                                        >
                                            <LockOpenIcon class="w-4 h-4" /> Restore
                                        </button>
                                        <button
                                            v-else
                                            @click="openBlockModal(user)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-danger-fg hover:underline"
                                        >
                                            <LockClosedIcon class="w-4 h-4" /> Block
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="7" class="py-6 text-center text-content-muted">No AI chat usage yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <!-- Recent requests -->
                <Card title="Recent Requests" :icon="ChatBubbleLeftRightIcon">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line text-left text-content-muted">
                                    <th class="py-2 pr-4 font-medium">User</th>
                                    <th class="py-2 pr-4 font-medium">Query</th>
                                    <th class="py-2 pr-4 font-medium text-right">Tokens</th>
                                    <th class="py-2 pr-4 font-medium">Model</th>
                                    <th class="py-2 font-medium">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in requestRows" :key="row.id" class="border-b border-line/60 align-top">
                                    <td class="py-3 pr-4 text-content">{{ row.user?.name ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-content-muted max-w-md">
                                        <span class="line-clamp-2">{{ row.query }}</span>
                                    </td>
                                    <td class="py-3 pr-4 text-right font-semibold text-content">{{ formatNumber(row.tokens) }}</td>
                                    <td class="py-3 pr-4 text-content-faint">{{ row.model ?? '—' }}</td>
                                    <td class="py-3 text-content-muted whitespace-nowrap">{{ formatDateTime(row.created_at) }}</td>
                                </tr>
                                <tr v-if="requestRows.length === 0">
                                    <td colspan="5" class="py-6 text-center text-content-muted">No requests recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>

        <!-- Block modal -->
        <Transition
            enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0"
        >
            <div v-if="blockTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-content/40 backdrop-blur-sm" @click="closeBlockModal"></div>
                <div class="relative w-full max-w-md ui-card p-6 space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="ui-icon-tile bg-danger-bg text-danger-fg">
                                <NoSymbolIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-content">Block AI Chat</h3>
                                <p class="text-xs text-content-muted">{{ blockTarget.name }}</p>
                            </div>
                        </div>
                        <button @click="closeBlockModal" class="ui-btn-ghost h-8 w-8 p-0" aria-label="Close">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <div>
                        <label class="ui-label">Reason (shown to the user) *</label>
                        <textarea
                            v-model="blockForm.reason"
                            rows="3"
                            class="ui-input resize-none"
                            placeholder="e.g. Due to repeated irrelevant usage, your AI chat access has been temporarily blocked."
                        ></textarea>
                        <p class="mt-1 text-xs text-content-faint">Minimum 5 characters.</p>
                    </div>

                    <div>
                        <label class="ui-label">Duration</label>
                        <select v-model="blockForm.expires_in_days" class="ui-input">
                            <option v-for="opt in durationOptions" :key="opt.label" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="closeBlockModal" class="ui-btn-secondary">Cancel</button>
                        <button
                            @click="submitBlock"
                            :disabled="isSubmitting || blockForm.reason.trim().length < 5"
                            class="ui-btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            {{ isSubmitting ? 'Blocking…' : 'Block Access' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

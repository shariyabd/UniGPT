<script setup>
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import { ShieldCheckIcon, CheckCircleIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissionsByCategory: { type: Object, default: () => ({}) },
    totalPermissions: { type: Number, default: 0 },
});

// Working copy of each role's permission ids, edited in place before saving.
const draft = reactive({});
// Snapshot of the persisted state, used to detect unsaved changes per role.
const original = reactive({});

props.roles.forEach((role) => {
    draft[role.id] = [...role.permissionIds];
    original[role.id] = [...role.permissionIds];
});

const saving = reactive({});

const has = (roleId, permissionId) => draft[roleId]?.includes(permissionId);

const toggle = (role, permissionId) => {
    if (role.locked) return;

    const ids = draft[role.id];
    const index = ids.indexOf(permissionId);
    if (index === -1) {
        ids.push(permissionId);
    } else {
        ids.splice(index, 1);
    }
};

const isDirty = (roleId) => {
    const a = [...draft[roleId]].sort((x, y) => x - y);
    const b = [...original[roleId]].sort((x, y) => x - y);

    return a.length !== b.length || a.some((value, i) => value !== b[i]);
};

const save = (role) => {
    if (role.locked || !isDirty(role.id)) return;

    saving[role.id] = true;
    router.patch(route('admin.roles.permissions', role.id), { permissions: draft[role.id] }, {
        preserveScroll: true,
        onSuccess: () => {
            original[role.id] = [...draft[role.id]];
        },
        onFinish: () => {
            saving[role.id] = false;
        },
    });
};

const categories = computed(() => Object.keys(props.permissionsByCategory));

const grantedCount = (roleId) => draft[roleId]?.length ?? 0;
</script>

<template>
    <div>
        <Head title="Roles & Permissions" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="Roles & Permissions"
                    subtitle="Toggle which permissions each role holds. Changes take effect immediately after saving."
                    :icon="ShieldCheckIcon"
                />

                <Card padding="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                        Permission
                                    </th>
                                    <th
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-content-faint"
                                    >
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold normal-case text-content">
                                                {{ role.name }}
                                                <LockClosedIcon v-if="role.locked" class="h-3.5 w-3.5 text-content-faint" />
                                            </span>
                                            <span class="text-[11px] font-medium normal-case text-primary">
                                                {{ role.locked ? `All ${totalPermissions}` : `${grantedCount(role.id)} / ${totalPermissions}` }}
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="category in categories" :key="category">
                                    <tr class="bg-bg">
                                        <td
                                            :colspan="roles.length + 1"
                                            class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary"
                                        >
                                            {{ category }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="permission in permissionsByCategory[category]"
                                        :key="permission.id"
                                        class="border-b border-line hover:bg-bg transition-colors"
                                    >
                                        <td class="px-4 py-3 text-sm text-content">
                                            <span class="font-medium text-content">{{ permission.name }}</span>
                                            <span class="block text-[11px] text-content-faint">{{ permission.slug }}</span>
                                        </td>
                                        <td
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="px-4 py-3 text-center"
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-line text-primary focus:ring-primary focus:ring-offset-0 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                :checked="role.locked || has(role.id, permission.id)"
                                                :disabled="role.locked"
                                                :aria-label="`Toggle ${permission.name} for ${role.name}`"
                                                @change="toggle(role, permission.id)"
                                            >
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-line bg-bg">
                                    <td class="px-4 py-4 text-sm font-medium text-content-muted">
                                        Save changes
                                    </td>
                                    <td v-for="role in roles" :key="role.id" class="px-4 py-4 text-center">
                                        <button
                                            v-if="!role.locked"
                                            :disabled="!isDirty(role.id) || saving[role.id]"
                                            class="ui-btn-primary inline-flex items-center gap-1.5 px-3 py-1.5 text-xs disabled:opacity-40 disabled:cursor-not-allowed"
                                            @click="save(role)"
                                        >
                                            <CheckCircleIcon class="h-4 w-4" />
                                            {{ saving[role.id] ? 'Saving…' : 'Save' }}
                                        </button>
                                        <span v-else class="inline-flex items-center gap-1 text-xs text-content-faint">
                                            <LockClosedIcon class="h-3.5 w-3.5" />
                                            Locked
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    </div>
</template>

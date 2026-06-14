<script setup>
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    <Head title="Roles & Permissions" />

    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center">
                    <ShieldCheckIcon class="w-7 h-7 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Roles &amp; Permissions</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Toggle which permissions each role holds. Changes take effect immediately after saving.
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Permission
                                </th>
                                <th
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="flex items-center gap-1 text-gray-900 dark:text-white">
                                            {{ role.name }}
                                            <LockClosedIcon v-if="role.locked" class="w-3.5 h-3.5 text-gray-400" />
                                        </span>
                                        <span class="text-[11px] font-normal normal-case text-gray-400">
                                            {{ role.locked ? `All ${totalPermissions}` : `${grantedCount(role.id)} / ${totalPermissions}` }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                            <template v-for="category in categories" :key="category">
                                <tr class="bg-gray-50/70 dark:bg-slate-900/30">
                                    <td :colspan="roles.length + 1" class="px-6 py-2 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                        {{ category }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="permission in permissionsByCategory[category]"
                                    :key="permission.id"
                                    class="hover:bg-gray-50 dark:hover:bg-slate-700/30"
                                >
                                    <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ permission.name }}
                                        <span class="block text-[11px] text-gray-400">{{ permission.slug }}</span>
                                    </td>
                                    <td
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="px-6 py-3 text-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40"
                                            :checked="role.locked || has(role.id, permission.id)"
                                            :disabled="role.locked"
                                            @change="toggle(role, permission.id)"
                                        >
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Save changes
                                </td>
                                <td v-for="role in roles" :key="role.id" class="px-6 py-4 text-center">
                                    <button
                                        v-if="!role.locked"
                                        :disabled="!isDirty(role.id) || saving[role.id]"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed bg-indigo-600 text-white hover:bg-indigo-700"
                                        @click="save(role)"
                                    >
                                        <CheckCircleIcon class="w-4 h-4" />
                                        {{ saving[role.id] ? 'Saving…' : 'Save' }}
                                    </button>
                                    <span v-else class="text-xs text-gray-400">Locked</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

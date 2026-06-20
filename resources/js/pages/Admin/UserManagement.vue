<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useConfirm } from '@/composables/useConfirm';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import {
    UsersIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    ShieldCheckIcon,
    UserCircleIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

// Component state
const { can } = usePermissions();
const { confirm } = useConfirm();

const selectedTab = ref('users');
const selectedUsers = ref(new Set());
const showUserModal = ref(false);
const editingUser = ref(null);
const formErrors = ref({});
const isSaving = ref(false);

// Props from the backend
const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    roles: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) }
});

// Users (mapped from the Laravel paginator's data into the template's field names)
const users = computed(() =>
    (props.users?.data || []).map(user => ({
        id: user.id,
        name: user.name,
        email: user.email,
        avatar: user.avatar,
        role: user.role,
        department: user.department,
        department_id: user.department_id,
        semester: user.semester,
        status: user.status,
        isProtected: user.isProtected,
        lastLogin: user.lastLogin,
        joinedDate: user.joinedDate,
        permissions: user.permissions
    }))
);

// Filters are applied SERVER-SIDE so they work across all paginated pages.
const selectedRole = ref(props.filters.role ?? 'all');
const selectedStatus = ref(props.filters.status ?? 'all');
const searchQuery = ref(props.filters.search ?? '');

const applyFilters = () => {
    router.get(
        route('admin.users'),
        {
            role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            search: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});
watch([selectedRole, selectedStatus], applyFilters);

const clearFilters = () => {
    selectedRole.value = 'all';
    selectedStatus.value = 'all';
    searchQuery.value = '';
    applyFilters();
};

// Filter options
const roleOptions = [
    { value: 'all', label: 'All Roles' },
    { value: 'student', label: 'Students' },
    { value: 'faculty', label: 'Faculty' },
    { value: 'admin', label: 'Administrators' }
];

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'active', label: 'Active Users' },
    { value: 'inactive', label: 'Inactive Users' }
];

// The current page is already filtered server-side; expose it under the name
// the template uses for the list, select-all and empty-state.
const filteredUsers = computed(() => users.value);

const userStats = computed(() => ({
    total: props.stats?.total_users ?? users.value.length,
    active: props.stats?.active_users ?? users.value.filter(u => u.status === 'active').length,
    students: props.stats?.total_students ?? users.value.filter(u => u.role === 'student').length,
    faculty: props.stats?.total_faculty ?? users.value.filter(u => u.role === 'faculty').length,
    admins: props.stats?.total_admins ?? users.value.filter(u => u.role === 'admin').length
}));

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getRoleVariant = (role) => {
    const variants = {
        student: 'info',
        faculty: 'success',
        admin: 'violet'
    };
    return variants[role] || 'slate';
};

const getStatusVariant = (status) => {
    const variants = {
        active: 'success',
        inactive: 'danger',
        pending: 'warning'
    };
    return variants[status] || 'warning';
};

const getRoleIcon = (role) => {
    const icons = {
        student: AcademicCapIcon,
        faculty: UserCircleIcon,
        admin: ShieldCheckIcon
    };
    return icons[role] || UserCircleIcon;
};

const getInitial = (name) => (name ? name.charAt(0).toUpperCase() : '?');

// Actions
const toggleUserSelection = (userId) => {
    if (selectedUsers.value.has(userId)) {
        selectedUsers.value.delete(userId);
    } else {
        selectedUsers.value.add(userId);
    }
};

const selectAllUsers = () => {
    if (selectedUsers.value.size === filteredUsers.value.length) {
        selectedUsers.value.clear();
    } else {
        selectedUsers.value = new Set(filteredUsers.value.map(u => u.id));
    }
};

const openUserModal = (user = null) => {
    formErrors.value = {};
    editingUser.value = user ? {
        ...user,
        department_id: user.department_id ?? '',
        password: ''
    } : {
        name: '',
        email: '',
        phone: '',
        password: '',
        role: 'student',
        department: '',
        department_id: '',
        semester: '',
        student_id: '',
        employee_id: '',
        status: 'active',
        permissions: []
    };
    showUserModal.value = true;
};

const saveUser = () => {
    const editing = editingUser.value;
    formErrors.value = {};
    isSaving.value = true;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showUserModal.value = false;
            editingUser.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
        onFinish: () => {
            isSaving.value = false;
        }
    };

    if (editing.id) {
        // Update existing user
        router.patch(route('admin.users.update', editing.id), {
            name: editing.name,
            email: editing.email,
            department_id: editing.department_id || null,
            semester: editing.semester || null,
            is_active: editing.status === 'active'
        }, options);
    } else {
        // Create new user
        router.post(route('admin.users.store'), {
            name: editing.name,
            email: editing.email,
            password: editing.password,
            role: editing.role,
            department_id: editing.department_id || null,
            student_id: editing.student_id || null,
            employee_id: editing.employee_id || null,
            semester: editing.semester || null
        }, options);
    }
};

const deleteUser = async (userId) => {
    const confirmed = await confirm({
        title: 'Delete user',
        message: 'This will permanently delete the user account. This action cannot be undone.',
        confirmLabel: 'Delete',
        variant: 'danger'
    });

    if (!confirmed) return;

    router.delete(route('admin.users.destroy', userId), {
        preserveScroll: true
    });
};

const bulkUpdateStatus = (status) => {
    const ids = Array.from(selectedUsers.value);
    ids.forEach(userId => {
        const user = users.value.find(u => u.id === userId);
        // Only toggle when the current state differs from the desired state
        if (user && user.status !== status) {
            router.patch(route('admin.users.toggle-active', userId), {}, {
                preserveScroll: true,
                preserveState: true
            });
        }
    });
    selectedUsers.value.clear();
};

const bulkUpdateRole = (role) => {
    const ids = Array.from(selectedUsers.value);
    ids.forEach(userId => {
        router.patch(route('admin.users.role', userId), { role }, {
            preserveScroll: true,
            preserveState: true
        });
    });
    selectedUsers.value.clear();
};

</script>

<template>
    <div>
        <Head title="User Management" />

        <AppLayout>
            <div class="page-container py-8 space-y-6 sm:space-y-8">
                <PageHeader
                    title="User Management"
                    subtitle="Manage users, roles, and permissions"
                    :icon="UsersIcon"
                >
                    <template #actions>
                        <Link
                            v-if="can('manage_permissions')"
                            :href="route('admin.roles')"
                            class="ui-btn-secondary"
                        >
                            <ShieldCheckIcon class="h-5 w-5" />
                            Roles &amp; Permissions
                        </Link>
                        <button
                            v-if="can('create_user')"
                            @click="openUserModal()"
                            class="ui-btn-primary"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Add User
                        </button>
                    </template>
                </PageHeader>

                <!-- Users Tab Content -->
                <div v-if="selectedTab === 'users'" class="space-y-6">
                    <!-- Search and Filters -->
                    <Card>
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <!-- Search -->
                            <div class="relative flex-1 lg:max-w-lg">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-content-faint" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search users by name, email, or department..."
                                    class="ui-input pl-10"
                                />
                            </div>

                            <!-- Filters -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <!-- Role Filter -->
                                <select
                                    v-model="selectedRole"
                                    class="ui-input sm:w-auto"
                                    aria-label="Filter by role"
                                >
                                    <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                                        {{ role.label }}
                                    </option>
                                </select>

                                <!-- Status Filter -->
                                <select
                                    v-model="selectedStatus"
                                    class="ui-input sm:w-auto"
                                    aria-label="Filter by status"
                                >
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </Card>

                    <!-- Bulk Actions -->
                    <div
                        v-if="selectedUsers.size > 0"
                        class="flex flex-col gap-3 rounded-card border border-primary bg-primary-soft p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span class="text-sm font-medium text-primary">
                            {{ selectedUsers.size }} user(s) selected
                        </span>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                @click="bulkUpdateStatus('active')"
                                class="ui-btn-secondary"
                            >
                                <CheckCircleIcon class="h-4 w-4 text-success-fg" />
                                Activate
                            </button>
                            <button
                                @click="bulkUpdateStatus('inactive')"
                                class="ui-btn-secondary"
                            >
                                <XCircleIcon class="h-4 w-4 text-danger-fg" />
                                Deactivate
                            </button>
                            <select
                                @change="bulkUpdateRole($event.target.value); $event.target.value = ''"
                                class="ui-input w-auto py-2 text-sm"
                                aria-label="Change role for selected users"
                            >
                                <option value="">Change Role</option>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <Card padding="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-line">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            <input
                                                type="checkbox"
                                                :checked="selectedUsers.size === filteredUsers.length && filteredUsers.length > 0"
                                                @change="selectAllUsers"
                                                class="rounded border-line text-primary focus:ring-primary"
                                                aria-label="Select all users"
                                            />
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            User
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            Role &amp; Department
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            Last Login
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-content-faint">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="user in filteredUsers"
                                        :key="user.id"
                                        class="border-b border-line transition-colors hover:bg-bg"
                                    >
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :checked="selectedUsers.has(user.id)"
                                                @change="toggleUserSelection(user.id)"
                                                class="rounded border-line text-primary focus:ring-primary"
                                                :aria-label="`Select ${user.name}`"
                                            />
                                        </td>
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-semibold text-primary"
                                                >
                                                    {{ getInitial(user.name) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-content">
                                                        {{ user.name }}
                                                    </div>
                                                    <div class="text-content-muted">
                                                        {{ user.email }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">
                                            <Badge :variant="getRoleVariant(user.role)">
                                                <component :is="getRoleIcon(user.role)" class="h-3.5 w-3.5" />
                                                {{ user.role }}
                                            </Badge>
                                            <div class="mt-1 text-content-muted">
                                                {{ user.department }}
                                            </div>
                                            <div v-if="user.year" class="text-xs text-content-faint">
                                                {{ user.year }}, {{ user.semester }}
                                            </div>
                                            <div v-if="user.designation" class="text-xs text-content-faint">
                                                {{ user.designation }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">
                                            <Badge :variant="getStatusVariant(user.status)" dot>
                                                {{ user.status }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3 align-middle whitespace-nowrap text-content-muted">
                                            {{ user.lastLogin || 'Never' }}
                                        </td>
                                        <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button
                                                    v-if="can('update_user')"
                                                    @click="openUserModal(user)"
                                                    class="ui-btn-ghost p-2"
                                                    aria-label="Edit user"
                                                >
                                                    <PencilIcon class="h-4 w-4" />
                                                </button>
                                                <button
                                                    v-if="can('update_user') && !user.isProtected"
                                                    @click="deleteUser(user.id)"
                                                    class="ui-btn-ghost p-2 text-danger-fg hover:text-danger-fg"
                                                    aria-label="Deactivate user"
                                                >
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <EmptyState
                            v-if="filteredUsers.length === 0"
                            title="No users found"
                            description="Try adjusting your search or filters."
                            :icon="UsersIcon"
                        >
                            <button
                                @click="clearFilters"
                                class="ui-btn-secondary"
                            >
                                Clear Filters
                            </button>
                        </EmptyState>

                        <Pagination :paginator="props.users" label="users" />
                    </Card>
                </div>
            </div>

            <!-- User Modal -->
            <Teleport to="body">
            <div v-if="showUserModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="showUserModal = false"></div>

                    <div class="relative w-full max-w-lg ui-card">
                        <div class="flex items-center justify-between border-b border-line px-6 py-4">
                            <h3 class="ui-card-title">
                                {{ editingUser?.id ? 'Edit User' : 'Add New User' }}
                            </h3>
                            <button
                                @click="showUserModal = false"
                                class="ui-btn-ghost p-2"
                                aria-label="Close"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="space-y-4 p-6">
                            <div
                                v-if="editingUser?.isProtected"
                                class="rounded-card border border-warning bg-warning-soft px-4 py-3 text-sm text-warning-fg"
                            >
                                This is the protected System Administrator account. Its role and
                                active status cannot be changed.
                            </div>

                            <div>
                                <label class="ui-label">Full Name *</label>
                                <input
                                    v-model="editingUser.name"
                                    type="text"
                                    class="ui-input"
                                    placeholder="Enter full name"
                                />
                                <p v-if="formErrors.name" class="mt-1 text-sm text-danger-fg">{{ formErrors.name }}</p>
                            </div>

                            <div>
                                <label class="ui-label">Email Address *</label>
                                <input
                                    v-model="editingUser.email"
                                    type="email"
                                    class="ui-input"
                                    placeholder="Enter email address"
                                />
                                <p v-if="formErrors.email" class="mt-1 text-sm text-danger-fg">{{ formErrors.email }}</p>
                            </div>

                            <div v-if="!editingUser?.id">
                                <label class="ui-label">Password *</label>
                                <input
                                    v-model="editingUser.password"
                                    type="password"
                                    class="ui-input"
                                    placeholder="At least 8 characters"
                                    autocomplete="new-password"
                                />
                                <p v-if="formErrors.password" class="mt-1 text-sm text-danger-fg">{{ formErrors.password }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div v-if="!editingUser?.id">
                                    <label class="ui-label">Role *</label>
                                    <select
                                        v-model="editingUser.role"
                                        class="ui-input"
                                    >
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                    <p v-if="formErrors.role" class="mt-1 text-sm text-danger-fg">{{ formErrors.role }}</p>
                                </div>

                                <div v-if="editingUser?.id">
                                    <label class="ui-label">Status</label>
                                    <select
                                        v-model="editingUser.status"
                                        class="ui-input disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="editingUser?.isProtected"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <p v-if="editingUser?.isProtected" class="mt-1 text-xs text-content-faint">
                                        The System Administrator is always active.
                                    </p>
                                </div>

                                <div>
                                    <label class="ui-label">Department</label>
                                    <select
                                        v-model="editingUser.department_id"
                                        class="ui-input"
                                    >
                                        <option value="">No department</option>
                                        <option
                                            v-for="department in departments"
                                            :key="department.id"
                                            :value="department.id"
                                        >
                                            {{ department.name }}
                                        </option>
                                    </select>
                                    <p v-if="formErrors.department_id" class="mt-1 text-sm text-danger-fg">{{ formErrors.department_id }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="ui-label">Semester</label>
                                <input
                                    v-model="editingUser.semester"
                                    type="text"
                                    class="ui-input"
                                    placeholder="e.g. Fall 2026"
                                />
                                <p v-if="formErrors.semester" class="mt-1 text-sm text-danger-fg">{{ formErrors.semester }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                            <button
                                @click="showUserModal = false"
                                class="ui-btn-ghost"
                            >
                                Cancel
                            </button>
                            <button
                                @click="saveUser"
                                class="ui-btn-primary"
                            >
                                {{ editingUser?.id ? 'Update User' : 'Create User' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            </Teleport>
        </AppLayout>
    </div>
</template>

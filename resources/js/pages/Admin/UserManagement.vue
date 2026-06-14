<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
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
    ClockIcon
} from '@heroicons/vue/24/outline';

// Component state
const { can } = usePermissions();

const selectedTab = ref('users');
const selectedRole = ref('all');
const selectedStatus = ref('all');
const searchQuery = ref('');
const selectedUsers = ref(new Set());
const showUserModal = ref(false);
const editingUser = ref(null);

// Props from the backend
const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    roles: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) }
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
        semester: user.semester,
        status: user.status,
        lastLogin: user.lastLogin,
        joinedDate: user.joinedDate,
        permissions: user.permissions
    }))
);

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
    { value: 'inactive', label: 'Inactive Users' },
    { value: 'pending', label: 'Pending Approval' }
];

// Computed properties
const filteredUsers = computed(() => {
    let filtered = users.value;

    // Filter by role
    if (selectedRole.value !== 'all') {
        filtered = filtered.filter(user => user.role === selectedRole.value);
    }

    // Filter by status
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(user => user.status === selectedStatus.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            user.department.toLowerCase().includes(query)
        );
    }

    return filtered;
});

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

const getTimeAgo = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
};

const getRoleColor = (role) => {
    const colors = {
        student: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        faculty: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        admin: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return colors[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

const getStatusColor = (status) => {
    const colors = {
        active: 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
        inactive: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
        pending: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
    };
    return colors[status] || colors.pending;
};

const getRoleIcon = (role) => {
    const icons = {
        student: AcademicCapIcon,
        faculty: UserCircleIcon,
        admin: ShieldCheckIcon
    };
    return icons[role] || UserCircleIcon;
};

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
    if (editing.id) {
        // Update existing user
        router.patch(route('admin.users.update', editing.id), {
            name: editing.name,
            email: editing.email,
            department_id: editing.department_id || null,
            semester: editing.semester || null
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showUserModal.value = false;
                editingUser.value = null;
            }
        });
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
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showUserModal.value = false;
                editingUser.value = null;
            }
        });
    }
};

const deleteUser = (userId) => {
    if (confirm('Are you sure you want to deactivate this user?')) {
        router.patch(route('admin.users.toggle-active', userId), {}, {
            preserveScroll: true
        });
    }
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
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-900 dark:via-indigo-900 dark:to-blue-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">
                                    👥 User Management
                                </h1>
                                <p class="text-xl text-white/90">
                                    Manage users, roles, and permissions
                                </p>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Quick Stats -->
                                <div class="flex gap-4">
                                    <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center">
                                        <div class="text-2xl font-bold">{{ userStats.total }}</div>
                                        <div class="text-xs text-white/80">Total Users</div>
                                    </div>
                                    <div class="bg-white/20 backdrop-blur-lg rounded-xl px-4 py-3 text-white text-center">
                                        <div class="text-2xl font-bold">{{ userStats.active }}</div>
                                        <div class="text-xs text-white/80">Active</div>
                                    </div>
                                </div>

                                <Link
                                    href="/admin/dashboard"
                                    class="bg-white/20 backdrop-blur-lg border border-white/20 rounded-xl text-white px-6 py-3 font-medium hover:bg-white/30 transition-all text-center"
                                >
                                    ← Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">
                    <!-- Tab Navigation -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg mb-8 overflow-hidden">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 font-medium text-sm bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-b-2 border-purple-600">
                                <UsersIcon class="w-5 h-5 inline mr-2" />
                                Users ({{ userStats.total }})
                            </div>
                            <Link
                                v-if="can('manage_permissions')"
                                :href="route('admin.roles')"
                                class="mr-4 inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-200"
                            >
                                <ShieldCheckIcon class="w-5 h-5 mr-2" />
                                Manage Roles &amp; Permissions →
                            </Link>
                        </div>

                        <!-- Users Tab Content -->
                        <div v-if="selectedTab === 'users'" class="p-6">
                            <!-- Search and Filters -->
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                                <!-- Search -->
                                <div class="relative flex-1 max-w-lg">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search users by name, email, or department..."
                                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>

                                <!-- Filters and Actions -->
                                <div class="flex items-center gap-4">
                                    <!-- Role Filter -->
                                    <select
                                        v-model="selectedRole"
                                        class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                                            {{ role.label }}
                                        </option>
                                    </select>

                                    <!-- Status Filter -->
                                    <select
                                        v-model="selectedStatus"
                                        class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </option>
                                    </select>

                                    <!-- Add User Button -->
                                    <button
                                        v-if="can('create_user')"
                                        @click="openUserModal()"
                                        class="inline-flex items-center px-4 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
                                    >
                                        <PlusIcon class="w-5 h-5 mr-2" />
                                        Add User
                                    </button>
                                </div>
                            </div>

                            <!-- Bulk Actions -->
                            <div v-if="selectedUsers.size > 0" class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                                        {{ selectedUsers.size }} user(s) selected
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="bulkUpdateStatus('active')"
                                            class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                                        >
                                            Activate
                                        </button>
                                        <button
                                            @click="bulkUpdateStatus('inactive')"
                                            class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
                                        >
                                            Deactivate
                                        </button>
                                        <select
                                            @change="bulkUpdateRole($event.target.value); $event.target.value = ''"
                                            class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                        >
                                            <option value="">Change Role</option>
                                            <option value="student">Student</option>
                                            <option value="faculty">Faculty</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Users Table -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    <input
                                                        type="checkbox"
                                                        :checked="selectedUsers.size === filteredUsers.length && filteredUsers.length > 0"
                                                        @change="selectAllUsers"
                                                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                    />
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    User
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Role & Department
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Last Login
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr
                                                v-for="user in filteredUsers"
                                                :key="user.id"
                                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input
                                                        type="checkbox"
                                                        :checked="selectedUsers.has(user.id)"
                                                        @change="toggleUserSelection(user.id)"
                                                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                    />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <img
                                                            :src="user.avatar"
                                                            :alt="user.name"
                                                            class="w-10 h-10 rounded-full mr-4"
                                                        />
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                                {{ user.name }}
                                                            </div>
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ user.email }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getRoleColor(user.role)}`">
                                                            <component :is="getRoleIcon(user.role)" class="w-3 h-3 inline mr-1" />
                                                            {{ user.role }}
                                                        </span>
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ user.department }}
                                                    </div>
                                                    <div v-if="user.year" class="text-xs text-gray-400 dark:text-gray-500">
                                                        {{ user.year }}, {{ user.semester }}
                                                    </div>
                                                    <div v-if="user.designation" class="text-xs text-gray-400 dark:text-gray-500">
                                                        {{ user.designation }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(user.status)}`">
                                                        <component
                                                            :is="user.status === 'active' ? CheckCircleIcon : user.status === 'inactive' ? XCircleIcon : ClockIcon"
                                                            class="w-3 h-3 inline mr-1"
                                                        />
                                                        {{ user.status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ getTimeAgo(user.lastLogin) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            v-if="can('update_user')"
                                                            @click="openUserModal(user)"
                                                            class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                        >
                                                            <PencilIcon class="w-4 h-4" />
                                                        </button>
                                                        <button
                                                            v-if="can('update_user')"
                                                            @click="deleteUser(user.id)"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                        >
                                                            <TrashIcon class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Empty State -->
                                <div v-if="filteredUsers.length === 0" class="text-center py-12">
                                    <UsersIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No users found</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">Try adjusting your search or filters.</p>
                                    <button
                                        @click="searchQuery = ''; selectedRole = 'all'; selectedStatus = 'all'"
                                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- User Modal -->
            <div v-if="showUserModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showUserModal = false"></div>

                    <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                                {{ editingUser?.id ? 'Edit User' : 'Add New User' }}
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Full Name *
                                    </label>
                                    <input
                                        v-model="editingUser.name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        placeholder="Enter full name"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Email Address *
                                    </label>
                                    <input
                                        v-model="editingUser.email"
                                        type="email"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        placeholder="Enter email address"
                                    />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Role *
                                        </label>
                                        <select
                                            v-model="editingUser.role"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="student">Student</option>
                                            <option value="faculty">Faculty</option>
                                            <option value="admin">Administrator</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Status
                                        </label>
                                        <select
                                            v-model="editingUser.status"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Department
                                    </label>
                                    <input
                                        v-model="editingUser.department"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        placeholder="Enter department"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Phone Number
                                    </label>
                                    <input
                                        v-model="editingUser.phone"
                                        type="tel"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        placeholder="Enter phone number"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 mt-6">
                                <button
                                    @click="showUserModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="saveUser"
                                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                >
                                    {{ editingUser?.id ? 'Update User' : 'Create User' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
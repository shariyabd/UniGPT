<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UsersIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    ShieldCheckIcon,
    UserCircleIcon,
    AcademicCapIcon,
    CogIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    EnvelopeIcon,
    PhoneIcon,
    CalendarIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';

// Component state
const selectedTab = ref('users');
const selectedRole = ref('all');
const selectedStatus = ref('all');
const searchQuery = ref('');
const selectedUsers = ref(new Set());
const showUserModal = ref(false);
const showRoleModal = ref(false);
const showPermissionModal = ref(false);
const editingUser = ref(null);
const editingRole = ref(null);

// Mock users data
const users = ref([
    {
        id: 1,
        name: 'Alex Johnson',
        email: 'alex.johnson@university.edu',
        phone: '+1 (555) 123-4567',
        avatar: 'https://ui-avatars.com/api/?name=Alex+Johnson&background=3b82f6&color=fff',
        role: 'student',
        department: 'Computer Science Engineering',
        year: '3rd Year',
        semester: '5th Semester',
        status: 'active',
        lastLogin: '2024-01-15T14:30:00',
        joinedDate: '2021-08-15T09:00:00',
        totalQueries: 234,
        permissions: ['chat.use', 'documents.view', 'profile.edit']
    },
    {
        id: 2,
        name: 'Dr. Sarah Smith',
        email: 'sarah.smith@university.edu',
        phone: '+1 (555) 234-5678',
        avatar: 'https://ui-avatars.com/api/?name=Sarah+Smith&background=10b981&color=fff',
        role: 'faculty',
        department: 'Computer Science Engineering',
        designation: 'Professor & Head of Department',
        status: 'active',
        lastLogin: '2024-01-15T16:45:00',
        joinedDate: '2015-07-01T09:00:00',
        totalQueries: 89,
        documentsUploaded: 23,
        permissions: ['chat.use', 'documents.view', 'documents.upload', 'students.view', 'courses.manage']
    },
    {
        id: 3,
        name: 'Prof. Michael Johnson',
        email: 'michael.j@university.edu',
        phone: '+1 (555) 345-6789',
        avatar: 'https://ui-avatars.com/api/?name=Michael+Johnson&background=8b5cf6&color=fff',
        role: 'faculty',
        department: 'Electrical Engineering',
        designation: 'Associate Professor',
        status: 'active',
        lastLogin: '2024-01-14T11:20:00',
        joinedDate: '2018-08-15T09:00:00',
        totalQueries: 67,
        documentsUploaded: 18,
        permissions: ['chat.use', 'documents.view', 'documents.upload', 'students.view']
    },
    {
        id: 4,
        name: 'Emily Chen',
        email: 'emily.chen@student.university.edu',
        phone: '+1 (555) 456-7890',
        avatar: 'https://ui-avatars.com/api/?name=Emily+Chen&background=f59e0b&color=fff',
        role: 'student',
        department: 'Electrical Engineering',
        year: '2nd Year',
        semester: '3rd Semester',
        status: 'active',
        lastLogin: '2024-01-15T13:15:00',
        joinedDate: '2022-08-20T09:00:00',
        totalQueries: 145,
        permissions: ['chat.use', 'documents.view', 'profile.edit']
    },
    {
        id: 5,
        name: 'Admin User',
        email: 'admin@university.edu',
        phone: '+1 (555) 567-8901',
        avatar: 'https://ui-avatars.com/api/?name=Admin+User&background=ef4444&color=fff',
        role: 'admin',
        department: 'Administration',
        designation: 'System Administrator',
        status: 'active',
        lastLogin: '2024-01-15T17:00:00',
        joinedDate: '2020-01-01T09:00:00',
        totalQueries: 45,
        permissions: ['*'] // All permissions
    },
    {
        id: 6,
        name: 'John Doe',
        email: 'john.doe@student.university.edu',
        phone: '+1 (555) 678-9012',
        avatar: 'https://ui-avatars.com/api/?name=John+Doe&background=6b7280&color=fff',
        role: 'student',
        department: 'Mechanical Engineering',
        year: '4th Year',
        semester: '7th Semester',
        status: 'inactive',
        lastLogin: '2024-01-10T09:30:00',
        joinedDate: '2020-08-15T09:00:00',
        totalQueries: 89,
        permissions: ['chat.use', 'documents.view']
    }
]);

// Roles configuration
const roles = ref([
    {
        id: 'student',
        name: 'Student',
        description: 'University students with access to academic resources',
        color: 'blue',
        icon: AcademicCapIcon,
        userCount: 3,
        defaultPermissions: [
            'chat.use',
            'documents.view',
            'profile.edit',
            'roadmap.view',
            'exams.view'
        ]
    },
    {
        id: 'faculty',
        name: 'Faculty',
        description: 'Teaching staff with course management capabilities',
        color: 'green',
        icon: UserCircleIcon,
        userCount: 2,
        defaultPermissions: [
            'chat.use',
            'documents.view',
            'documents.upload',
            'documents.approve',
            'students.view',
            'courses.manage',
            'analytics.view'
        ]
    },
    {
        id: 'admin',
        name: 'Administrator',
        description: 'System administrators with full access',
        color: 'red',
        icon: ShieldCheckIcon,
        userCount: 1,
        defaultPermissions: ['*'] // All permissions
    }
]);

// Available permissions
const availablePermissions = ref([
    { id: 'chat.use', name: 'Use Chat System', category: 'Chat' },
    { id: 'chat.history', name: 'View Chat History', category: 'Chat' },
    { id: 'documents.view', name: 'View Documents', category: 'Documents' },
    { id: 'documents.upload', name: 'Upload Documents', category: 'Documents' },
    { id: 'documents.approve', name: 'Approve Documents', category: 'Documents' },
    { id: 'documents.delete', name: 'Delete Documents', category: 'Documents' },
    { id: 'students.view', name: 'View Students', category: 'Users' },
    { id: 'students.manage', name: 'Manage Students', category: 'Users' },
    { id: 'faculty.view', name: 'View Faculty', category: 'Users' },
    { id: 'faculty.manage', name: 'Manage Faculty', category: 'Users' },
    { id: 'courses.view', name: 'View Courses', category: 'Academic' },
    { id: 'courses.manage', name: 'Manage Courses', category: 'Academic' },
    { id: 'roadmap.view', name: 'View Roadmap', category: 'Academic' },
    { id: 'roadmap.edit', name: 'Edit Roadmap', category: 'Academic' },
    { id: 'exams.view', name: 'View Exams', category: 'Academic' },
    { id: 'exams.manage', name: 'Manage Exams', category: 'Academic' },
    { id: 'analytics.view', name: 'View Analytics', category: 'System' },
    { id: 'settings.view', name: 'View Settings', category: 'System' },
    { id: 'settings.manage', name: 'Manage Settings', category: 'System' },
    { id: 'profile.edit', name: 'Edit Profile', category: 'Profile' }
]);

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

const userStats = computed(() => {
    const total = users.value.length;
    const active = users.value.filter(u => u.status === 'active').length;
    const students = users.value.filter(u => u.role === 'student').length;
    const faculty = users.value.filter(u => u.role === 'faculty').length;
    const admins = users.value.filter(u => u.role === 'admin').length;

    return { total, active, students, faculty, admins };
});

const permissionsByCategory = computed(() => {
    const categories = {};
    availablePermissions.value.forEach(permission => {
        if (!categories[permission.category]) {
            categories[permission.category] = [];
        }
        categories[permission.category].push(permission);
    });
    return categories;
});

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
    editingUser.value = user ? { ...user } : {
        name: '',
        email: '',
        phone: '',
        role: 'student',
        department: '',
        status: 'active',
        permissions: []
    };
    showUserModal.value = true;
};

const saveUser = () => {
    if (editingUser.value.id) {
        // Update existing user
        const index = users.value.findIndex(u => u.id === editingUser.value.id);
        if (index !== -1) {
            users.value[index] = { ...editingUser.value };
        }
    } else {
        // Create new user
        editingUser.value.id = Date.now();
        editingUser.value.avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(editingUser.value.name)}&background=3b82f6&color=fff`;
        editingUser.value.joinedDate = new Date().toISOString();
        editingUser.value.lastLogin = new Date().toISOString();
        editingUser.value.totalQueries = 0;
        users.value.push({ ...editingUser.value });
    }

    showUserModal.value = false;
    editingUser.value = null;
};

const deleteUser = (userId) => {
    if (confirm('Are you sure you want to delete this user?')) {
        users.value = users.value.filter(u => u.id !== userId);
    }
};

const bulkUpdateStatus = (status) => {
    selectedUsers.value.forEach(userId => {
        const user = users.value.find(u => u.id === userId);
        if (user) {
            user.status = status;
        }
    });
    selectedUsers.value.clear();
    alert(`Updated ${selectedUsers.value.size} users to ${status}`);
};

const bulkUpdateRole = (role) => {
    selectedUsers.value.forEach(userId => {
        const user = users.value.find(u => u.id === userId);
        if (user) {
            user.role = role;
            // Update permissions based on role
            const roleData = roles.value.find(r => r.id === role);
            if (roleData) {
                user.permissions = [...roleData.defaultPermissions];
            }
        }
    });
    selectedUsers.value.clear();
    alert(`Updated ${selectedUsers.value.size} users to ${role} role`);
};

const openRoleModal = (role = null) => {
    editingRole.value = role ? { ...role } : {
        id: '',
        name: '',
        description: '',
        color: 'blue',
        defaultPermissions: []
    };
    showRoleModal.value = true;
};

const saveRole = () => {
    if (editingRole.value.id) {
        // Update existing role
        const index = roles.value.findIndex(r => r.id === editingRole.value.id);
        if (index !== -1) {
            roles.value[index] = { ...editingRole.value };
        }
    } else {
        // Create new role
        editingRole.value.id = editingRole.value.name.toLowerCase().replace(/\s+/g, '_');
        editingRole.value.userCount = 0;
        roles.value.push({ ...editingRole.value });
    }

    showRoleModal.value = false;
    editingRole.value = null;
};

const deleteRole = (roleId) => {
    const usersWithRole = users.value.filter(u => u.role === roleId);
    if (usersWithRole.length > 0) {
        alert(`Cannot delete role. ${usersWithRole.length} users are assigned to this role.`);
        return;
    }

    if (confirm('Are you sure you want to delete this role?')) {
        roles.value = roles.value.filter(r => r.id !== roleId);
    }
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
                        <div class="flex border-b border-gray-200 dark:border-gray-700">
                            <button
                                @click="selectedTab = 'users'"
                                :class="`px-6 py-4 font-medium text-sm transition-colors ${
                                    selectedTab === 'users'
                                        ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-b-2 border-purple-600'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                                }`"
                            >
                                <UsersIcon class="w-5 h-5 inline mr-2" />
                                Users ({{ userStats.total }})
                            </button>
                            <button
                                @click="selectedTab = 'roles'"
                                :class="`px-6 py-4 font-medium text-sm transition-colors ${
                                    selectedTab === 'roles'
                                        ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-b-2 border-purple-600'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                                }`"
                            >
                                <ShieldCheckIcon class="w-5 h-5 inline mr-2" />
                                Roles & Permissions ({{ roles.length }})
                            </button>
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
                                                            @click="openUserModal(user)"
                                                            class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                        >
                                                            <PencilIcon class="w-4 h-4" />
                                                        </button>
                                                        <button
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

                        <!-- Roles Tab Content -->
                        <div v-if="selectedTab === 'roles'" class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Roles & Permissions Management
                                </h3>
                                <button
                                    @click="openRoleModal()"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
                                >
                                    <PlusIcon class="w-5 h-5 mr-2" />
                                    Create Role
                                </button>
                            </div>

                            <!-- Roles Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                <div
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="bg-white dark:bg-gray-700 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300"
                                >
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div :class="`w-12 h-12 rounded-xl flex items-center justify-center ${
                                                role.color === 'blue' ? 'bg-blue-100 dark:bg-blue-900/30' :
                                                role.color === 'green' ? 'bg-green-100 dark:bg-green-900/30' :
                                                role.color === 'red' ? 'bg-red-100 dark:bg-red-900/30' :
                                                'bg-gray-100 dark:bg-gray-900/30'
                                            }`">
                                                <component
                                                    :is="role.icon"
                                                    :class="`w-6 h-6 ${
                                                        role.color === 'blue' ? 'text-blue-600 dark:text-blue-400' :
                                                        role.color === 'green' ? 'text-green-600 dark:text-green-400' :
                                                        role.color === 'red' ? 'text-red-600 dark:text-red-400' :
                                                        'text-gray-600 dark:text-gray-400'
                                                    }`"
                                                />
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 dark:text-white">{{ role.name }}</h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ role.userCount }} users</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="openRoleModal(role)"
                                                class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                            >
                                                <PencilIcon class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="deleteRole(role.id)"
                                                v-if="role.id !== 'admin'"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">
                                        {{ role.description }}
                                    </p>

                                    <div class="space-y-2">
                                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Permissions:</h5>
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-if="role.defaultPermissions.includes('*')"
                                                class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded text-xs font-medium"
                                            >
                                                All Permissions
                                            </span>
                                            <template v-else>
                                                <span
                                                    v-for="permission in role.defaultPermissions.slice(0, 3)"
                                                    :key="permission"
                                                    class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 rounded text-xs font-medium"
                                                >
                                                    {{ permission }}
                                                </span>
                                                <span
                                                    v-if="role.defaultPermissions.length > 3"
                                                    class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded text-xs font-medium"
                                                >
                                                    +{{ role.defaultPermissions.length - 3 }} more
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions Matrix -->
                            <div class="mt-8 bg-white dark:bg-gray-700 rounded-2xl shadow-lg p-6">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Permission Matrix</h4>

                                <div class="space-y-6">
                                    <div v-for="(permissions, category) in permissionsByCategory" :key="category">
                                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ category }}</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <div
                                                v-for="permission in permissions"
                                                :key="permission.id"
                                                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                            >
                                                <div class="font-medium text-gray-900 dark:text-white text-sm">{{ permission.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ permission.id }}</div>

                                                <!-- Role checkboxes -->
                                                <div class="flex items-center gap-3 mt-2">
                                                    <label
                                                        v-for="role in roles"
                                                        :key="role.id"
                                                        class="flex items-center gap-1 text-xs"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            :checked="role.defaultPermissions.includes('*') || role.defaultPermissions.includes(permission.id)"
                                                            :disabled="role.defaultPermissions.includes('*')"
                                                            class="rounded text-purple-600 focus:ring-purple-500"
                                                        />
                                                        <span :class="`${
                                                            role.color === 'blue' ? 'text-blue-600 dark:text-blue-400' :
                                                            role.color === 'green' ? 'text-green-600 dark:text-green-400' :
                                                            role.color === 'red' ? 'text-red-600 dark:text-red-400' :
                                                            'text-gray-600 dark:text-gray-400'
                                                        }`">
                                                            {{ role.name }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

            <!-- Role Modal -->
            <div v-if="showRoleModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showRoleModal = false"></div>

                    <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                                {{ editingRole?.id && roles.some(r => r.id === editingRole.id) ? 'Edit Role' : 'Create New Role' }}
                            </h3>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Role Name *
                                        </label>
                                        <input
                                            v-model="editingRole.name"
                                            type="text"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                            placeholder="Enter role name"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Color Theme
                                        </label>
                                        <select
                                            v-model="editingRole.color"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        >
                                            <option value="blue">Blue</option>
                                            <option value="green">Green</option>
                                            <option value="red">Red</option>
                                            <option value="purple">Purple</option>
                                            <option value="yellow">Yellow</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="editingRole.description"
                                        rows="3"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none"
                                        placeholder="Enter role description"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Permissions
                                    </label>
                                    <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-xl p-4">
                                        <div class="space-y-4">
                                            <div v-for="(permissions, category) in permissionsByCategory" :key="category">
                                                <h6 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ category }}</h6>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 ml-4">
                                                    <label
                                                        v-for="permission in permissions"
                                                        :key="permission.id"
                                                        class="flex items-center gap-2 text-sm"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            :value="permission.id"
                                                            v-model="editingRole.defaultPermissions"
                                                            class="rounded text-purple-600 focus:ring-purple-500"
                                                        />
                                                        <span class="text-gray-700 dark:text-gray-300">{{ permission.name }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 mt-6">
                                <button
                                    @click="showRoleModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="saveRole"
                                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                >
                                    {{ editingRole?.id && roles.some(r => r.id === editingRole.id) ? 'Update Role' : 'Create Role' }}
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
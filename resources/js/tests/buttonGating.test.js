import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const pageState = vi.hoisted(() => ({ user: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: pageState.user } } }),
    Link: { name: 'Link', template: '<a><slot /></a>' },
    router: { patch: vi.fn(), post: vi.fn() },
    Head: { template: '<div><slot /></div>' },
}));

import UserManagement from '@/pages/Admin/UserManagement.vue';

function mountAs(permissions) {
    pageState.user = { name: 'Ada', primary_role: 'admin', roles: ['admin'], permissions };

    return mount(UserManagement, {
        props: {
            users: { data: [] },
            roles: [],
            departments: [],
            stats: { total_users: 0, active_users: 0 },
        },
        global: {
            mocks: { route: (name, id) => (id ? `/${name}/${id}` : `/${name}`) },
            stubs: { AppLayout: { template: '<div><slot /></div>' } },
        },
    });
}

describe('UserManagement button gating', () => {
    it('shows "Add User" when the admin holds create_user', () => {
        expect(mountAs(['view_users', 'create_user']).text()).toContain('Add User');
    });

    it('hides "Add User" without create_user', () => {
        expect(mountAs(['view_users']).text()).not.toContain('Add User');
    });
});

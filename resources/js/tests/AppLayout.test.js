import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const pageState = vi.hoisted(() => ({ user: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: pageState.user } } }),
    Link: { name: 'Link', template: '<a><slot /></a>' },
}));

import AppLayout from '@/Layouts/AppLayout.vue';

function mountAs(user) {
    pageState.user = user;

    return mount(AppLayout, {
        global: {
            mocks: {
                $page: { props: { auth: { user } } },
                route: (name) => (name === undefined ? { current: () => false } : `/${name}`),
            },
            stubs: {
                NavLink: { template: '<a class="navlink"><slot /></a>' },
            },
        },
    });
}

const student = {
    name: 'Sam Student',
    primary_role: 'student',
    roles: ['student'],
    permissions: ['use_ai_chat', 'view_chat_history', 'view_courses', 'view_documents'],
};

const faculty = {
    name: 'Faye Faculty',
    primary_role: 'faculty',
    roles: ['faculty'],
    permissions: ['view_courses', 'use_ai_chat', 'grade_assignment'],
};

const adminBase = {
    name: 'Ada Admin',
    primary_role: 'admin',
    roles: ['admin'],
    permissions: ['view_users', 'view_documents', 'approve_document', 'view_all_analytics', 'configure_ai', 'manage_system'],
};

describe('AppLayout navigation', () => {
    it('renders the student nav for a student', () => {
        const text = mountAs(student).find('nav').text();
        expect(text).toContain('Dashboard');
        expect(text).toContain('Chat');
        expect(text).toContain('Roadmap');
        expect(text).toContain('Documents');
        // Faculty/admin-only items must not leak in
        expect(text).not.toContain('Grading');
        expect(text).not.toContain('Users');
    });

    it('renders the faculty nav for faculty', () => {
        const text = mountAs(faculty).find('nav').text();
        expect(text).toContain('Courses');
        expect(text).toContain('Grading');
        expect(text).not.toContain('Roadmap');
        expect(text).not.toContain('Users');
    });

    it('hides a nav item when its permission is absent', () => {
        // admin WITHOUT manage_permissions → no "Roles" link
        const text = mountAs(adminBase).find('nav').text();
        expect(text).toContain('Users');
        expect(text).not.toContain('Roles');
    });

    it('shows the Roles editor link when manage_permissions is held', () => {
        const text = mountAs({ ...adminBase, permissions: [...adminBase.permissions, 'manage_permissions'] })
            .find('nav').text();
        expect(text).toContain('Roles');
    });
});

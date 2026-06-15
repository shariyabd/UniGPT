import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: {
                user: {
                    permissions: ['use_ai_chat', 'view_documents', 'view_courses'],
                    roles: ['student'],
                    primary_role: 'student',
                },
            },
        },
    }),
}));

import { usePermissions } from '@/composables/usePermissions';

describe('usePermissions', () => {
    it('can() reports a single permission', () => {
        const { can } = usePermissions();
        expect(can('use_ai_chat')).toBe(true);
        expect(can('approve_document')).toBe(false);
    });

    it('canAny() is true when at least one permission is held', () => {
        const { canAny } = usePermissions();
        expect(canAny(['approve_document', 'view_documents'])).toBe(true);
        expect(canAny(['approve_document', 'manage_system'])).toBe(false);
    });

    it('hasRole() matches the user roles', () => {
        const { hasRole } = usePermissions();
        expect(hasRole('student')).toBe(true);
        expect(hasRole('admin')).toBe(false);
    });

    it('exposes primaryRole and the permission list', () => {
        const { primaryRole, permissions } = usePermissions();
        expect(primaryRole.value).toBe('student');
        expect(permissions.value).toContain('view_courses');
    });
});

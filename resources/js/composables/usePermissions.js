import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Reads the authenticated user shared by HandleInertiaRequests and exposes
 * permission/role helpers so the UI can hide actions the backend would reject.
 *
 * Mirrors the server-side matrix (PermissionMiddleware / RoleMiddleware) so
 * what a user sees matches what they can actually do.
 */
export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);
    const permissions = computed(() => user.value?.permissions ?? []);
    const roles = computed(() => user.value?.roles ?? []);

    /** Whether the user holds a single permission. */
    const can = (permission) => permissions.value.includes(permission);

    /** Whether the user holds at least one of the given permissions. */
    const canAny = (list = []) => list.some((permission) => can(permission));

    /** Whether the user has a given role slug. */
    const hasRole = (role) => roles.value.includes(role);

    const primaryRole = computed(() => user.value?.primary_role ?? null);

    return { user, permissions, roles, primaryRole, can, canAny, hasRole };
}

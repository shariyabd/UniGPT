import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const patchMock = vi.hoisted(() => vi.fn((url, data, opts) => {
    opts?.onSuccess?.();
    opts?.onFinish?.();
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    router: { patch: patchMock },
}));

import RolePermissions from '@/pages/Admin/RolePermissions.vue';

const roles = [
    { id: 1, name: 'Student', slug: 'student', locked: false, permissionIds: [10] },
    { id: 3, name: 'Admin', slug: 'admin', locked: true, permissionIds: [10, 20] },
];

const permissionsByCategory = {
    Documents: [
        { id: 10, slug: 'view_documents', name: 'View Documents' },
        { id: 20, slug: 'approve_document', name: 'Approve Documents' },
    ],
};

function mountMatrix() {
    return mount(RolePermissions, {
        props: { roles, permissionsByCategory, totalPermissions: 2 },
        global: {
            mocks: { route: (name, id) => `/${name}/${id}` },
            stubs: { AppLayout: { template: '<div><slot /></div>' } },
        },
    });
}

describe('RolePermissions matrix editor', () => {
    it('locks (disables) the admin column', () => {
        const checkboxes = mountMatrix().findAll('tbody input[type="checkbox"]');
        // order: [p10/student, p10/admin, p20/student, p20/admin]
        expect(checkboxes[1].element.disabled).toBe(true); // admin
        expect(checkboxes[3].element.disabled).toBe(true); // admin
        expect(checkboxes[0].element.disabled).toBe(false); // student
    });

    it('reflects current permissions and enables Save only when dirty', async () => {
        const wrapper = mountMatrix();
        const saveBtn = wrapper.findAll('tfoot button').find((b) => b.text().includes('Save'));

        // Not dirty initially → disabled
        expect(saveBtn.element.disabled).toBe(true);

        // Toggle the student's currently-unchecked "approve_document" (p20, col student = index 2)
        const studentApprove = wrapper.findAll('tbody input[type="checkbox"]')[2];
        await studentApprove.setValue(true);

        expect(saveBtn.element.disabled).toBe(false);
    });

    it('saves the edited permission set via router.patch', async () => {
        const wrapper = mountMatrix();
        const studentApprove = wrapper.findAll('tbody input[type="checkbox"]')[2];
        await studentApprove.setValue(true);

        await wrapper.findAll('tfoot button').find((b) => b.text().includes('Save')).trigger('click');

        expect(patchMock).toHaveBeenCalledTimes(1);
        const [url, payload] = patchMock.mock.calls[0];
        expect(url).toBe('/admin.roles.permissions/1');
        expect(payload.permissions).toEqual([10, 20]);
    });
});

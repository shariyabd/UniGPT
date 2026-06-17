import { reactive } from 'vue';

/**
 * App-wide confirmation dialog backed by a single <ConfirmDialog> mounted in
 * AppLayout. Usage:
 *
 *   const { confirm } = useConfirm();
 *   if (!(await confirm({ title: 'Delete user', message: '…', variant: 'danger' }))) return;
 *
 * `confirm()` returns a Promise<boolean> that resolves true on accept and
 * false on cancel/backdrop/Escape — a friendly replacement for window.confirm.
 */
const state = reactive({
    open: false,
    title: 'Are you sure?',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'danger', // 'danger' | 'primary'
    hideCancel: false,
    resolver: null,
});

function settle(result) {
    state.open = false;
    if (state.resolver) {
        state.resolver(result);
        state.resolver = null;
    }
}

function confirm(options = {}) {
    state.title = options.title ?? 'Are you sure?';
    state.message = options.message ?? '';
    state.confirmLabel = options.confirmLabel ?? 'Confirm';
    state.cancelLabel = options.cancelLabel ?? 'Cancel';
    state.variant = options.variant ?? 'danger';
    state.hideCancel = options.hideCancel ?? false;
    state.open = true;

    return new Promise((resolve) => {
        state.resolver = resolve;
    });
}

/**
 * Single-button informational dialog — a friendly replacement for window.alert.
 */
function notify(options = {}) {
    return confirm({
        confirmLabel: 'OK',
        variant: 'primary',
        ...options,
        hideCancel: true,
    });
}

export function useConfirm() {
    return {
        state,
        confirm,
        notify,
        accept: () => settle(true),
        cancel: () => settle(false),
    };
}

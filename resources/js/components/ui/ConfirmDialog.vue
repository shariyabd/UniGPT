<script setup>
import { watch, onUnmounted } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const { state, accept, cancel } = useConfirm();

const onKeydown = (event) => {
    if (!state.open) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        cancel();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        accept();
    }
};

// Only listen while a dialog is actually open.
watch(
    () => state.open,
    (isOpen) => {
        if (isOpen) {
            window.addEventListener('keydown', onKeydown);
        } else {
            window.removeEventListener('keydown', onKeydown);
        }
    }
);

onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.open"
                class="fixed inset-0 z-[60] overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-content/60 backdrop-blur-sm" @click="cancel"></div>

                    <div class="relative w-full max-w-md ui-card">
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <span
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                                    :class="state.variant === 'danger' ? 'bg-danger-bg text-danger-fg' : 'bg-primary-soft text-primary'"
                                >
                                    <ExclamationTriangleIcon class="h-6 w-6" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="ui-card-title">{{ state.title }}</h3>
                                    <p v-if="state.message" class="mt-1 text-sm text-content-muted">
                                        {{ state.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                            <button v-if="!state.hideCancel" type="button" class="ui-btn-ghost" @click="cancel">
                                {{ state.cancelLabel }}
                            </button>
                            <button
                                type="button"
                                :class="state.variant === 'danger' ? 'ui-btn-danger' : 'ui-btn-primary'"
                                @click="accept"
                            >
                                {{ state.confirmLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

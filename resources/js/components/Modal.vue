<script setup>
import { onBeforeUnmount, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const emit = defineEmits(['close']);

const widths = { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-3xl' };

function onKey(e) {
    if (e.key === 'Escape') emit('close');
}

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
        open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-from-class="opacity-0" enter-active-class="transition duration-150" leave-to-class="opacity-0" leave-active-class="transition duration-100">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-[2px] sm:p-8"
                @mousedown.self="emit('close')"
            >
                <div class="my-auto w-full rounded-2xl bg-white shadow-2xl" :class="widths[size]" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                        <button class="btn btn-ghost btn-sm" aria-label="Close" @click="emit('close')">
                            <Icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

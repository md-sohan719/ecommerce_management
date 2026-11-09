<script setup>
import { dismiss, toasts } from '../lib/toast';
import Icon from './Icon.vue';
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6">
        <TransitionGroup
            enter-from-class="opacity-0 -translate-y-2"
            enter-active-class="transition duration-200"
            leave-to-class="opacity-0"
            leave-active-class="transition duration-150"
        >
            <div
                v-for="t in toasts"
                :key="t.id"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-white px-4 py-3 text-sm text-slate-700 shadow-lg"
                :class="t.type === 'error' ? 'border-rose-200' : 'border-emerald-200'"
                role="status"
            >
                <Icon
                    :name="t.type === 'error' ? 'alert-circle' : 'check-circle'"
                    class="h-5 w-5"
                    :class="t.type === 'error' ? 'text-rose-600' : 'text-emerald-600'"
                />
                <p class="flex-1">{{ t.message }}</p>
                <button class="text-slate-400 hover:text-slate-600" aria-label="Dismiss" @click="dismiss(t.id)">
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

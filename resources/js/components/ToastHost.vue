<script setup>
import { dismiss, toasts } from '../lib/toast';
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
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl px-4 py-3 text-sm shadow-lg ring-1"
                :class="t.type === 'error' ? 'bg-rose-50 text-rose-800 ring-rose-200' : 'bg-emerald-50 text-emerald-800 ring-emerald-200'"
                role="status"
            >
                <span class="mt-0.5">{{ t.type === 'error' ? '⚠️' : '✅' }}</span>
                <p class="flex-1">{{ t.message }}</p>
                <button class="opacity-60 hover:opacity-100" aria-label="Dismiss" @click="dismiss(t.id)">✕</button>
            </div>
        </TransitionGroup>
    </div>
</template>

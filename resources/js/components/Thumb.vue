<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    rounded: { type: String, default: 'rounded-lg' },
});

const failed = ref(false);
watch(() => props.src, () => (failed.value = false));

const initials = computed(() =>
    (props.alt || '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join(''),
);
</script>

<template>
    <div class="relative overflow-hidden bg-slate-100" :class="rounded">
        <img v-if="src && !failed" :src="src" :alt="alt" loading="lazy" class="h-full w-full object-cover" @error="failed = true" />
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300 font-semibold text-slate-500">
            {{ initials }}
        </div>
    </div>
</template>

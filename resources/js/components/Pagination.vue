<script setup>
import { computed } from 'vue';

const props = defineProps({
    meta: { type: Object, default: null },
});

const emit = defineEmits(['change']);

const pages = computed(() => {
    if (!props.meta) return [];
    const { current_page: current, last_page: last } = props.meta;
    const set = new Set([1, last, current - 1, current, current + 1].filter((p) => p >= 1 && p <= last));
    const sorted = [...set].sort((a, b) => a - b);
    const out = [];
    sorted.forEach((p, i) => {
        if (i > 0 && p - sorted[i - 1] > 1) out.push('…');
        out.push(p);
    });
    return out;
});
</script>

<template>
    <div v-if="meta && meta.total > 0" class="flex flex-col items-center justify-between gap-3 text-sm text-slate-600 sm:flex-row">
        <p>
            Showing <span class="font-medium text-slate-800">{{ meta.from }}</span>–<span class="font-medium text-slate-800">{{ meta.to }}</span>
            of <span class="font-medium text-slate-800">{{ meta.total }}</span>
        </p>
        <nav v-if="meta.last_page > 1" class="flex items-center gap-1">
            <button class="btn btn-secondary btn-sm" :disabled="meta.current_page === 1" @click="emit('change', meta.current_page - 1)">‹ Prev</button>
            <template v-for="(p, i) in pages" :key="i">
                <span v-if="p === '…'" class="px-1 text-slate-400">…</span>
                <button
                    v-else
                    class="btn btn-sm"
                    :class="p === meta.current_page ? 'btn-primary' : 'btn-secondary'"
                    @click="p !== meta.current_page && emit('change', p)"
                >
                    {{ p }}
                </button>
            </template>
            <button class="btn btn-secondary btn-sm" :disabled="meta.current_page === meta.last_page" @click="emit('change', meta.current_page + 1)">Next ›</button>
        </nav>
    </div>
</template>

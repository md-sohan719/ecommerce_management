<script setup>
const search = defineModel('search', { type: String, default: '' });
const sort = defineModel('sort', { type: String, default: 'latest' });

defineProps({
    placeholder: { type: String, default: 'Search products…' },
});
</script>

<template>
    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">🔍</span>
            <input v-model="search" type="search" class="input !pl-9" :placeholder="placeholder" />
        </div>
        <select v-model="sort" class="input sm:!w-48">
            <option value="latest">Newest first</option>
            <option value="price_asc">Price: low to high</option>
            <option value="price_desc">Price: high to low</option>
        </select>
    </div>
</template>

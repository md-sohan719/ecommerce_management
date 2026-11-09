<script setup>
import { onMounted, ref } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { toast } from '../../lib/toast';

const stats = ref(null);

onMounted(async () => {
    try {
        const { data } = await api.get('/admin/stats');
        stats.value = data;
    } catch (e) {
        toast.error(errorMessage(e));
    }
});

const cards = [
    { key: 'shops', label: 'Shops', icon: '🏪', to: 'admin.shops', sub: (s) => `${s.active_shops} active` },
    { key: 'products', label: 'Catalog products', icon: '📦', to: 'admin.products', sub: (s) => `${s.unlisted_products} not in any shop` },
    { key: 'listings', label: 'Shop listings', icon: '🏷️', to: 'admin.shops', sub: () => 'products assigned to shops' },
    { key: 'shop_owners', label: 'Shop owners', icon: '👥', to: 'admin.owners', sub: () => 'accounts' },
];
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <RouterLink v-for="c in cards" :key="c.key" :to="{ name: c.to }" class="card p-5 transition hover:border-indigo-200 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ c.label }}</p>
                    <span class="text-2xl">{{ c.icon }}</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats ? stats[c.key] : '–' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ stats ? c.sub(stats) : '' }}</p>
            </RouterLink>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">How it works</h2>
            <ul class="mt-3 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                <li class="flex gap-2"><span>📦</span><span><b>Products</b> form a shared catalog. They can be created by you or by any shop owner.</span></li>
                <li class="flex gap-2"><span>🏪</span><span><b>Shops</b> belong to a shop owner, who picks products from the catalog and sets shop-specific prices, details and thumbnails.</span></li>
                <li class="flex gap-2"><span>👀</span><span><b>Customers</b> only see products that are assigned to an active shop. Unassigned products are never shown.</span></li>
                <li class="flex gap-2"><span>👥</span><span><b>Shop owners</b> are created here and assigned to shops from the Shops page.</span></li>
            </ul>
        </div>
    </div>
</template>

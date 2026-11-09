<script setup>
import { onMounted, ref } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { toast } from '../../lib/toast';
import Icon from '../../components/Icon.vue';

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
    { key: 'shops', label: 'Shops', icon: 'store', color: 'bg-indigo-50 text-indigo-600', to: 'admin.shops', sub: (s) => `${s.active_shops} active` },
    { key: 'products', label: 'Catalog products', icon: 'cube', color: 'bg-amber-50 text-amber-600', to: 'admin.products', sub: (s) => `${s.unlisted_products} not in any shop` },
    { key: 'listings', label: 'Shop listings', icon: 'tag', color: 'bg-emerald-50 text-emerald-600', to: 'admin.shops', sub: () => 'Products assigned to shops' },
    { key: 'shop_owners', label: 'Shop owners', icon: 'users', color: 'bg-sky-50 text-sky-600', to: 'admin.owners', sub: () => 'Registered accounts' },
];

const notes = [
    { icon: 'cube', title: 'Products', text: 'form a shared catalog. They can be created by the admin or by any shop owner.' },
    { icon: 'store', title: 'Shops', text: 'belong to a shop owner, who picks products from the catalog and sets shop-specific prices, details and thumbnails.' },
    { icon: 'eye', title: 'Customers', text: 'only see products that are assigned to an active shop. Unassigned products are never shown.' },
    { icon: 'users', title: 'Shop owners', text: 'are created on the Shop owners page and assigned to shops from the Shops page.' },
];
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <RouterLink v-for="c in cards" :key="c.key" :to="{ name: c.to }" class="card p-5 transition hover:border-indigo-200 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ c.label }}</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg" :class="c.color">
                        <Icon :name="c.icon" class="h-5 w-5" />
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats ? stats[c.key] : '–' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ stats ? c.sub(stats) : '' }}</p>
            </RouterLink>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">Overview</h2>
            <ul class="mt-4 grid gap-4 text-sm text-slate-600 md:grid-cols-2">
                <li v-for="n in notes" :key="n.title" class="flex gap-3">
                    <Icon :name="n.icon" class="mt-0.5 h-5 w-5 text-slate-400" />
                    <span><b class="text-slate-800">{{ n.title }}</b> {{ n.text }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

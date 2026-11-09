<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { money } from '../../lib/format';
import { ensureOwnerShops, ownerShops } from '../../lib/ownerShops';
import { toast } from '../../lib/toast';
import { useList } from '../../lib/useList';
import ListingForm from '../../components/ListingForm.vue';
import Modal from '../../components/Modal.vue';
import Pagination from '../../components/Pagination.vue';
import Thumb from '../../components/Thumb.vue';

const shop = computed(() => ownerShops.current);

const { items: listings, meta, loading, filters, load } = useList(
    () => (ownerShops.currentId ? `/owner/shops/${ownerShops.currentId}/listings` : null),
    { status: '' },
    { immediate: false },
);

onMounted(async () => {
    try {
        await ensureOwnerShops();
    } catch (e) {
        toast.error(errorMessage(e));
    }
    load(1);
});

watch(
    () => ownerShops.currentId,
    () => load(1),
);

// ----- Edit listing -----
const modal = reactive({ open: false, listing: null, key: 0 });

function edit(listing) {
    modal.listing = listing;
    modal.key++;
    modal.open = true;
}

function saved() {
    modal.open = false;
    load();
}

async function toggle(listing) {
    try {
        await api.put(`/owner/shops/${shop.value.id}/listings/${listing.id}`, {
            price: listing.price,
            is_active: !listing.is_active,
        });
        listing.is_active = !listing.is_active;
        toast.success(listing.is_active ? 'Product is now visible to customers.' : 'Product hidden from customers.');
    } catch (e) {
        toast.error(errorMessage(e));
    }
}

async function remove(listing) {
    if (!confirm(`Remove "${listing.effective.name}" from ${shop.value.name}? The product stays in the shared catalog.`)) return;
    try {
        await api.delete(`/owner/shops/${shop.value.id}/listings/${listing.id}`);
        toast.success('Removed from your shop.');
        load();
    } catch (e) {
        toast.error(errorMessage(e));
    }
}

function priceDiff(listing) {
    const base = listing.product?.base_price;
    if (base === undefined || base === null) return null;
    return listing.price - base;
}
</script>

<template>
    <div class="space-y-4">
        <!-- No shop assigned -->
        <div v-if="ownerShops.loaded && !shop" class="card p-10 text-center">
            <p class="text-4xl">🏪</p>
            <h2 class="mt-3 text-lg font-semibold text-slate-900">You don't have a shop yet</h2>
            <p class="mt-1 text-sm text-slate-600">Ask the administrator to assign a shop to your account. Meanwhile you can still add products to the shared catalog.</p>
            <RouterLink :to="{ name: 'owner.catalog' }" class="btn btn-primary mt-5">Browse the catalog</RouterLink>
        </div>

        <template v-else-if="shop">
            <div class="card flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">{{ shop.name }}</h2>
                        <span class="badge" :class="shop.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                            {{ shop.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500">
                        Set your own price, details and thumbnail for every product you sell.
                        <span v-if="!shop.is_active" class="text-amber-700">This shop is inactive, so customers can't see it.</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <RouterLink v-if="shop.is_active" :to="{ name: 'shop', params: { slug: shop.slug } }" target="_blank" class="btn btn-secondary">View shop ↗</RouterLink>
                    <RouterLink :to="{ name: 'owner.catalog' }" class="btn btn-primary">+ Add from catalog</RouterLink>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <input v-model="filters.search" type="search" class="input sm:!w-72" placeholder="Search my products…" />
                <select v-model="filters.status" class="input sm:!w-48">
                    <option value="">All</option>
                    <option value="active">Visible</option>
                    <option value="hidden">Hidden</option>
                </select>
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="th">Product (as customers see it)</th>
                                <th class="th">Your price</th>
                                <th class="th">Visibility</th>
                                <th class="th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" :class="{ 'opacity-60': loading }">
                            <tr v-for="l in listings" :key="l.id" class="hover:bg-slate-50/60">
                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <Thumb :src="l.effective.thumbnail_url" :alt="l.effective.name" class="h-12 w-12 shrink-0" />
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ l.effective.name }}</p>
                                            <p v-if="l.title" class="truncate text-xs text-slate-500">Catalog: {{ l.product.name }}</p>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <span v-if="l.title" class="badge bg-violet-50 text-violet-700">Custom title</span>
                                                <span v-if="l.description" class="badge bg-violet-50 text-violet-700">Custom details</span>
                                                <span v-if="l.thumbnail_url" class="badge bg-violet-50 text-violet-700">Custom image</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="td">
                                    <p class="font-semibold text-slate-900">{{ money(l.price) }}</p>
                                    <p v-if="priceDiff(l) !== null" class="text-xs" :class="priceDiff(l) > 0 ? 'text-amber-600' : priceDiff(l) < 0 ? 'text-emerald-600' : 'text-slate-400'">
                                        Base {{ money(l.product.base_price) }}
                                    </p>
                                </td>
                                <td class="td">
                                    <button
                                        class="badge cursor-pointer"
                                        :class="l.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        :title="l.is_active ? 'Click to hide from customers' : 'Click to show to customers'"
                                        @click="toggle(l)"
                                    >
                                        {{ l.is_active ? '● Visible' : '○ Hidden' }}
                                    </button>
                                </td>
                                <td class="td">
                                    <div class="flex justify-end gap-1">
                                        <RouterLink
                                            v-if="l.is_active && shop.is_active"
                                            :to="{ name: 'product', params: { slug: shop.slug, id: l.product_id } }"
                                            target="_blank"
                                            class="btn btn-ghost btn-sm"
                                        >
                                            View
                                        </RouterLink>
                                        <button class="btn btn-secondary btn-sm" @click="edit(l)">Edit</button>
                                        <button class="btn btn-ghost btn-sm text-rose-600" @click="remove(l)">Remove</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!listings.length && !loading">
                                <td colspan="4" class="td py-12 text-center text-slate-500">
                                    <template v-if="filters.search || filters.status">No products match your filters.</template>
                                    <template v-else>
                                        No products in this shop yet.
                                        <RouterLink :to="{ name: 'owner.catalog' }" class="text-indigo-600 hover:underline">Add some from the catalog</RouterLink>.
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :meta="meta" @change="load" />
        </template>

        <Modal :open="modal.open" title="Edit product in your shop" size="lg" @close="modal.open = false">
            <ListingForm
                v-if="shop && modal.listing"
                :key="modal.key"
                :shop-id="shop.id"
                :shop-name="shop.name"
                :listing="modal.listing"
                @saved="saved"
                @cancel="modal.open = false"
            />
        </Modal>
    </div>
</template>

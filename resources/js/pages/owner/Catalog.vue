<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { auth } from '../../lib/auth';
import { money } from '../../lib/format';
import { ensureOwnerShops, loadOwnerShops, ownerShops } from '../../lib/ownerShops';
import { toast } from '../../lib/toast';
import { useList } from '../../lib/useList';
import ListingForm from '../../components/ListingForm.vue';
import Modal from '../../components/Modal.vue';
import Pagination from '../../components/Pagination.vue';
import ProductForm from '../../components/ProductForm.vue';
import Thumb from '../../components/Thumb.vue';

const shop = computed(() => ownerShops.current);

const { items: products, meta, loading, filters, load } = useList(
    '/owner/catalog',
    { shop_id: ownerShops.currentId ?? '', availability: '', mine: false },
    { perPage: 12, immediate: false },
);

// Tabs map onto the API filters
const tab = ref('all');
const tabs = [
    { key: 'all', label: 'All products' },
    { key: 'not_listed', label: 'Not in my shop' },
    { key: 'mine', label: 'Created by me' },
];
watch(tab, (t) => {
    filters.availability = t === 'not_listed' ? 'not_listed' : '';
    filters.mine = t === 'mine';
});

onMounted(async () => {
    try {
        await ensureOwnerShops();
    } catch (e) {
        toast.error(errorMessage(e));
    }
    const id = ownerShops.currentId ?? '';
    if (filters.shop_id !== id) filters.shop_id = id; // the filter watcher reloads
    else load(1);
});

watch(
    () => ownerShops.currentId,
    (id) => (filters.shop_id = id ?? ''),
);

function creatorLabel(product) {
    if (!product.creator) return 'Unknown';
    if (product.creator.id === auth.user?.id) return 'You';
    return product.creator.role === 'admin' ? 'Admin' : product.creator.name;
}

// ----- Add to shop -----
const listingModal = reactive({ open: false, product: null, key: 0 });

function addToShop(product) {
    listingModal.product = product;
    listingModal.key++;
    listingModal.open = true;
}

function listed() {
    listingModal.open = false;
    load();
    loadOwnerShops().catch(() => {});
}

// ----- Create / edit own product -----
const productModal = reactive({ open: false, product: null, key: 0 });

function openProductForm(product = null) {
    productModal.product = product;
    productModal.key++;
    productModal.open = true;
}

function productSaved() {
    productModal.open = false;
    load();
}

async function removeProduct(product) {
    if (!confirm(`Delete "${product.name}" from the shared catalog? It will also be removed from your shops.`)) return;
    try {
        await api.delete(`/owner/products/${product.id}`);
        toast.success('Product deleted.');
        load();
    } catch (e) {
        toast.error(errorMessage(e));
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Shared product catalog</h2>
                <p class="text-sm text-slate-500">
                    Products created by the admin and by all shop owners. Add any of them to
                    <b v-if="shop">{{ shop.name }}</b><span v-else>your shop</span> with your own price, details and thumbnail.
                </p>
            </div>
            <button class="btn btn-primary" @click="openProductForm()">+ Create new product</button>
        </div>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                    :class="tab === t.key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    @click="tab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>
            <input v-model="filters.search" type="search" class="input lg:!w-80" placeholder="Search name, SKU, description…" />
        </div>

        <div v-if="loading && !products.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div v-for="i in 8" :key="i" class="card h-72 animate-pulse bg-slate-100" />
        </div>

        <div v-else-if="products.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" :class="{ 'opacity-60': loading }">
            <article v-for="p in products" :key="p.id" class="card flex flex-col overflow-hidden">
                <div class="relative">
                    <Thumb :src="p.thumbnail_url" :alt="p.name" class="aspect-[4/3] w-full" rounded="rounded-none" />
                    <span v-if="p.is_listed" class="badge absolute top-2 left-2 bg-emerald-600 text-white shadow">✓ In your shop</span>
                </div>
                <div class="flex flex-1 flex-col p-4">
                    <p class="line-clamp-2 font-medium text-slate-900">{{ p.name }}</p>
                    <p class="text-xs text-slate-500">{{ p.sku || 'No SKU' }} · by {{ creatorLabel(p) }}</p>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-800">{{ money(p.base_price) }}</span>
                        <span class="text-xs text-slate-500">in {{ p.listings_count }} shop(s)</span>
                    </div>

                    <div class="mt-auto space-y-2 pt-4">
                        <button
                            v-if="!p.is_listed"
                            class="btn btn-primary w-full"
                            :disabled="!shop"
                            :title="shop ? '' : 'You need a shop first'"
                            @click="addToShop(p)"
                        >
                            + Add to {{ shop ? shop.name : 'shop' }}
                        </button>
                        <RouterLink v-else :to="{ name: 'owner.listings' }" class="btn btn-secondary w-full">Manage in my shop</RouterLink>

                        <div v-if="p.can_edit" class="flex gap-2">
                            <button class="btn btn-ghost btn-sm flex-1 border border-slate-200" @click="openProductForm(p)">Edit product</button>
                            <button class="btn btn-ghost btn-sm flex-1 border border-slate-200 text-rose-600" @click="removeProduct(p)">Delete</button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="card p-10 text-center text-slate-500">
            <template v-if="tab === 'mine'">You haven't created any products yet.</template>
            <template v-else-if="tab === 'not_listed'">Your shop already has every catalog product. 🎉</template>
            <template v-else>No products found.</template>
        </div>

        <Pagination :meta="meta" @change="load" />

        <Modal :open="listingModal.open" :title="`Add to ${shop?.name ?? 'shop'}`" size="lg" @close="listingModal.open = false">
            <ListingForm
                v-if="shop && listingModal.product"
                :key="listingModal.key"
                :shop-id="shop.id"
                :shop-name="shop.name"
                :product="listingModal.product"
                @saved="listed"
                @cancel="listingModal.open = false"
            />
        </Modal>

        <Modal :open="productModal.open" :title="productModal.product ? 'Edit product' : 'Create new product'" size="lg" @close="productModal.open = false">
            <ProductForm
                :key="productModal.key"
                :product="productModal.product"
                mode="owner"
                :shops="ownerShops.list"
                :default-shop-id="ownerShops.currentId"
                @saved="productSaved"
                @cancel="productModal.open = false"
            />
        </Modal>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { debounce } from '../../lib/format';
import ProductCard from '../../components/ProductCard.vue';
import ProductFilters from '../../components/ProductFilters.vue';
import Pagination from '../../components/Pagination.vue';

const shops = ref([]);
const products = ref([]);
const meta = ref(null);
const loading = ref(true);
const error = ref('');

const search = ref('');
const sort = ref('latest');
const page = ref(1);

async function loadShops() {
    try {
        const { data } = await api.get('/storefront/shops');
        shops.value = data.data;
    } catch (e) {
        error.value = errorMessage(e);
    }
}

async function loadProducts() {
    loading.value = true;
    try {
        const { data } = await api.get('/storefront/products', {
            params: { search: search.value || undefined, sort: sort.value, page: page.value, per_page: 12 },
        });
        products.value = data.data;
        meta.value = data.meta;
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}

const reload = debounce(() => {
    page.value = 1;
    loadProducts();
});

watch(search, reload);
watch(sort, () => {
    page.value = 1;
    loadProducts();
});

function goTo(p) {
    page.value = p;
    loadProducts();
    document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
}

onMounted(() => {
    loadShops();
    loadProducts();
});
</script>

<template>
    <div>
        <!-- Hero -->
        <section class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 text-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                <p class="text-sm font-medium tracking-wide text-indigo-200 uppercase">Local shops, one place</p>
                <h1 class="mt-2 max-w-2xl text-3xl font-bold sm:text-4xl">Discover products from shops near you</h1>
                <p class="mt-3 max-w-2xl text-indigo-100">
                    Every shop sets its own prices — compare offers for the same product across shops.
                </p>
            </div>
        </section>

        <div class="mx-auto max-w-7xl space-y-12 px-4 py-10 sm:px-6">
            <p v-if="error" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>

            <!-- Shops -->
            <section>
                <div class="mb-4 flex items-end justify-between">
                    <h2 class="text-xl font-bold text-slate-900">Shops</h2>
                    <span class="text-sm text-slate-500">{{ shops.length }} shops</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <RouterLink
                        v-for="shop in shops"
                        :key="shop.id"
                        :to="{ name: 'shop', params: { slug: shop.slug } }"
                        class="card group p-5 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl">🏪</span>
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-slate-900 group-hover:text-indigo-700">{{ shop.name }}</h3>
                                <p class="text-xs text-slate-500">{{ shop.products_count }} products</p>
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ shop.description }}</p>
                        <p v-if="shop.address" class="mt-2 truncate text-xs text-slate-400">📍 {{ shop.address }}</p>
                    </RouterLink>
                    <p v-if="!shops.length && !error" class="text-sm text-slate-500">No shops yet.</p>
                </div>
            </section>

            <!-- Products from all shops -->
            <section id="products" class="scroll-mt-20">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <h2 class="text-xl font-bold text-slate-900">{{ search ? `Results for “${search}”` : 'All products' }}</h2>
                    <ProductFilters v-model:search="search" v-model:sort="sort" class="md:w-[32rem]" />
                </div>

                <div v-if="loading && !products.length" class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    <div v-for="i in 8" :key="i" class="card aspect-[3/4] animate-pulse bg-slate-100" />
                </div>
                <div v-else-if="products.length" class="grid grid-cols-2 gap-4 transition md:grid-cols-3 lg:grid-cols-4" :class="{ 'opacity-60': loading }">
                    <ProductCard v-for="p in products" :key="p.listing_id" :product="p" />
                </div>
                <div v-else class="card p-10 text-center text-slate-500">No products found.</div>

                <Pagination class="mt-6" :meta="meta" @change="goTo" />
            </section>
        </div>
    </div>
</template>

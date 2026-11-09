<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, errorMessage } from '../../lib/api';
import { debounce } from '../../lib/format';
import ProductCard from '../../components/ProductCard.vue';
import ProductFilters from '../../components/ProductFilters.vue';
import Pagination from '../../components/Pagination.vue';
import NotFound from './NotFound.vue';

const route = useRoute();

const shop = ref(null);
const notFound = ref(false);
const products = ref([]);
const meta = ref(null);
const loading = ref(true);
const error = ref('');

const search = ref('');
const sort = ref('latest');
const page = ref(1);

async function loadShop() {
    notFound.value = false;
    shop.value = null;
    try {
        const { data } = await api.get(`/storefront/shops/${route.params.slug}`);
        shop.value = data.data;
        document.title = `${shop.value.name} · Small E-commerce`;
        await loadProducts();
    } catch (e) {
        if (e.response?.status === 404) notFound.value = true;
        else error.value = errorMessage(e);
        loading.value = false;
    }
}

async function loadProducts() {
    loading.value = true;
    try {
        const { data } = await api.get(`/storefront/shops/${route.params.slug}/products`, {
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
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

watch(
    () => route.params.slug,
    (slug) => {
        if (!slug) return;
        search.value = '';
        page.value = 1;
        loadShop();
    },
    { immediate: true },
);
</script>

<template>
    <NotFound v-if="notFound" message="This shop does not exist or is currently closed." />
    <div v-else>
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <RouterLink :to="{ name: 'home' }" class="text-sm text-slate-500 hover:text-indigo-600">← All shops</RouterLink>
                <div v-if="shop" class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-3xl">🏪</span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ shop.name }}</h1>
                        <p v-if="shop.description" class="mt-1 text-slate-600">{{ shop.description }}</p>
                        <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span v-if="shop.address">📍 {{ shop.address }}</span>
                            <span v-if="shop.phone">📞 {{ shop.phone }}</span>
                            <span>📦 {{ shop.products_count }} products</span>
                        </p>
                    </div>
                </div>
                <div v-else class="mt-3 h-16 w-72 animate-pulse rounded-xl bg-slate-100" />
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <p v-if="error" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>
            <ProductFilters v-model:search="search" v-model:sort="sort" :placeholder="shop ? `Search in ${shop.name}…` : 'Search…'" class="mb-6" />

            <div v-if="loading && !products.length" class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                <div v-for="i in 8" :key="i" class="card aspect-[3/4] animate-pulse bg-slate-100" />
            </div>
            <div v-else-if="products.length" class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4" :class="{ 'opacity-60': loading }">
                <ProductCard v-for="p in products" :key="p.listing_id" :product="p" :show-shop="false" />
            </div>
            <div v-else class="card p-10 text-center text-slate-500">
                {{ search ? 'No products match your search.' : 'This shop has no products yet.' }}
            </div>

            <Pagination class="mt-6" :meta="meta" @change="goTo" />
        </div>
    </div>
</template>

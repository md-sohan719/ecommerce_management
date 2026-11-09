<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, errorMessage } from '../../lib/api';
import { money } from '../../lib/format';
import Thumb from '../../components/Thumb.vue';
import NotFound from './NotFound.vue';

const route = useRoute();

const product = ref(null);
const otherShops = ref([]);
const notFound = ref(false);
const error = ref('');

async function load() {
    product.value = null;
    notFound.value = false;
    error.value = '';
    try {
        const { data } = await api.get(`/storefront/shops/${route.params.slug}/products/${route.params.id}`);
        product.value = data.data;
        otherShops.value = data.other_shops;
        document.title = `${product.value.name} · ${product.value.shop.name}`;
    } catch (e) {
        if (e.response?.status === 404) notFound.value = true;
        else error.value = errorMessage(e);
    }
}

watch(() => [route.params.slug, route.params.id], ([slug, id]) => slug && id && load(), { immediate: true });
</script>

<template>
    <NotFound v-if="notFound" message="This product is not available in this shop." />
    <div v-else class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <p v-if="error" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>

        <div v-if="!product && !error" class="grid gap-8 md:grid-cols-2">
            <div class="aspect-square animate-pulse rounded-2xl bg-slate-100" />
            <div class="space-y-4">
                <div class="h-8 w-3/4 animate-pulse rounded bg-slate-100" />
                <div class="h-6 w-1/3 animate-pulse rounded bg-slate-100" />
                <div class="h-24 animate-pulse rounded bg-slate-100" />
            </div>
        </div>

        <template v-if="product">
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <RouterLink :to="{ name: 'home' }" class="hover:text-indigo-600">Home</RouterLink>
                <span>/</span>
                <RouterLink :to="{ name: 'shop', params: { slug: product.shop.slug } }" class="hover:text-indigo-600">{{ product.shop.name }}</RouterLink>
                <span>/</span>
                <span class="text-slate-700">{{ product.name }}</span>
            </nav>

            <div class="grid gap-8 md:grid-cols-2">
                <Thumb :src="product.thumbnail_url" :alt="product.name" class="aspect-square w-full border border-slate-200" rounded="rounded-2xl" />

                <div>
                    <RouterLink :to="{ name: 'shop', params: { slug: product.shop.slug } }" class="badge bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                        🏪 Sold by {{ product.shop.name }}
                    </RouterLink>
                    <h1 class="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">{{ product.name }}</h1>
                    <p class="mt-4 text-3xl font-bold text-indigo-700">{{ money(product.price) }}</p>
                    <p v-if="product.sku" class="mt-1 text-xs text-slate-400">SKU: {{ product.sku }}</p>

                    <div class="mt-6">
                        <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Details</h2>
                        <p class="mt-2 whitespace-pre-line text-slate-700">{{ product.description || 'No description provided.' }}</p>
                    </div>

                    <div v-if="otherShops.length" class="mt-8">
                        <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Also available at</h2>
                        <ul class="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                            <li v-for="offer in otherShops" :key="offer.listing_id">
                                <RouterLink
                                    :to="{ name: 'product', params: { slug: offer.shop.slug, id: offer.id } }"
                                    class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-slate-50"
                                >
                                    <span class="font-medium text-slate-800">{{ offer.shop.name }}</span>
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="badge"
                                            :class="offer.price < product.price ? 'bg-emerald-50 text-emerald-700' : offer.price > product.price ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ offer.price < product.price ? 'Cheaper' : offer.price > product.price ? 'Higher' : 'Same price' }}
                                        </span>
                                        <span class="font-semibold text-slate-900">{{ money(offer.price) }}</span>
                                    </span>
                                </RouterLink>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

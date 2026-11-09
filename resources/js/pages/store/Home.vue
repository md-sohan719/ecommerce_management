<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, errorMessage } from '../../lib/api';
import { debounce, money } from '../../lib/format';
import { fetchHome } from '../../lib/storefront';
import Icon from '../../components/Icon.vue';
import Pagination from '../../components/Pagination.vue';
import ProductCard from '../../components/ProductCard.vue';
import ProductFilters from '../../components/ProductFilters.vue';
import Thumb from '../../components/Thumb.vue';

const route = useRoute();

const ready = ref(false); // the whole page is shown at once, when the home data has arrived
const shops = ref([]);
const products = ref([]);
const meta = ref(null);
const loading = ref(false); // products reloading (search, sort, page)
const error = ref('');

const search = ref(typeof route.query.search === 'string' ? route.query.search : '');
const sort = ref('latest');
const page = ref(1);

// ----- Banner slider: one slide per featured shop -----
const slides = ref([]);
const current = ref(0);
let timer = null;

const themes = [
    { bg: 'bg-indigo-50', label: 'text-indigo-600', button: 'bg-indigo-600 hover:bg-indigo-700' },
    { bg: 'bg-amber-50', label: 'text-amber-600', button: 'bg-amber-500 hover:bg-amber-600' },
    { bg: 'bg-emerald-50', label: 'text-emerald-600', button: 'bg-emerald-600 hover:bg-emerald-700' },
];

const shopColors = ['bg-indigo-100 text-indigo-700', 'bg-amber-100 text-amber-700', 'bg-emerald-100 text-emerald-700', 'bg-rose-100 text-rose-700', 'bg-sky-100 text-sky-700'];

function go(index) {
    const count = slides.value.length;
    if (!count) return;
    current.value = (index + count) % count;
}

function play() {
    stop();
    if (slides.value.length > 1) timer = setInterval(() => go(current.value + 1), 6000);
}

function stop() {
    clearInterval(timer);
    timer = null;
}

onBeforeUnmount(stop);

// ----- Data -----
// First load: banner, shops and products come from a single request.
async function loadHome() {
    try {
        const data = await fetchHome({ search: search.value || undefined, sort: sort.value, per_page: 12 });
        slides.value = data.featured;
        shops.value = data.shops;
        products.value = data.products.data;
        meta.value = data.products.meta;
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        ready.value = true;
        play();
    }

    // Opened as /#shops or /#products: scroll there now that the sections exist
    if (route.hash) {
        await nextTick();
        scrollTo(route.hash.slice(1));
    }
}

// Search, sort and pagination only reload the product list.
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

// Search submitted from the header
watch(
    () => route.query.search,
    (value) => (search.value = typeof value === 'string' ? value : ''),
);

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

function goTo(p) {
    page.value = p;
    loadProducts();
    scrollTo('products');
}

const totalProducts = computed(() => shops.value.reduce((sum, s) => sum + (s.products_count || 0), 0));

const features = computed(() => [
    { icon: 'store', title: `${shops.value.length || 'Local'} shops`, text: 'All in one place' },
    { icon: 'scale', title: 'Compare prices', text: 'Same product, many offers' },
    { icon: 'tag', title: 'Shop prices', text: 'Set by each shop owner' },
    { icon: 'refresh', title: 'Always up to date', text: 'Listings managed by sellers' },
]);

onMounted(loadHome);
</script>

<template>
    <!-- Loading: one placeholder for the whole page -->
    <div v-if="!ready" class="mx-auto max-w-7xl animate-pulse space-y-12 px-4 pt-6 sm:px-6" aria-busy="true">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="h-80 rounded-2xl bg-slate-200/70 sm:h-[22rem] lg:col-span-2" />
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <div class="h-40 rounded-2xl bg-slate-200/70 lg:h-auto" />
                <div class="h-40 rounded-2xl bg-slate-200/70 lg:h-auto" />
            </div>
        </div>
        <div class="h-20 rounded-2xl bg-slate-200/70" />
        <div>
            <div class="mb-5 h-7 w-48 rounded bg-slate-200/70" />
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="i in 4" :key="i" class="h-44 rounded-2xl bg-slate-200/70" />
            </div>
        </div>
        <div>
            <div class="mb-5 h-7 w-40 rounded bg-slate-200/70" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                <div v-for="i in 8" :key="i" class="aspect-[3/4] rounded-2xl bg-slate-200/70" />
            </div>
        </div>
    </div>

    <div v-else class="mx-auto max-w-7xl space-y-12 px-4 pt-6 sm:px-6">
        <p v-if="error" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>

        <!-- Banner -->
        <section class="grid gap-4 lg:grid-cols-3">
            <!-- Slider -->
            <div class="relative overflow-hidden rounded-2xl lg:col-span-2" @mouseenter="stop" @mouseleave="play">
                <div v-if="slides.length" class="flex transition-transform duration-500 ease-out" :style="{ transform: `translateX(-${current * 100}%)` }">
                    <div v-for="(slide, i) in slides" :key="slide.shop.id" class="w-full shrink-0" :class="themes[i % themes.length].bg">
                        <div class="grid h-80 items-center gap-6 px-8 sm:h-[22rem] sm:px-12 md:grid-cols-5">
                            <div class="md:col-span-3">
                                <p class="text-xs font-semibold tracking-widest uppercase" :class="themes[i % themes.length].label">Featured shop</p>
                                <h2 class="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">{{ slide.shop.name }}</h2>
                                <p class="mt-3 line-clamp-2 max-w-md text-slate-600">{{ slide.shop.description }}</p>
                                <p class="mt-3 text-sm text-slate-500">
                                    {{ slide.shop.products_count }} products
                                    <template v-if="slide.shop.address"> · {{ slide.shop.address }}</template>
                                </p>
                                <RouterLink
                                    :to="{ name: 'shop', params: { slug: slide.shop.slug } }"
                                    class="mt-6 inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white shadow-sm transition"
                                    :class="themes[i % themes.length].button"
                                >
                                    Visit shop
                                    <Icon name="arrow-right" class="h-4 w-4" />
                                </RouterLink>
                            </div>
                            <RouterLink
                                :to="{ name: 'product', params: { slug: slide.shop.slug, id: slide.product.id } }"
                                class="relative hidden justify-self-center md:col-span-2 md:block"
                            >
                                <Thumb :src="slide.product.thumbnail_url" :alt="slide.product.name" class="h-56 w-56 bg-white shadow-lg ring-1 ring-black/5" rounded="rounded-2xl" />
                                <div class="absolute -bottom-4 left-1/2 w-48 -translate-x-1/2 rounded-lg bg-white px-3 py-2 text-center shadow-md">
                                    <p class="truncate text-xs text-slate-500">{{ slide.product.name }}</p>
                                    <p class="text-sm font-bold text-slate-900">{{ money(slide.product.price) }}</p>
                                </div>
                            </RouterLink>
                        </div>
                    </div>
                </div>

                <!-- Fallback when there are no shops yet -->
                <div v-else class="flex h-80 flex-col justify-center bg-indigo-50 px-8 sm:h-[22rem] sm:px-12">
                    <h2 class="text-3xl font-bold text-slate-900">Welcome to Small E-commerce</h2>
                    <p class="mt-3 text-slate-600">Shops will appear here as soon as they are open.</p>
                </div>

                <!-- Controls -->
                <template v-if="slides.length > 1">
                    <button
                        class="absolute top-1/2 left-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow hover:bg-white"
                        aria-label="Previous slide"
                        @click="go(current - 1)"
                    >
                        <Icon name="chevron-left" class="h-5 w-5" />
                    </button>
                    <button
                        class="absolute top-1/2 right-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow hover:bg-white"
                        aria-label="Next slide"
                        @click="go(current + 1)"
                    >
                        <Icon name="chevron-right" class="h-5 w-5" />
                    </button>
                    <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                        <button
                            v-for="(slide, i) in slides"
                            :key="slide.shop.id"
                            class="h-2 rounded-full transition-all"
                            :class="i === current ? 'w-6 bg-slate-800' : 'w-2 bg-slate-400/60 hover:bg-slate-500'"
                            :aria-label="`Go to slide ${i + 1}`"
                            @click="go(i)"
                        />
                    </div>
                </template>
            </div>

            <!-- Side promos -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <div class="flex flex-col justify-between rounded-2xl bg-slate-900 p-6 text-white">
                    <div>
                        <p class="text-xs font-semibold tracking-widest text-indigo-300 uppercase">Compare prices</p>
                        <h3 class="mt-2 text-xl font-semibold">Same product, different shops</h3>
                        <p class="mt-2 text-sm text-slate-300">Check every shop's offer before you buy.</p>
                    </div>
                    <button class="mt-4 inline-flex items-center gap-1.5 self-start text-sm font-semibold hover:text-indigo-300" @click="scrollTo('products')">
                        Browse {{ totalProducts || '' }} products
                        <Icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase">For shop owners</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900">Sell on Small E-commerce</h3>
                        <p class="mt-2 text-sm text-slate-500">List products from the shared catalog and set your own prices.</p>
                    </div>
                    <RouterLink :to="{ name: 'login' }" class="mt-4 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-slate-900 hover:text-indigo-600">
                        Seller login
                        <Icon name="arrow-right" class="h-4 w-4" />
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- Feature strip -->
        <section class="grid grid-cols-2 gap-6 rounded-2xl border border-slate-200 bg-white px-6 py-5 lg:grid-cols-4">
            <div v-for="f in features" :key="f.icon" class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    <Icon :name="f.icon" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">{{ f.title }}</p>
                    <p class="truncate text-xs text-slate-500">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <!-- Shops -->
        <section id="shops" class="scroll-mt-36">
            <div class="mb-5 flex items-end justify-between border-b border-slate-200 pb-3">
                <h2 class="text-xl font-semibold text-slate-900">Shop by store</h2>
                <span class="text-sm text-slate-500">{{ shops.length }} shops</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <RouterLink
                    v-for="(shop, i) in shops"
                    :key="shop.id"
                    :to="{ name: 'shop', params: { slug: shop.slug } }"
                    class="card group flex flex-col p-5 transition hover:border-indigo-200 hover:shadow-md"
                >
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg font-bold" :class="shopColors[i % shopColors.length]">
                            {{ shop.name.charAt(0) }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold text-slate-900 group-hover:text-indigo-600">{{ shop.name }}</h3>
                            <p class="text-xs text-slate-500">{{ shop.products_count }} products</p>
                        </div>
                    </div>
                    <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ shop.description }}</p>
                    <p v-if="shop.address" class="mt-3 flex items-center gap-1.5 truncate text-xs text-slate-400">
                        <Icon name="map-pin" class="h-4 w-4" />
                        <span class="truncate">{{ shop.address }}</span>
                    </p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600">
                        Visit shop
                        <Icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </RouterLink>
                <p v-if="!shops.length && !error" class="text-sm text-slate-500">No shops yet.</p>
            </div>
        </section>

        <!-- Products from all shops -->
        <section id="products" class="scroll-mt-36">
            <div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-3 md:flex-row md:items-end md:justify-between">
                <h2 class="text-xl font-semibold text-slate-900">{{ search ? `Results for “${search}”` : 'All products' }}</h2>
                <ProductFilters v-model:search="search" v-model:sort="sort" class="md:w-[32rem]" />
            </div>

            <div v-if="products.length" class="grid grid-cols-2 gap-4 transition md:grid-cols-3 lg:grid-cols-4" :class="{ 'opacity-60': loading }">
                <ProductCard v-for="p in products" :key="p.listing_id" :product="p" />
            </div>
            <div v-else class="card p-10 text-center text-slate-500">No products found.</div>

            <Pagination class="mt-6" :meta="meta" @change="goTo" />
        </section>
    </div>
</template>

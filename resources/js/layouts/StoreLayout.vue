<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { auth, homeRouteFor } from '../lib/auth';
import { fetchShops } from '../lib/storefront';
import Icon from '../components/Icon.vue';

const route = useRoute();
const router = useRouter();

const shops = ref([]);
const query = ref(typeof route.query.search === 'string' ? route.query.search : '');
const year = new Date().getFullYear();

watch(
    () => route.query.search,
    (value) => (query.value = typeof value === 'string' ? value : ''),
);

onMounted(async () => {
    try {
        shops.value = await fetchShops();
    } catch {
        /* navigation still works without the shop links */
    }
});

function search() {
    const term = query.value.trim();
    router.push({ name: 'home', query: term ? { search: term } : {}, hash: '#products' });
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <!-- Top bar -->
        <div class="bg-slate-900 text-xs text-slate-300">
            <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
                <p class="truncate">Products from local shops, at each shop's own price.</p>
                <RouterLink v-if="auth.user" :to="homeRouteFor(auth.user)" class="shrink-0 hover:text-white">
                    {{ auth.isAdmin ? 'Admin panel' : 'Seller dashboard' }}
                </RouterLink>
                <RouterLink v-else :to="{ name: 'login' }" class="shrink-0 hover:text-white">Seller / Admin login</RouterLink>
            </div>
        </div>

        <!-- Header -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:h-[4.5rem] sm:gap-8 sm:px-6">
                <RouterLink :to="{ name: 'home' }" class="flex shrink-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <Icon name="bag" class="h-5 w-5" />
                    </span>
                    <span class="hidden text-lg font-bold tracking-tight text-slate-900 sm:block">Small E-commerce</span>
                </RouterLink>

                <form class="flex flex-1" role="search" @submit.prevent="search">
                    <input
                        v-model="query"
                        type="search"
                        class="h-11 w-full min-w-0 rounded-l-lg border border-r-0 border-slate-300 px-4 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none"
                        placeholder="Search for products…"
                        aria-label="Search products"
                    />
                    <button type="submit" class="flex h-11 items-center gap-2 rounded-r-lg bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700">
                        <Icon name="search" class="h-5 w-5" />
                        <span class="hidden sm:inline">Search</span>
                    </button>
                </form>

                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-700 md:flex">
                    <RouterLink :to="{ name: 'home', hash: '#shops' }" class="flex items-center gap-1.5 hover:text-indigo-600">
                        <Icon name="store" class="h-5 w-5" />
                        Shops
                    </RouterLink>
                    <RouterLink :to="auth.user ? homeRouteFor(auth.user) : { name: 'login' }" class="flex items-center gap-1.5 hover:text-indigo-600">
                        <Icon name="user" class="h-5 w-5" />
                        {{ auth.user ? 'Account' : 'Sign in' }}
                    </RouterLink>
                </nav>
            </div>

            <!-- Shop navigation -->
            <div class="border-t border-slate-100">
                <nav class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-4 text-sm whitespace-nowrap sm:px-6">
                    <RouterLink
                        :to="{ name: 'home' }"
                        class="border-b-2 py-2.5 font-medium"
                        :class="route.name === 'home' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    >
                        All products
                    </RouterLink>
                    <RouterLink
                        v-for="shop in shops"
                        :key="shop.id"
                        :to="{ name: 'shop', params: { slug: shop.slug } }"
                        class="border-b-2 py-2.5"
                        :class="route.params.slug === shop.slug ? 'border-indigo-600 font-medium text-indigo-600' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    >
                        {{ shop.name }}
                    </RouterLink>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <RouterView />
        </main>

        <!-- Footer -->
        <footer class="mt-16 bg-slate-900 text-slate-400">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                            <Icon name="bag" class="h-5 w-5" />
                        </span>
                        <span class="text-lg font-bold text-white">Small E-commerce</span>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed">
                        A marketplace for local shops. Every shop lists products from a shared catalog and sets its own prices.
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Shops</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li v-for="shop in shops.slice(0, 5)" :key="shop.id">
                            <RouterLink :to="{ name: 'shop', params: { slug: shop.slug } }" class="hover:text-white">{{ shop.name }}</RouterLink>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Customer</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><RouterLink :to="{ name: 'home', hash: '#products' }" class="hover:text-white">All products</RouterLink></li>
                        <li><RouterLink :to="{ name: 'home', hash: '#shops' }" class="hover:text-white">All shops</RouterLink></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Sell with us</h3>
                    <p class="mt-4 text-sm leading-relaxed">Shop owners manage their products and prices from the seller dashboard.</p>
                    <RouterLink :to="{ name: 'login' }" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-white hover:text-indigo-300">
                        Seller login
                        <Icon name="arrow-right" class="h-4 w-4" />
                    </RouterLink>
                </div>
            </div>
            <div class="border-t border-slate-800">
                <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-5 text-xs sm:flex-row sm:justify-between sm:px-6">
                    <p>&copy; {{ year }} Small E-commerce. All rights reserved.</p>
                    <p>All prices are in Bangladeshi Taka (BDT).</p>
                </div>
            </div>
        </footer>
    </div>
</template>

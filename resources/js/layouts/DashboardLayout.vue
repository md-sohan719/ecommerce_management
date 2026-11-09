<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { auth, logout } from '../lib/auth';
import { ensureOwnerShops, ownerShops, resetOwnerShops, selectShop } from '../lib/ownerShops';
import { errorMessage } from '../lib/api';
import { toast } from '../lib/toast';
import Icon from '../components/Icon.vue';

const route = useRoute();
const router = useRouter();
const mobileOpen = ref(false);

const nav = computed(() =>
    auth.isAdmin
        ? [
              { to: { name: 'admin.dashboard' }, label: 'Dashboard', icon: 'grid' },
              { to: { name: 'admin.shops' }, label: 'Shops', icon: 'store' },
              { to: { name: 'admin.products' }, label: 'Products', icon: 'cube' },
              { to: { name: 'admin.owners' }, label: 'Shop owners', icon: 'users' },
          ]
        : [
              { to: { name: 'owner.listings' }, label: 'My shop products', icon: 'tag' },
              { to: { name: 'owner.catalog' }, label: 'Product catalog', icon: 'stack' },
          ],
);

const currentShopId = computed({
    get: () => ownerShops.currentId,
    set: (id) => selectShop(Number(id)),
});

onMounted(async () => {
    if (auth.isShopOwner) {
        try {
            await ensureOwnerShops();
        } catch (e) {
            toast.error(errorMessage(e));
        }
    }
});

async function signOut() {
    await logout();
    resetOwnerShops();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen lg:flex">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 transform border-r border-slate-200 bg-white transition lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-2 border-b border-slate-200 px-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <Icon name="bag" class="h-5 w-5" />
                </span>
                <div class="leading-tight">
                    <p class="font-bold text-slate-900">Small E-commerce</p>
                    <p class="text-xs text-slate-500">{{ auth.isAdmin ? 'Admin panel' : 'Shop owner panel' }}</p>
                </div>
            </div>
            <nav class="space-y-1 p-3">
                <RouterLink
                    v-for="item in nav"
                    :key="item.label"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    exact-active-class="!bg-indigo-50 !text-indigo-700"
                    @click="mobileOpen = false"
                >
                    <Icon :name="item.icon" class="h-5 w-5" />{{ item.label }}
                </RouterLink>
                <hr class="my-3 border-slate-200" />
                <RouterLink :to="{ name: 'home' }" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    <Icon name="external" class="h-5 w-5" />View storefront
                </RouterLink>
            </nav>
        </aside>
        <div v-if="mobileOpen" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="mobileOpen = false" />

        <!-- Main -->
        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="btn btn-ghost btn-sm lg:hidden" aria-label="Menu" @click="mobileOpen = true">
                        <Icon name="menu" class="h-5 w-5" />
                    </button>
                    <h1 class="truncate text-lg font-semibold text-slate-900">{{ route.meta.title }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Shop switcher for owners -->
                    <label v-if="auth.isShopOwner && ownerShops.list.length" class="flex items-center gap-2 text-sm">
                        <span class="hidden text-slate-500 md:inline">Shop:</span>
                        <select v-model="currentShopId" class="input !w-auto !py-1.5">
                            <option v-for="shop in ownerShops.list" :key="shop.id" :value="shop.id">
                                {{ shop.name }}{{ shop.is_active ? '' : ' (inactive)' }}
                            </option>
                        </select>
                    </label>
                    <div class="hidden text-right text-sm leading-tight sm:block">
                        <p class="font-medium text-slate-800">{{ auth.user?.name }}</p>
                        <p class="text-xs text-slate-500">{{ auth.user?.email }}</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" @click="signOut">
                        <Icon name="logout" class="h-4 w-4" />
                        <span class="hidden sm:inline">Log out</span>
                    </button>
                </div>
            </header>

            <main class="mx-auto max-w-7xl p-4 sm:p-6">
                <RouterView />
            </main>
        </div>
    </div>
</template>

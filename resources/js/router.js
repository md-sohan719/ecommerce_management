import { createRouter, createWebHistory } from 'vue-router';
import { auth, homeRouteFor, initAuth } from './lib/auth';

import StoreLayout from './layouts/StoreLayout.vue';
import Home from './pages/store/Home.vue';
import ShopPage from './pages/store/ShopPage.vue';
import ProductPage from './pages/store/ProductPage.vue';
import NotFound from './pages/store/NotFound.vue';
import Login from './pages/Login.vue';

// Panels are loaded on demand so customers don't download admin code.
const DashboardLayout = () => import('./layouts/DashboardLayout.vue');

const routes = [
    {
        path: '/',
        component: StoreLayout,
        children: [
            { path: '', name: 'home', component: Home, meta: { title: 'Home' } },
            { path: 'shops/:slug', name: 'shop', component: ShopPage },
            { path: 'shops/:slug/products/:id', name: 'product', component: ProductPage },
            { path: ':pathMatch(.*)*', name: 'not-found', component: NotFound, meta: { title: 'Not found' } },
        ],
    },
    { path: '/login', name: 'login', component: Login, meta: { guest: true, title: 'Login' } },
    {
        path: '/admin',
        component: DashboardLayout,
        meta: { role: 'admin' },
        children: [
            { path: '', name: 'admin.dashboard', component: () => import('./pages/admin/Dashboard.vue'), meta: { title: 'Dashboard' } },
            { path: 'shops', name: 'admin.shops', component: () => import('./pages/admin/Shops.vue'), meta: { title: 'Shops' } },
            { path: 'products', name: 'admin.products', component: () => import('./pages/admin/Products.vue'), meta: { title: 'Products' } },
            { path: 'owners', name: 'admin.owners', component: () => import('./pages/admin/Owners.vue'), meta: { title: 'Shop owners' } },
        ],
    },
    {
        path: '/owner',
        component: DashboardLayout,
        meta: { role: 'shop_owner' },
        children: [
            { path: '', redirect: { name: 'owner.listings' } },
            { path: 'listings', name: 'owner.listings', component: () => import('./pages/owner/Listings.vue'), meta: { title: 'My shop products' } },
            { path: 'catalog', name: 'owner.catalog', component: () => import('./pages/owner/Catalog.vue'), meta: { title: 'Product catalog' } },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, saved) {
        if (saved) return saved;
        if (to.path !== from.path) return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    await initAuth();

    const role = to.matched.find((r) => r.meta.role)?.meta.role;

    if (role) {
        if (!auth.user) return { name: 'login', query: { redirect: to.fullPath } };
        if (auth.user.role !== role) return homeRouteFor(auth.user);
    }

    if (to.meta.guest && auth.user) return homeRouteFor(auth.user);
});

router.afterEach((to) => {
    const base = 'Small E-commerce';
    document.title = to.meta.title ? `${to.meta.title} · ${base}` : base;
});

export default router;

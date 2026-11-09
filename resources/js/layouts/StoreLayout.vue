<script setup>
import { auth, homeRouteFor } from '../lib/auth';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
                <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2 text-lg font-bold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-lg text-white">🛍️</span>
                    <span class="hidden sm:inline">Small E-commerce</span>
                </RouterLink>
                <nav class="flex items-center gap-2">
                    <RouterLink :to="{ name: 'home' }" class="btn btn-ghost">Shop</RouterLink>
                    <RouterLink v-if="auth.user" :to="homeRouteFor(auth.user)" class="btn btn-secondary">
                        {{ auth.isAdmin ? 'Admin panel' : 'My shop panel' }}
                    </RouterLink>
                    <RouterLink v-else :to="{ name: 'login' }" class="btn btn-secondary">Staff login</RouterLink>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <RouterView />
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:px-6">
                <p>© {{ new Date().getFullYear() }} Small E-commerce Management</p>
                <p>Prices are set by each shop and shown in BDT (৳).</p>
            </div>
        </footer>
    </div>
</template>

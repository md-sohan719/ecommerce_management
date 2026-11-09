<script setup>
defineProps({
    message: { type: String, default: 'The page you are looking for does not exist.' },
});
</script>

<template>
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <p class="text-7xl font-extrabold tracking-tight text-indigo-600">404</p>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Page not found</h1>
        <p class="mt-2 text-slate-600">{{ message }}</p>
        <RouterLink :to="{ name: 'home' }" class="btn btn-primary mt-8">Back to home</RouterLink>
    </div>
</template>

<script setup>
import Thumb from './Thumb.vue';
import { money } from '../lib/format';

defineProps({
    product: { type: Object, required: true }, // storefront product (a shop listing)
    showShop: { type: Boolean, default: true },
});
</script>

<template>
    <article class="group card flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
        <RouterLink :to="{ name: 'product', params: { slug: product.shop.slug, id: product.id } }" class="block">
            <Thumb :src="product.thumbnail_url" :alt="product.name" class="aspect-square w-full" rounded="rounded-none" />
        </RouterLink>
        <div class="flex flex-1 flex-col gap-1 p-4">
            <RouterLink
                v-if="showShop"
                :to="{ name: 'shop', params: { slug: product.shop.slug } }"
                class="text-xs font-medium text-indigo-600 hover:underline"
            >
                {{ product.shop.name }}
            </RouterLink>
            <RouterLink :to="{ name: 'product', params: { slug: product.shop.slug, id: product.id } }" class="line-clamp-2 font-medium text-slate-900 group-hover:text-indigo-700">
                {{ product.name }}
            </RouterLink>
            <p class="mt-auto pt-2 text-lg font-bold text-slate-900">{{ money(product.price) }}</p>
        </div>
    </article>
</template>

<script setup>
import Thumb from './Thumb.vue';
import { money } from '../lib/format';

defineProps({
    product: { type: Object, required: true }, // storefront product (a shop listing)
    showShop: { type: Boolean, default: true },
});
</script>

<template>
    <article class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-slate-300 hover:shadow-lg">
        <RouterLink :to="{ name: 'product', params: { slug: product.shop.slug, id: product.id } }" class="block overflow-hidden bg-slate-50">
            <Thumb :src="product.thumbnail_url" :alt="product.name" class="aspect-square w-full transition duration-300 group-hover:scale-[1.03]" rounded="rounded-none" />
        </RouterLink>
        <div class="flex flex-1 flex-col p-4">
            <RouterLink
                v-if="showShop"
                :to="{ name: 'shop', params: { slug: product.shop.slug } }"
                class="truncate text-xs font-medium tracking-wide text-slate-500 uppercase hover:text-indigo-600"
            >
                {{ product.shop.name }}
            </RouterLink>
            <RouterLink
                :to="{ name: 'product', params: { slug: product.shop.slug, id: product.id } }"
                class="mt-1 line-clamp-2 text-sm font-medium text-slate-900 hover:text-indigo-600"
            >
                {{ product.name }}
            </RouterLink>
            <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                <p class="text-base font-bold text-slate-900">{{ money(product.price) }}</p>
                <RouterLink
                    :to="{ name: 'product', params: { slug: product.shop.slug, id: product.id } }"
                    class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:border-indigo-600 hover:bg-indigo-600 hover:text-white"
                >
                    View
                </RouterLink>
            </div>
        </div>
    </article>
</template>

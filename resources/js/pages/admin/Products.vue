<script setup>
import { reactive } from 'vue';
import { api, errorMessage } from '../../lib/api';
import { money } from '../../lib/format';
import { toast } from '../../lib/toast';
import { useList } from '../../lib/useList';
import Modal from '../../components/Modal.vue';
import Pagination from '../../components/Pagination.vue';
import ProductForm from '../../components/ProductForm.vue';
import Thumb from '../../components/Thumb.vue';

const { items: products, meta, loading, filters, load } = useList('/admin/products', { created_by: '', status: '' });

const modal = reactive({ open: false, product: null, key: 0 });

function openForm(product = null) {
    modal.product = product;
    modal.key++;
    modal.open = true;
}

function saved() {
    modal.open = false;
    load();
}

async function remove(product) {
    const extra = product.listings_count ? ` It is listed in ${product.listings_count} shop(s) and will be removed from them.` : '';
    if (!confirm(`Delete "${product.name}" from the catalog?${extra}`)) return;
    try {
        await api.delete(`/admin/products/${product.id}`);
        toast.success('Product deleted.');
        load();
    } catch (e) {
        toast.error(errorMessage(e));
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                <input v-model="filters.search" type="search" class="input sm:!w-72" placeholder="Search name, SKU…" />
                <select v-model="filters.created_by" class="input sm:!w-48">
                    <option value="">Created by anyone</option>
                    <option value="admin">Created by admin</option>
                    <option value="shop_owner">Created by shop owners</option>
                </select>
                <select v-model="filters.status" class="input sm:!w-48">
                    <option value="">Any status</option>
                    <option value="listed">In at least one shop</option>
                    <option value="unlisted">Not in any shop</option>
                </select>
            </div>
            <button class="btn btn-primary" @click="openForm()">+ New product</button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Product</th>
                            <th class="th">Created by</th>
                            <th class="th">Base price</th>
                            <th class="th">Shops</th>
                            <th class="th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" :class="{ 'opacity-60': loading }">
                        <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50/60">
                            <td class="td">
                                <div class="flex items-center gap-3">
                                    <Thumb :src="product.thumbnail_url" :alt="product.name" class="h-12 w-12 shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ product.name }}</p>
                                        <p class="text-xs text-slate-500">{{ product.sku || 'No SKU' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="td">
                                <span v-if="!product.creator" class="text-slate-400">Deleted user</span>
                                <span v-else-if="product.creator.role === 'admin'" class="badge bg-indigo-50 text-indigo-700">Admin</span>
                                <span v-else class="badge bg-violet-50 text-violet-700">Owner · {{ product.creator.name }}</span>
                            </td>
                            <td class="td font-medium">{{ money(product.base_price) }}</td>
                            <td class="td">
                                <span v-if="product.listings_count" class="badge bg-emerald-50 text-emerald-700">{{ product.listings_count }} shop(s)</span>
                                <span v-else class="badge bg-amber-50 text-amber-700" title="Customers can't see products that aren't assigned to a shop">Not in any shop · hidden</span>
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-1">
                                    <button class="btn btn-secondary btn-sm" @click="openForm(product)">Edit</button>
                                    <button class="btn btn-ghost btn-sm text-rose-600" @click="remove(product)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!products.length && !loading">
                            <td colspan="5" class="td py-10 text-center text-slate-500">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pagination :meta="meta" @change="load" />

        <Modal :open="modal.open" :title="modal.product ? 'Edit product' : 'New product'" size="lg" @close="modal.open = false">
            <ProductForm :key="modal.key" :product="modal.product" mode="admin" @saved="saved" @cancel="modal.open = false" />
        </Modal>
    </div>
</template>

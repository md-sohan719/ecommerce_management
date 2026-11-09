<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api, errorMessage, validationErrors } from '../../lib/api';
import { toast } from '../../lib/toast';
import { useList } from '../../lib/useList';
import Modal from '../../components/Modal.vue';
import Pagination from '../../components/Pagination.vue';

const { items: shops, meta, loading, filters, load } = useList('/admin/shops', { owner_id: '' });

const owners = ref([]);

async function loadOwners() {
    try {
        const { data } = await api.get('/admin/owner-options');
        owners.value = data.data;
    } catch (e) {
        toast.error(errorMessage(e));
    }
}
onMounted(loadOwners);

// ----- Create / edit modal -----
const modal = reactive({ open: false, shop: null });
const form = reactive({});
const errors = ref({});
const busy = ref(false);

function openForm(shop = null) {
    modal.shop = shop;
    Object.assign(form, {
        name: shop?.name ?? '',
        owner_id: shop?.owner_id ?? '',
        address: shop?.address ?? '',
        phone: shop?.phone ?? '',
        description: shop?.description ?? '',
        is_active: shop?.is_active ?? true,
    });
    errors.value = {};
    modal.open = true;
}

async function save() {
    busy.value = true;
    errors.value = {};
    const payload = { ...form, owner_id: form.owner_id || null };
    try {
        if (modal.shop) await api.put(`/admin/shops/${modal.shop.id}`, payload);
        else await api.post('/admin/shops', payload);
        toast.success(modal.shop ? 'Shop updated.' : 'Shop created.');
        modal.open = false;
        load();
    } catch (e) {
        errors.value = validationErrors(e);
        toast.error(errorMessage(e));
    } finally {
        busy.value = false;
    }
}

async function remove(shop) {
    if (!confirm(`Delete "${shop.name}"? Its ${shop.listings_count} product listing(s) will be removed from the shop (catalog products are kept).`)) return;
    try {
        await api.delete(`/admin/shops/${shop.id}`);
        toast.success('Shop deleted.');
        load();
    } catch (e) {
        toast.error(errorMessage(e));
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                <input v-model="filters.search" type="search" class="input sm:!w-72" placeholder="Search shops…" />
                <select v-model="filters.owner_id" class="input sm:!w-56">
                    <option value="">All owners</option>
                    <option v-for="o in owners" :key="o.id" :value="o.id">{{ o.name }}</option>
                </select>
            </div>
            <button class="btn btn-primary" @click="openForm()">+ New shop</button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Shop</th>
                            <th class="th">Owner</th>
                            <th class="th">Products</th>
                            <th class="th">Status</th>
                            <th class="th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" :class="{ 'opacity-60': loading }">
                        <tr v-for="shop in shops" :key="shop.id" class="hover:bg-slate-50/60">
                            <td class="td">
                                <p class="font-medium text-slate-900">{{ shop.name }}</p>
                                <p class="text-xs text-slate-500">{{ shop.address || '—' }}</p>
                            </td>
                            <td class="td">
                                <template v-if="shop.owner">
                                    <p class="text-slate-800">{{ shop.owner.name }}</p>
                                    <p class="text-xs text-slate-500">{{ shop.owner.email }}</p>
                                </template>
                                <span v-else class="badge bg-amber-50 text-amber-700">No owner</span>
                            </td>
                            <td class="td">{{ shop.listings_count }}</td>
                            <td class="td">
                                <span class="badge" :class="shop.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                    {{ shop.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-1">
                                    <RouterLink v-if="shop.is_active" :to="{ name: 'shop', params: { slug: shop.slug } }" target="_blank" class="btn btn-ghost btn-sm">View</RouterLink>
                                    <button class="btn btn-secondary btn-sm" @click="openForm(shop)">Edit</button>
                                    <button class="btn btn-ghost btn-sm text-rose-600" @click="remove(shop)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!shops.length && !loading">
                            <td colspan="5" class="td py-10 text-center text-slate-500">No shops found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pagination :meta="meta" @change="load" />

        <Modal :open="modal.open" :title="modal.shop ? 'Edit shop' : 'New shop'" @close="modal.open = false">
            <form @submit.prevent="save">
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="label" for="s-name">Name *</label>
                        <input id="s-name" v-model="form.name" class="input" required maxlength="255" />
                        <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="s-owner">Shop owner</label>
                        <select id="s-owner" v-model="form.owner_id" class="input">
                            <option value="">— No owner —</option>
                            <option v-for="o in owners" :key="o.id" :value="o.id">{{ o.name }} ({{ o.email }})</option>
                        </select>
                        <p class="hint">
                            The owner manages this shop's products.
                            <RouterLink :to="{ name: 'admin.owners' }" class="text-indigo-600 hover:underline">Create a shop owner account</RouterLink>
                        </p>
                        <p v-if="errors.owner_id" class="field-error">{{ errors.owner_id[0] }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="s-address">Address</label>
                            <input id="s-address" v-model="form.address" class="input" maxlength="255" />
                            <p v-if="errors.address" class="field-error">{{ errors.address[0] }}</p>
                        </div>
                        <div>
                            <label class="label" for="s-phone">Phone</label>
                            <input id="s-phone" v-model="form.phone" class="input" maxlength="30" />
                            <p v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="s-desc">Description</label>
                        <textarea id="s-desc" v-model="form.description" rows="3" class="input" maxlength="5000" />
                        <p v-if="errors.description" class="field-error">{{ errors.description[0] }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded" />
                        Active (visible to customers)
                    </label>
                </div>
                <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" class="btn btn-secondary" @click="modal.open = false">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="busy">{{ busy ? 'Saving…' : 'Save' }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>

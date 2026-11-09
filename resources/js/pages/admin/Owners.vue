<script setup>
import { reactive, ref } from 'vue';
import { api, errorMessage, validationErrors } from '../../lib/api';
import { date } from '../../lib/format';
import { toast } from '../../lib/toast';
import { useList } from '../../lib/useList';
import Modal from '../../components/Modal.vue';
import Pagination from '../../components/Pagination.vue';

const { items: owners, meta, loading, filters, load } = useList('/admin/owners');

const modal = reactive({ open: false, owner: null });
const form = reactive({ name: '', email: '', password: '' });
const errors = ref({});
const busy = ref(false);

function openForm(owner = null) {
    modal.owner = owner;
    Object.assign(form, { name: owner?.name ?? '', email: owner?.email ?? '', password: '' });
    errors.value = {};
    modal.open = true;
}

async function save() {
    busy.value = true;
    errors.value = {};
    try {
        if (modal.owner) await api.put(`/admin/owners/${modal.owner.id}`, form);
        else await api.post('/admin/owners', form);
        toast.success(modal.owner ? 'Shop owner updated.' : 'Shop owner account created.');
        modal.open = false;
        load();
    } catch (e) {
        errors.value = validationErrors(e);
        toast.error(errorMessage(e));
    } finally {
        busy.value = false;
    }
}

async function remove(owner) {
    const extra = owner.shops_count ? ` Their ${owner.shops_count} shop(s) will be kept without an owner.` : '';
    if (!confirm(`Delete the account of ${owner.name}?${extra}`)) return;
    try {
        await api.delete(`/admin/owners/${owner.id}`);
        toast.success('Shop owner deleted.');
        load();
    } catch (e) {
        toast.error(errorMessage(e));
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input v-model="filters.search" type="search" class="input sm:!w-72" placeholder="Search name or email…" />
            <button class="btn btn-primary" @click="openForm()">+ New shop owner</button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Name</th>
                            <th class="th">Email</th>
                            <th class="th">Shops</th>
                            <th class="th">Created</th>
                            <th class="th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" :class="{ 'opacity-60': loading }">
                        <tr v-for="owner in owners" :key="owner.id" class="hover:bg-slate-50/60">
                            <td class="td font-medium text-slate-900">{{ owner.name }}</td>
                            <td class="td text-slate-600">{{ owner.email }}</td>
                            <td class="td">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="shop in owner.shops" :key="shop.id" class="badge bg-slate-100 text-slate-700">🏪 {{ shop.name }}</span>
                                    <span v-if="!owner.shops?.length" class="text-xs text-slate-400">No shop assigned</span>
                                </div>
                            </td>
                            <td class="td text-slate-500">{{ date(owner.created_at) }}</td>
                            <td class="td">
                                <div class="flex justify-end gap-1">
                                    <button class="btn btn-secondary btn-sm" @click="openForm(owner)">Edit</button>
                                    <button class="btn btn-ghost btn-sm text-rose-600" @click="remove(owner)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!owners.length && !loading">
                            <td colspan="5" class="td py-10 text-center text-slate-500">No shop owners found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pagination :meta="meta" @change="load" />

        <Modal :open="modal.open" :title="modal.owner ? 'Edit shop owner' : 'New shop owner'" size="sm" @close="modal.open = false">
            <form @submit.prevent="save">
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="label" for="o-name">Name *</label>
                        <input id="o-name" v-model="form.name" class="input" required maxlength="255" />
                        <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="o-email">Email *</label>
                        <input id="o-email" v-model="form.email" type="email" class="input" required maxlength="255" autocomplete="off" />
                        <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="o-password">{{ modal.owner ? 'New password' : 'Password *' }}</label>
                        <input id="o-password" v-model="form.password" type="password" class="input" :required="!modal.owner" minlength="8" autocomplete="new-password" />
                        <p class="hint">{{ modal.owner ? 'Leave empty to keep the current password.' : 'At least 8 characters.' }}</p>
                        <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
                    </div>
                    <p class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Assign shops to this owner from the <b>Shops</b> page.</p>
                </div>
                <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" class="btn btn-secondary" @click="modal.open = false">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="busy">{{ busy ? 'Saving…' : 'Save' }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { api, errorMessage, toFormData, validationErrors } from '../lib/api';
import { toast } from '../lib/toast';
import ImageInput from './ImageInput.vue';

/**
 * Create / edit a catalog product.
 *  mode "admin": /api/admin/products
 *  mode "owner": /api/owner/products (and optionally list it in one of the owner's shops right away)
 */
const props = defineProps({
    product: { type: Object, default: null },
    mode: { type: String, default: 'admin' },
    shops: { type: Array, default: () => [] },
    defaultShopId: { type: Number, default: null },
});

const emit = defineEmits(['saved', 'cancel']);

const editing = computed(() => !!props.product);

const form = reactive({
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    description: props.product?.description ?? '',
    base_price: props.product?.base_price ?? '',
    thumbnail: null,
    remove_thumbnail: false,
    // owner only, when creating
    list_in_shop: props.mode === 'owner' && props.shops.length > 0,
    shop_id: props.defaultShopId ?? props.shops[0]?.id ?? null,
    price: '',
});

const errors = ref({});
const busy = ref(false);

async function submit() {
    busy.value = true;
    errors.value = {};

    const payload = {
        name: form.name,
        sku: form.sku,
        description: form.description,
        base_price: form.base_price,
        thumbnail: form.thumbnail ?? undefined,
        remove_thumbnail: form.remove_thumbnail,
    };

    if (props.mode === 'owner' && !editing.value && form.list_in_shop && form.shop_id) {
        payload.shop_id = form.shop_id;
        payload.price = form.price === '' ? undefined : form.price;
    }

    const base = props.mode === 'admin' ? '/admin/products' : '/owner/products';
    const url = editing.value ? `${base}/${props.product.id}` : base;

    try {
        const { data } = await api.post(url, toFormData(payload, editing.value ? 'PUT' : null));
        toast.success(editing.value ? 'Product updated.' : 'Product created and added to the shared catalog.');
        emit('saved', data.data);
    } catch (e) {
        errors.value = validationErrors(e);
        toast.error(errorMessage(e));
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <form @submit.prevent="submit">
        <div class="space-y-4 px-6 py-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="label" for="p-name">Name *</label>
                    <input id="p-name" v-model="form.name" class="input" required maxlength="255" />
                    <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
                </div>
                <div>
                    <label class="label" for="p-sku">SKU</label>
                    <input id="p-sku" v-model="form.sku" class="input" maxlength="64" placeholder="Optional" />
                    <p v-if="errors.sku" class="field-error">{{ errors.sku[0] }}</p>
                </div>
            </div>

            <div>
                <label class="label" for="p-price">Base price (৳) *</label>
                <input id="p-price" v-model="form.base_price" type="number" min="0" step="0.01" class="input sm:!w-48" required />
                <p class="hint">Suggested price. Each shop sets its own selling price.</p>
                <p v-if="errors.base_price" class="field-error">{{ errors.base_price[0] }}</p>
            </div>

            <div>
                <label class="label" for="p-desc">Description</label>
                <textarea id="p-desc" v-model="form.description" rows="4" class="input" maxlength="5000" />
                <p v-if="errors.description" class="field-error">{{ errors.description[0] }}</p>
            </div>

            <div>
                <span class="label">Thumbnail</span>
                <ImageInput
                    v-model="form.thumbnail"
                    v-model:remove="form.remove_thumbnail"
                    :current-url="product?.thumbnail_url"
                    :alt="form.name"
                />
                <p v-if="errors.thumbnail" class="field-error">{{ errors.thumbnail[0] }}</p>
            </div>

            <!-- Shop owner: list the new product in one of their shops right away -->
            <div v-if="mode === 'owner' && !editing" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
                    <input v-model="form.list_in_shop" type="checkbox" class="h-4 w-4 rounded" :disabled="!shops.length" />
                    Also add it to my shop now
                </label>
                <p v-if="!shops.length" class="hint">You don't have a shop yet — the product will only be added to the shared catalog.</p>
                <div v-if="form.list_in_shop && shops.length" class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="p-shop">Shop</label>
                        <select id="p-shop" v-model="form.shop_id" class="input">
                            <option v-for="s in shops" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="errors.shop_id" class="field-error">{{ errors.shop_id[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="p-shop-price">Selling price in this shop (৳)</label>
                        <input id="p-shop-price" v-model="form.price" type="number" min="0" step="0.01" class="input" :placeholder="form.base_price ? `${form.base_price} (base price)` : 'Base price'" />
                        <p v-if="errors.price" class="field-error">{{ errors.price[0] }}</p>
                    </div>
                </div>
            </div>
            <p v-if="mode === 'owner' && !editing" class="hint">
                Products you create are added to the shared catalog so other shop owners can sell them too.
            </p>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" class="btn btn-secondary" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="busy">{{ busy ? 'Saving…' : editing ? 'Save changes' : 'Create product' }}</button>
        </div>
    </form>
</template>

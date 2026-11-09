<script setup>
import { computed, reactive, ref } from 'vue';
import { api, errorMessage, toFormData, validationErrors } from '../lib/api';
import { money } from '../lib/format';
import { toast } from '../lib/toast';
import ImageInput from './ImageInput.vue';
import Thumb from './Thumb.vue';

/**
 * Add a catalog product to a shop, or edit an existing listing:
 * shop-specific price plus optional overrides for title, details and thumbnail.
 */
const props = defineProps({
    shopId: { type: Number, required: true },
    shopName: { type: String, default: '' },
    product: { type: Object, default: null }, // catalog product (when adding)
    listing: { type: Object, default: null }, // existing listing (when editing)
});

const emit = defineEmits(['saved', 'cancel']);

const editing = computed(() => !!props.listing);
const catalog = computed(() => props.listing?.product ?? props.product);

const form = reactive({
    price: props.listing?.price ?? catalog.value?.base_price ?? '',
    title: props.listing?.title ?? '',
    description: props.listing?.description ?? '',
    is_active: props.listing?.is_active ?? true,
    thumbnail: null,
    remove_thumbnail: false,
});

const errors = ref({});
const busy = ref(false);

async function submit() {
    busy.value = true;
    errors.value = {};

    const payload = {
        price: form.price,
        title: form.title,
        description: form.description,
        is_active: form.is_active,
        thumbnail: form.thumbnail ?? undefined,
        remove_thumbnail: form.remove_thumbnail,
    };

    const base = `/owner/shops/${props.shopId}/listings`;

    try {
        let response;
        if (editing.value) {
            response = await api.post(`${base}/${props.listing.id}`, toFormData(payload, 'PUT'));
        } else {
            response = await api.post(base, toFormData({ ...payload, product_id: catalog.value.id }));
        }
        toast.success(editing.value ? 'Listing updated.' : `Added to ${props.shopName || 'your shop'}.`);
        emit('saved', response.data.data);
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
        <div class="space-y-5 px-6 py-5">
            <!-- Catalog product summary -->
            <div v-if="catalog" class="flex gap-4 rounded-xl bg-slate-50 p-4">
                <Thumb :src="catalog.thumbnail_url" :alt="catalog.name" class="h-16 w-16 shrink-0" />
                <div class="min-w-0 text-sm">
                    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Catalog product</p>
                    <p class="truncate font-semibold text-slate-900">{{ catalog.name }}</p>
                    <p class="text-slate-500">
                        Base price {{ money(catalog.base_price) }}
                        <template v-if="catalog.creator"> · by {{ catalog.creator.role === 'admin' ? 'Admin' : catalog.creator.name }}</template>
                    </p>
                </div>
            </div>
            <p v-if="errors.product_id" class="field-error">{{ errors.product_id[0] }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="l-price">Your price (৳) *</label>
                    <input id="l-price" v-model="form.price" type="number" min="0" step="0.01" class="input" required />
                    <p v-if="errors.price" class="field-error">{{ errors.price[0] }}</p>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 pb-2 text-sm font-medium text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded" />
                        Visible to customers
                    </label>
                </div>
            </div>

            <div>
                <label class="label" for="l-title">Title in your shop</label>
                <input id="l-title" v-model="form.title" class="input" maxlength="255" :placeholder="catalog?.name" />
                <p class="hint">Leave empty to use the catalog name.</p>
                <p v-if="errors.title" class="field-error">{{ errors.title[0] }}</p>
            </div>

            <div>
                <label class="label" for="l-desc">Details in your shop</label>
                <textarea id="l-desc" v-model="form.description" rows="4" class="input" maxlength="5000" :placeholder="catalog?.description || 'Product details'" />
                <p class="hint">Leave empty to use the catalog description.</p>
                <p v-if="errors.description" class="field-error">{{ errors.description[0] }}</p>
            </div>

            <div>
                <span class="label">Thumbnail in your shop</span>
                <ImageInput
                    v-model="form.thumbnail"
                    v-model:remove="form.remove_thumbnail"
                    :current-url="listing?.thumbnail_url"
                    :fallback-url="catalog?.thumbnail_url"
                    fallback-label="Using the catalog thumbnail. Upload one to use a different image in your shop."
                    :alt="form.title || catalog?.name"
                />
                <p v-if="errors.thumbnail" class="field-error">{{ errors.thumbnail[0] }}</p>
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" class="btn btn-secondary" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="busy">{{ busy ? 'Saving…' : editing ? 'Save changes' : 'Add to shop' }}</button>
        </div>
    </form>
</template>

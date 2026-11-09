<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Thumb from './Thumb.vue';

/**
 * Thumbnail picker with preview.
 *  - modelValue: the newly chosen File (or null)
 *  - remove: whether the existing image should be removed
 *  - currentUrl: the image currently saved
 *  - fallbackUrl: what is shown when there is no image of its own (e.g. the catalog thumbnail for a listing)
 */
const props = defineProps({
    modelValue: { default: null }, // File | null
    remove: { type: Boolean, default: false },
    currentUrl: { type: String, default: null },
    fallbackUrl: { type: String, default: null },
    fallbackLabel: { type: String, default: '' },
    alt: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'update:remove']);

const input = ref(null);
const objectUrl = ref(null);

watch(
    () => props.modelValue,
    (file) => {
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = file ? URL.createObjectURL(file) : null;
    },
    { immediate: true },
);

onBeforeUnmount(() => objectUrl.value && URL.revokeObjectURL(objectUrl.value));

const hasOwn = computed(() => !!props.currentUrl && !props.remove);
const preview = computed(() => objectUrl.value || (hasOwn.value ? props.currentUrl : props.fallbackUrl));
const usingFallback = computed(() => !objectUrl.value && !hasOwn.value && !!props.fallbackUrl);

function pick(e) {
    const file = e.target.files?.[0] ?? null;
    emit('update:modelValue', file);
    if (file) emit('update:remove', false);
    e.target.value = '';
}
</script>

<template>
    <div class="flex items-start gap-4">
        <Thumb :src="preview" :alt="alt" class="h-24 w-24 shrink-0 border border-slate-200" rounded="rounded-xl" />
        <div class="space-y-2 text-sm">
            <input ref="input" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" @change="pick" />
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary btn-sm" @click="input.click()">
                    {{ modelValue || hasOwn ? 'Change image' : 'Upload image' }}
                </button>
                <button v-if="modelValue" type="button" class="btn btn-ghost btn-sm" @click="emit('update:modelValue', null)">Cancel new image</button>
                <button v-else-if="hasOwn" type="button" class="btn btn-ghost btn-sm text-rose-600" @click="emit('update:remove', true)">Remove</button>
                <button v-else-if="remove" type="button" class="btn btn-ghost btn-sm" @click="emit('update:remove', false)">Undo remove</button>
            </div>
            <p class="hint">
                <template v-if="modelValue">New image: {{ modelValue.name }}</template>
                <template v-else-if="usingFallback">{{ fallbackLabel || 'Using the default image.' }}</template>
                <template v-else>JPG, PNG, WEBP or GIF, up to 2&nbsp;MB.</template>
            </p>
        </div>
    </div>
</template>

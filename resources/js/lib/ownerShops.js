import { reactive } from 'vue';
import { api } from './api';

const KEY = 'ecom_owner_shop';

function stored() {
    try {
        return Number(localStorage.getItem(KEY)) || null;
    } catch {
        return null;
    }
}

/** The authenticated shop owner's shops and the currently selected one. */
export const ownerShops = reactive({
    list: [],
    currentId: stored(),
    loaded: false,

    get current() {
        return this.list.find((shop) => shop.id === this.currentId) ?? null;
    },
});

export async function loadOwnerShops() {
    const { data } = await api.get('/owner/shops');
    ownerShops.list = data.data;

    if (!ownerShops.list.some((shop) => shop.id === ownerShops.currentId)) {
        selectShop(ownerShops.list[0]?.id ?? null);
    }
    ownerShops.loaded = true;
}

let loading = null;

/** Load the owner's shops once (shared by the layout and the pages). */
export function ensureOwnerShops() {
    if (ownerShops.loaded) return Promise.resolve();
    if (!loading) {
        loading = loadOwnerShops().finally(() => {
            loading = null;
        });
    }
    return loading;
}

export function selectShop(id) {
    ownerShops.currentId = id;
    try {
        id ? localStorage.setItem(KEY, String(id)) : localStorage.removeItem(KEY);
    } catch {
        /* storage unavailable */
    }
}

export function resetOwnerShops() {
    ownerShops.list = [];
    ownerShops.loaded = false;
}

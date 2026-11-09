import { reactive } from 'vue';
import { api, tokenStore } from './api';

export const auth = reactive({
    user: null,
    ready: false,

    get isAdmin() {
        return this.user?.role === 'admin';
    },
    get isShopOwner() {
        return this.user?.role === 'shop_owner';
    },
});

let initPromise = null;

/** Restore the session from a stored token (runs once). */
export function initAuth() {
    if (!initPromise) {
        initPromise = (async () => {
            if (tokenStore.get()) {
                try {
                    const { data } = await api.get('/me');
                    auth.user = data.data;
                } catch {
                    clearSession();
                }
            }
            auth.ready = true;
        })();
    }
    return initPromise;
}

export async function login(email, password) {
    const { data } = await api.post('/login', { email, password });
    tokenStore.set(data.token);
    auth.user = data.user;
    return data.user;
}

export async function logout() {
    try {
        await api.post('/logout');
    } catch {
        /* token may already be invalid */
    }
    clearSession();
}

export function clearSession() {
    tokenStore.set(null);
    auth.user = null;
}

/** Where a user lands after logging in. */
export function homeRouteFor(user) {
    if (user?.role === 'admin') return { name: 'admin.dashboard' };
    if (user?.role === 'shop_owner') return { name: 'owner.listings' };
    return { name: 'home' };
}

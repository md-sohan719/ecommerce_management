import axios from 'axios';

const TOKEN_KEY = 'ecom_token';

export const tokenStore = {
    get() {
        try {
            return localStorage.getItem(TOKEN_KEY);
        } catch {
            return null;
        }
    },
    set(token) {
        try {
            token ? localStorage.setItem(TOKEN_KEY, token) : localStorage.removeItem(TOKEN_KEY);
        } catch {
            /* storage unavailable */
        }
    },
};

export const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const token = tokenStore.get();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

let unauthorizedHandler = null;

export function onUnauthorized(handler) {
    unauthorizedHandler = handler;
}

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && unauthorizedHandler) {
            unauthorizedHandler(error);
        }
        return Promise.reject(error);
    },
);

/**
 * Build multipart form data (for thumbnail uploads).
 * Booleans become "1"/"0", null becomes "" (Laravel converts it back to null),
 * undefined values are skipped. `method` adds Laravel's _method spoofing (PUT).
 */
export function toFormData(payload, method = null) {
    const form = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined) return;
        if (value === null) form.append(key, '');
        else if (typeof value === 'boolean') form.append(key, value ? '1' : '0');
        else form.append(key, value);
    });

    if (method) form.append('_method', method);

    return form;
}

/** Laravel validation errors ({ field: [messages] }) from a 422 response. */
export function validationErrors(error) {
    return error?.response?.status === 422 ? error.response.data.errors ?? {} : {};
}

/** A human readable error message from an API error. */
export function errorMessage(error) {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }
    return data?.message || error?.message || 'Something went wrong.';
}

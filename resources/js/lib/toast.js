import { reactive } from 'vue';

export const toasts = reactive([]);

let nextId = 1;

function push(type, message, timeout = 3500) {
    const id = nextId++;
    toasts.push({ id, type, message });
    setTimeout(() => dismiss(id), timeout);
}

export function dismiss(id) {
    const index = toasts.findIndex((t) => t.id === id);
    if (index !== -1) toasts.splice(index, 1);
}

export const toast = {
    success: (message) => push('success', message),
    error: (message) => push('error', message, 6000),
};

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { errorMessage, validationErrors } from '../lib/api';
import { homeRouteFor, login } from '../lib/auth';

const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });
const errors = ref({});
const message = ref('');
const busy = ref(false);

const demoAccounts = [
    { label: 'Admin', email: 'admin@example.com' },
    { label: 'Shop owner (2 shops)', email: 'rahim@example.com' },
    { label: 'Shop owner', email: 'nusrat@example.com' },
];

function fill(email) {
    form.email = email;
    form.password = 'password';
}

async function submit() {
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        const user = await login(form.email, form.password);
        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
        const home = homeRouteFor(user);
        // Only follow the redirect if it belongs to this user's panel.
        const prefix = user.role === 'admin' ? '/admin' : '/owner';
        router.replace(redirect && redirect.startsWith(prefix) ? redirect : home);
    } catch (e) {
        errors.value = validationErrors(e);
        if (!Object.keys(errors.value).length) message.value = errorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-violet-50 px-4 py-12">
        <div class="w-full max-w-md">
            <RouterLink :to="{ name: 'home' }" class="mb-6 flex items-center justify-center gap-2 text-lg font-bold text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-xl text-white">🛍️</span>
                Small E-commerce
            </RouterLink>

            <div class="card p-8">
                <h1 class="text-xl font-semibold text-slate-900">Sign in</h1>
                <p class="mt-1 text-sm text-slate-500">For administrators and shop owners.</p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <p v-if="message" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ message }}</p>
                    <div>
                        <label class="label" for="email">Email</label>
                        <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" required autofocus />
                        <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="password">Password</label>
                        <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required />
                        <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-full" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
                </form>
            </div>

            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white/60 p-4 text-sm">
                <p class="font-medium text-slate-700">Demo accounts <span class="font-normal text-slate-500">(password: <code>password</code>)</span></p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button v-for="a in demoAccounts" :key="a.email" type="button" class="btn btn-secondary btn-sm" @click="fill(a.email)">
                        {{ a.label }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

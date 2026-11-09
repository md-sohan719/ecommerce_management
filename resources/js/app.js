import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { onUnauthorized } from './lib/api';
import { clearSession } from './lib/auth';
import { resetOwnerShops } from './lib/ownerShops';

// If the API says our token is no longer valid, drop the session and go to login.
onUnauthorized(() => {
    clearSession();
    resetOwnerShops();
    const current = router.currentRoute.value;
    if (current.matched.some((r) => r.meta.role)) {
        router.push({ name: 'login', query: { redirect: current.fullPath } });
    }
});

createApp(App).use(router).mount('#app');

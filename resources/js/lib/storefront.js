import { api } from './api';

// The store header (shop menu) and the pages both need the shop list. Share one
// request, and reuse the result for a short time, instead of fetching it twice.
const MAX_AGE = 30_000;

let cached = null;
let cachedAt = 0;

function remember(promise) {
    cachedAt = Date.now();
    cached = promise.catch((error) => {
        cached = null;
        throw error;
    });
    cached.catch(() => {}); // callers handle the error; avoid an "unhandled rejection" warning

    return cached;
}

export function fetchShops() {
    if (!cached || Date.now() - cachedAt > MAX_AGE) {
        remember(api.get('/storefront/shops').then(({ data }) => data.data));
    }

    return cached;
}

/**
 * All home page data in one request (banner, shops, first page of products).
 * The shop list from this response is also used by the header, so the header
 * menu and the page sections appear at the same moment.
 */
export function fetchHome(params = {}) {
    const request = api.get('/storefront/home', { params }).then(({ data }) => data);
    remember(request.then((data) => data.shops));

    return request;
}

import { reactive, ref, watch } from 'vue';
import { api, errorMessage } from './api';
import { debounce } from './format';
import { toast } from './toast';

/**
 * Paginated, filterable list backed by a Laravel API resource collection.
 *
 *   const { items, meta, loading, filters, load } = useList('/admin/shops', { status: '' });
 *
 * `url` may be a function (e.g. depends on the selected shop). Changing `filters.search`
 * reloads with a debounce, other filters reload immediately.
 */
export function useList(url, initialFilters = {}, { perPage = 10, immediate = true } = {}) {
    const items = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const page = ref(1);
    const filters = reactive({ search: '', ...initialFilters });

    async function load(target = page.value) {
        const endpoint = typeof url === 'function' ? url() : url;
        if (!endpoint) {
            items.value = [];
            meta.value = null;
            return;
        }

        page.value = target;
        loading.value = true;

        const params = { page: page.value, per_page: perPage };
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined && value !== false) params[key] = value;
        });

        try {
            const { data } = await api.get(endpoint, { params });
            // e.g. after deleting the last item of the last page
            if (!data.data.length && page.value > 1) return load(page.value - 1);
            items.value = data.data;
            meta.value = data.meta;
        } catch (e) {
            toast.error(errorMessage(e));
        } finally {
            loading.value = false;
        }
    }

    watch(
        () => filters.search,
        debounce(() => load(1), 300),
    );

    watch(
        () => JSON.stringify({ ...filters, search: undefined }),
        () => load(1),
    );

    if (immediate) load(1);

    return { items, meta, loading, filters, page, load };
}

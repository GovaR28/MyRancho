import { router, usePage } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

/**
 * Estado de búsqueda y filtros de una lista, sincronizado con la URL.
 *
 * @param {string} endpoint Ruta de la lista, ej. "/animales".
 * @param {string[]} keys Claves de filtro soportadas por la lista.
 */
export function useListFilters(endpoint, keys = []) {
    const page = usePage();

    const readUrl = () => {
        const query = new URLSearchParams(page.url.split('?')[1] || '');

        return {
            q: query.get('q') || '',
            filters: Object.fromEntries(keys.map((key) => [key, query.get(key) || ''])),
        };
    };

    const initial = readUrl();
    const search = ref(initial.q);
    const filters = reactive({ ...initial.filters });

    const buildParams = (overrides = {}) => {
        const merged = { q: search.value, ...filters, ...overrides };
        const params = {};

        for (const [key, value] of Object.entries(merged)) {
            if (value !== '' && value !== null && value !== undefined) params[key] = value;
        }

        return params;
    };

    const visit = (params) => {
        router.get(endpoint, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const updateSearch = (term) => {
        search.value = term;
        visit(buildParams());
    };

    const applyFilters = (values) => {
        for (const key of keys) filters[key] = values[key] ?? '';
        visit(buildParams());
    };

    const clearFilters = () => {
        for (const key of keys) filters[key] = '';
        visit(buildParams());
    };

    // Botón atrás/adelante del navegador: resincroniza desde la URL.
    watch(
        () => page.url,
        () => {
            const current = readUrl();
            search.value = current.q;
            for (const key of keys) filters[key] = current.filters[key];
        },
    );

    return { search, filters, updateSearch, applyFilters, clearFilters };
}

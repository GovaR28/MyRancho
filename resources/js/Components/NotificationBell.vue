<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const open = ref(false);
const loading = ref(false);
const busyKey = ref(null);
const count = ref(0);
const items = ref([]);
const truncated = ref(false);
const menu = ref(null);

function apply(data) {
    count.value = data?.count ?? 0;
    items.value = data?.items ?? [];
    truncated.value = data?.truncated ?? false;
}

async function load() {
    loading.value = true;

    try {
        const { data } = await axios.get('/alertas', { headers: { Accept: 'application/json' } });
        apply(data);
    } catch (error) {
        // Si falla, se mantiene lo que ya había.
    } finally {
        loading.value = false;
    }
}

async function dismiss(item) {
    if (busyKey.value) return;

    busyKey.value = item.key;

    try {
        const { data } = await axios.post('/alertas/ocultar', { key: item.key });
        apply(data);
    } catch (error) {
        // sin cambios
    } finally {
        busyKey.value = null;
    }
}

async function dismissAll() {
    if (busyKey.value === 'all') return;

    busyKey.value = 'all';

    try {
        const { data } = await axios.post('/alertas/ocultar-todas', {});
        apply(data);
    } catch (error) {
        // sin cambios
    } finally {
        busyKey.value = null;
    }
}

function toggle() {
    open.value = !open.value;

    if (open.value) load();
}

function close() {
    open.value = false;
}

function onDocumentClick(event) {
    if (open.value && menu.value && !menu.value.contains(event.target)) close();
}

function onKeydown(event) {
    if (event.key === 'Escape') close();
}

function formatDate(value) {
    if (!value) return '';

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(() => {
    load();
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="menu" class="relative">
        <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600 transition hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800"
            :aria-expanded="open"
            aria-haspopup="true"
            aria-label="Notificaciones"
            @click="toggle"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="count > 0"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold leading-none text-white"
            >
                {{ count > 99 ? '99+' : count }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-800 dark:bg-stone-900"
            role="menu"
        >
            <div class="flex items-center justify-between gap-2 border-b border-stone-100 px-4 py-3 dark:border-stone-800">
                <p class="text-sm font-semibold text-stone-900 dark:text-stone-100">Notificaciones</p>
                <button
                    v-if="count > 0"
                    type="button"
                    :disabled="busyKey === 'all'"
                    class="text-xs font-medium text-green-800 hover:underline disabled:opacity-50 dark:text-green-400"
                    @click="dismissAll"
                >
                    Marcar todas como vistas
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <p v-if="loading && !items.length" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">Cargando…</p>

                <p v-else-if="!items.length" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
                    No tienes avisos pendientes.
                </p>

                <ul v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                    <li v-for="item in items" :key="item.key" class="flex items-start gap-3 px-4 py-3 hover:bg-stone-50 dark:hover:bg-stone-800/60">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <Link :href="item.url" class="min-w-0 flex-1" role="menuitem" @click="close">
                            <span class="block truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ item.title }}</span>
                            <span class="mt-0.5 block text-xs text-stone-600 dark:text-stone-400">{{ item.message }}</span>
                            <span v-if="item.date" class="mt-1 block text-[11px] uppercase tracking-wide text-stone-400 dark:text-stone-500">{{ formatDate(item.date) }}</span>
                        </Link>
                        <button
                            type="button"
                            :disabled="busyKey === item.key"
                            class="mt-0.5 rounded-md px-2 py-1 text-lg leading-none text-stone-400 hover:bg-stone-200 hover:text-stone-700 disabled:opacity-50 dark:hover:bg-stone-700 dark:hover:text-stone-200"
                            :aria-label="`Ocultar aviso`"
                            title="Ocultar"
                            @click="dismiss(item)"
                        >
                            ×
                        </button>
                    </li>
                </ul>

                <p v-if="truncated" class="border-t border-stone-100 px-4 py-2.5 text-center text-xs text-stone-500 dark:border-stone-800 dark:text-stone-400">
                    <Link href="/animales?foto=pendiente" class="font-medium text-green-800 hover:underline dark:text-green-400" @click="close">Ver todos los pendientes</Link>
                </p>
            </div>
        </div>
    </div>
</template>

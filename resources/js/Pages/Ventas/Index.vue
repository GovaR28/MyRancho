<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ListToolbar from '../../Components/ListToolbar.vue';
import HeaderActions from '../../Components/HeaderActions.vue';
import { useListFilters } from '../../Composables/useListFilters';
import { formatDate, formatMoney } from '../../Composables/usePreferences';

defineProps({
    ventas: { type: Array, required: true },
});

const page = usePage();

const { search, filters, updateSearch, applyFilters, clearFilters } = useListFilters('/ventas', ['desde', 'hasta']);

const listFilters = computed(() => [
    { key: 'desde', label: 'Desde', type: 'date' },
    { key: 'hasta', label: 'Hasta', type: 'date' },
]);
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
                <HeaderActions />
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <Link href="/dashboard" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Panel principal</Link>
            <div class="mt-5">
                <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-100">Ventas y salidas</h1>
                <p class="mt-1 text-stone-600 dark:text-stone-400">Historial de animales vendidos individualmente o por lote.</p>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-4 py-3 text-sm text-green-800 dark:text-green-400">{{ page.props.flash.success }}</div>

            <ListToolbar
                :model-value="search"
                placeholder="Buscar por comprador, lote, arete o nombre del animal"
                :filters="listFilters"
                :active-filters="filters"
                @update:model-value="updateSearch"
                @apply="applyFilters"
                @clear="clearFilters"
            />

            <div v-if="ventas.length" class="mt-6 overflow-x-auto rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-stone-50 dark:bg-stone-950 text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        <tr>
                            <th class="px-5 py-3">Fecha de salida</th>
                            <th class="px-5 py-3">Comprador</th>
                            <th class="px-5 py-3">Animales / lote</th>
                            <th class="px-5 py-3">Precio total</th>
                            <th class="px-5 py-3">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="venta in ventas" :key="venta.id">
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">{{ formatDate(venta.sale_date) }}</td>
                            <td class="px-5 py-4 font-medium text-stone-900 dark:text-stone-100">{{ venta.buyer_name }}</td>
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">
                                <span v-if="venta.lote" class="block font-medium">{{ venta.lote.name }}</span>
                                <span class="block text-stone-500 dark:text-stone-400">{{ venta.animals.map((animal) => animal.name ? `${animal.name} (${animal.ear_tag})` : animal.ear_tag).join(', ') }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-stone-900 dark:text-stone-100">{{ formatMoney(venta.total_price) }}</td>
                            <td class="px-5 py-4 text-stone-600 dark:text-stone-400">{{ venta.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="search || Object.values(filters).some(Boolean)" class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin resultados</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Ninguna venta coincide con la búsqueda o el rango de fechas.</p>
                <button type="button" class="mt-5 rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950" @click="clearFilters">Limpiar búsqueda y filtros</button>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Aún no hay salidas registradas</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Registra la venta desde la ficha de un animal o administra un lote.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <Link href="/animales" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Ver animales</Link>
                    <Link href="/lotes" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">Ver lotes</Link>
                </div>
            </div>
        </main>
    </div>
</template>

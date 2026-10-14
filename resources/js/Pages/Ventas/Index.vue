<script setup>
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    ventas: { type: Array, required: true },
});

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800">MyRancho</Link>
                <Link href="/logout" method="post" as="button" class="text-sm font-medium text-stone-600 hover:text-green-700">Cerrar sesión</Link>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <Link href="/dashboard" class="text-sm font-medium text-green-700 hover:underline">← Panel principal</Link>
            <div class="mt-5">
                <h1 class="text-3xl font-bold text-stone-900">Ventas y salidas</h1>
                <p class="mt-1 text-stone-600">Historial de animales vendidos individualmente o por lote.</p>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ page.props.flash.success }}</div>
            <div v-if="ventas.length" class="mt-6 overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Fecha de salida</th>
                            <th class="px-5 py-3">Comprador</th>
                            <th class="px-5 py-3">Animales / lote</th>
                            <th class="px-5 py-3">Precio total</th>
                            <th class="px-5 py-3">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="venta in ventas" :key="venta.id">
                            <td class="px-5 py-4 text-stone-700">{{ venta.sale_date }}</td>
                            <td class="px-5 py-4 font-medium text-stone-900">{{ venta.buyer_name }}</td>
                            <td class="px-5 py-4 text-stone-700">
                                <span v-if="venta.lote" class="block font-medium">{{ venta.lote.name }}</span>
                                <span class="block text-stone-500">{{ venta.animals.map((animal) => animal.name ? `${animal.name} (${animal.ear_tag})` : animal.ear_tag).join(', ') }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-stone-900">${{ Number(venta.total_price).toLocaleString('es-CO', { minimumFractionDigits: 2 }) }}</td>
                            <td class="px-5 py-4 text-stone-600">{{ venta.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900">Aún no hay salidas registradas</h2>
                <p class="mt-2 text-sm text-stone-600">Registra la venta desde la ficha de un animal o administra un lote.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <Link href="/animales" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Ver animales</Link>
                    <Link href="/lotes" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">Ver lotes</Link>
                </div>
            </div>
        </main>
    </div>
</template>

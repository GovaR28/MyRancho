<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lote: { type: Object, required: true },
    animals: { type: Array, required: true },
});

const page = usePage();
const search = ref('');
const sellOpen = ref(false);
const removeAnimal = ref(null);
const addForm = useForm({ animal_ids: [] });
const saleForm = useForm({
    lote_id: props.lote.id,
    sale_date: new Date().toISOString().slice(0, 10),
    total_price: '',
    buyer_name: '',
    notes: '',
});
const filteredAnimals = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();
    if (!term) return props.animals;
    return props.animals.filter((animal) =>
        [animal.ear_tag, animal.name, animal.breed, animal.sex].filter(Boolean).join(' ').toLocaleLowerCase().includes(term),
    );
});
const unsoldCount = computed(() => props.lote.animals.filter((animal) => !animal.ventas?.length).length);

function addSelected() {
    addForm.post(`/lotes/${props.lote.id}/animales`, {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset();
            search.value = '';
        },
    });
}

function submitSale() {
    saleForm.post('/ventas', {
        preserveScroll: true,
        onSuccess: () => {
            sellOpen.value = false;
        },
    });
}

function removeSelected() {
    if (!removeAnimal.value) return;
    router.delete(`/lotes/${props.lote.id}/animales/${removeAnimal.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            removeAnimal.value = null;
        },
    });
}
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
            <Link href="/lotes" class="text-sm font-medium text-green-700 hover:underline">← Volver a lotes</Link>
            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">{{ lote.name }}</h1>
                    <p class="mt-1 text-stone-600">{{ lote.description || 'Sin descripción' }}</p>
                </div>
                <button type="button" :disabled="!unsoldCount" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50" @click="sellOpen = true">Vender {{ unsoldCount }} animales del lote</button>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ page.props.flash.success }}</div>

            <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Agregar animales</h2>
                <p class="mt-1 text-sm text-stone-600">Solo aparecen animales disponibles que no estén ya en este lote.</p>
                <form class="mt-4" @submit.prevent="addSelected">
                    <input v-model="search" type="search" placeholder="Buscar por arete, nombre, raza o sexo" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                    <div v-if="filteredAnimals.length" class="mt-3 grid max-h-56 gap-1 overflow-y-auto sm:grid-cols-2">
                        <label v-for="animal in filteredAnimals" :key="animal.id" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-stone-50">
                            <input v-model="addForm.animal_ids" type="checkbox" :value="animal.id" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                            <span class="text-sm"><span class="font-medium text-stone-900">{{ animal.name || 'Sin nombre' }}</span><span class="ml-2 text-stone-500">{{ animal.ear_tag }} · {{ animal.breed || animal.sex }}</span></span>
                        </label>
                    </div>
                    <p v-else class="mt-3 text-sm text-stone-500">{{ animals.length ? 'No hay coincidencias.' : 'No hay otros animales disponibles.' }}</p>
                    <p v-if="addForm.errors.animal_ids" class="mt-2 text-sm text-red-600">{{ addForm.errors.animal_ids }}</p>
                    <button type="submit" :disabled="addForm.processing || !addForm.animal_ids.length" class="mt-3 rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50">{{ addForm.processing ? 'Agregando...' : `Agregar seleccionados (${addForm.animal_ids.length})` }}</button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <h2 class="border-b border-stone-100 px-5 py-4 text-lg font-semibold text-stone-900">Animales del lote</h2>
                <div v-if="lote.animals.length" class="divide-y divide-stone-100">
                    <article v-for="animal in lote.animals" :key="animal.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-medium text-stone-900">{{ animal.name || 'Sin nombre' }} <span class="text-stone-500">· Arete {{ animal.ear_tag }}</span></p>
                            <p class="mt-1 text-sm text-stone-500">{{ animal.finca?.name || 'Finca archivada' }} · {{ animal.breed || animal.sex }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="animal.ventas?.length" class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600">Vendido</span>
                            <button v-else type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removeAnimal = animal">Quitar del lote</button>
                        </div>
                    </article>
                </div>
                <p v-else class="p-5 text-sm text-stone-600">Este lote aún no tiene animales.</p>
            </section>

            <section v-if="lote.ventas.length" class="mt-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <h2 class="border-b border-stone-100 px-5 py-4 text-lg font-semibold text-stone-900">Ventas registradas</h2>
                <article v-for="venta in lote.ventas" :key="venta.id" class="flex flex-wrap justify-between gap-2 border-b border-stone-100 px-5 py-4 last:border-0">
                    <span class="text-sm text-stone-700">{{ venta.sale_date }} · {{ venta.buyer_name }}</span>
                    <span class="text-sm font-semibold text-stone-900">${{ Number(venta.total_price).toLocaleString('es-CO', { minimumFractionDigits: 2 }) }}</span>
                </article>
            </section>
        </main>
        <Teleport to="body">
            <div v-if="sellOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="sellOpen = false" @keydown.esc="sellOpen = false">
                <section role="dialog" aria-modal="true" aria-labelledby="sell-lote-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="shrink-0 border-b border-stone-100 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="sell-lote-title" class="text-xl font-bold text-stone-900">Registrar venta del lote</h2>
                            <p class="mt-1 text-sm text-stone-600">Se registrará el precio total por los {{ unsoldCount }} animales disponibles. Los ya vendidos no se incluirán.</p>
                        </div>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitSale">
                        <div>
                            <label for="lote-sale-date" class="mb-1.5 block text-sm font-medium text-stone-700">Fecha de salida *</label>
                            <input id="lote-sale-date" v-model="saleForm.sale_date" type="date" required class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.sale_date" class="mt-1 text-sm text-red-600">{{ saleForm.errors.sale_date }}</p>
                        </div>
                        <div>
                            <label for="lote-sale-price" class="mb-1.5 block text-sm font-medium text-stone-700">Precio total del lote *</label>
                            <input id="lote-sale-price" v-model="saleForm.total_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.total_price" class="mt-1 text-sm text-red-600">{{ saleForm.errors.total_price }}</p>
                        </div>
                        <div>
                            <label for="lote-sale-buyer" class="mb-1.5 block text-sm font-medium text-stone-700">Persona compradora *</label>
                            <input id="lote-sale-buyer" v-model="saleForm.buyer_name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.buyer_name" class="mt-1 text-sm text-red-600">{{ saleForm.errors.buyer_name }}</p>
                        </div>
                        <div>
                            <label for="lote-sale-notes" class="mb-1.5 block text-sm font-medium text-stone-700">Notas</label>
                            <textarea id="lote-sale-notes" v-model="saleForm.notes" rows="2" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                        </div>
                        <p v-if="saleForm.errors.lote_id" class="text-sm text-red-600">{{ saleForm.errors.lote_id }}</p>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="saleForm.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50" @click="sellOpen = false">Cancelar</button>
                            <button type="submit" :disabled="saleForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ saleForm.processing ? 'Guardando...' : 'Registrar salida' }}</button>
                        </div>
                    </form>
                </section>
            </div>
            <div v-if="removeAnimal" class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-950/50 p-4" @click.self="removeAnimal = null" @keydown.esc="removeAnimal = null">
                <section role="dialog" aria-modal="true" aria-labelledby="remove-lote-animal-title" class="flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="shrink-0 border-b border-stone-100 px-6 pb-4 pt-6">
                        <h2 id="remove-lote-animal-title" class="text-xl font-bold text-stone-900">Quitar animal del lote</h2>
                        <p class="mt-3 text-sm text-stone-600">¿Quitar el animal {{ removeAnimal.ear_tag }} de este lote? El animal seguirá registrado.</p>
                    </div>
                    <div class="flex justify-end gap-3 px-6 pb-6 pt-5">
                        <button type="button" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50" @click="removeAnimal = null">Cancelar</button>
                        <button type="button" class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-600" @click="removeSelected">Quitar del lote</button>
                    </div>
                </section>
            </div>
        </Teleport>
    </div>
</template>

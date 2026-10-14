<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import ListToolbar from '../../Components/ListToolbar.vue';
import { useListFilters } from '../../Composables/useListFilters';

const props = defineProps({
    lotes: { type: Array, required: true },
    animals: { type: Array, required: true },
});

const page = usePage();

const { search: listSearch, updateSearch } = useListFilters('/lotes');

const createOpen = ref(false);
const search = ref('');
const form = useForm({
    name: '',
    description: '',
    animal_ids: [],
});
const filteredAnimals = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();
    if (!term) return props.animals;
    return props.animals.filter((animal) =>
        [animal.ear_tag, animal.name, animal.breed, animal.sex].filter(Boolean).join(' ').toLocaleLowerCase().includes(term),
    );
});

function openCreate() {
    form.reset();
    form.clearErrors();
    search.value = '';
    createOpen.value = true;
    nextTick(() => document.getElementById('new-lote-name')?.focus());
}

function closeCreate() {
    if (form.processing) return;
    createOpen.value = false;
}

function submit() {
    form.post('/lotes', {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
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
            <Link href="/dashboard" class="text-sm font-medium text-green-700 hover:underline">← Panel principal</Link>
            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">Lotes</h1>
                    <p class="mt-1 text-stone-600">Agrupa animales para organizarlos y registrar una venta conjunta.</p>
                </div>
                <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreate">+ Crear lote</button>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ page.props.flash.success }}</div>

            <ListToolbar
                :model-value="listSearch"
                placeholder="Buscar lote por nombre o descripción"
                :filters="[]"
                :active-filters="{}"
                @update:model-value="updateSearch"
            />

            <div v-if="lotes.length" class="mt-6 grid gap-4 md:grid-cols-2">
                <article v-for="lote in lotes" :key="lote.id" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">{{ lote.name }}</h2>
                    <p class="mt-2 text-sm text-stone-600">{{ lote.description || 'Sin descripción' }}</p>
                    <p class="mt-3 text-sm text-stone-500">{{ lote.animals_count }} animales · {{ lote.ventas_count }} ventas</p>
                    <Link :href="`/lotes/${lote.id}`" class="mt-4 inline-flex rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50">Administrar lote</Link>
                </article>
            </div>
            <div v-else-if="listSearch" class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900">Sin resultados</h2>
                <p class="mt-2 text-sm text-stone-600">Ningún lote coincide con la búsqueda.</p>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900">Aún no tienes lotes</h2>
                <p class="mt-2 text-sm text-stone-600">Crea un lote y selecciona los animales que quieres agrupar.</p>
                <button type="button" class="mt-5 rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreate">Crear primer lote</button>
            </div>
        </main>
        <Teleport to="body">
            <div v-if="createOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeCreate" @keydown.esc="closeCreate">
                <section role="dialog" aria-modal="true" aria-labelledby="new-lote-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="new-lote-title" tabindex="-1" class="text-xl font-bold text-stone-900">Crear lote</h2>
                            <p class="mt-1 text-sm text-stone-600">Selecciona animales disponibles; podrás agregar más después.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="form.processing" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100" @click="closeCreate">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submit">
                        <div>
                            <label for="new-lote-name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre del lote *</label>
                            <input id="new-lote-name" v-model="form.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label for="new-lote-description" class="mb-1.5 block text-sm font-medium text-stone-700">Descripción</label>
                            <textarea id="new-lote-description" v-model="form.description" rows="2" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="text-sm font-medium text-stone-700">Animales disponibles *</label>
                                <span class="text-xs text-stone-500">{{ form.animal_ids.length }} seleccionados</span>
                            </div>
                            <input v-model="search" type="search" placeholder="Buscar por arete, nombre, raza o sexo" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <div v-if="filteredAnimals.length" class="mt-2 max-h-56 divide-y divide-stone-100 overflow-y-auto rounded-lg border border-stone-200">
                                <label v-for="animal in filteredAnimals" :key="animal.id" class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-stone-50">
                                    <input v-model="form.animal_ids" type="checkbox" :value="animal.id" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                                    <span class="min-w-0 text-sm"><span class="font-medium text-stone-900">{{ animal.name || 'Sin nombre' }}</span><span class="ml-2 text-stone-500">Arete: {{ animal.ear_tag }} · {{ animal.breed || animal.sex }}</span></span>
                                </label>
                            </div>
                            <p v-else class="mt-2 rounded-lg bg-stone-50 p-4 text-sm text-stone-600">{{ animals.length ? 'No hay coincidencias con esa búsqueda.' : 'No hay animales disponibles para agregar a un lote.' }}</p>
                            <p v-if="form.errors.animal_ids" class="mt-1 text-sm text-red-600">{{ form.errors.animal_ids }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="form.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeCreate">Cancelar</button>
                            <button type="submit" :disabled="form.processing || !form.animal_ids.length" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ form.processing ? 'Guardando...' : 'Crear lote' }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </div>
</template>

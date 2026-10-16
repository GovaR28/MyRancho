<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, ref } from 'vue';
import ListToolbar from '../../Components/ListToolbar.vue';
import UserMenu from '../../Components/UserMenu.vue';
import { useListFilters } from '../../Composables/useListFilters';

const props = defineProps({
    vaccinations: { type: Object, required: true },
    vaccineClasses: { type: Array, default: () => [] },
    vaccineSuggestions: { type: Array, default: () => [] },
    filterOptions: { type: Object, default: () => ({ clase: [], alcance: [] }) },
});

const page = usePage();

const { search: listSearch, filters, updateSearch, applyFilters, clearFilters } = useListFilters('/vacunaciones', [
    'clase',
    'alcance',
    'desde',
    'hasta',
]);

const listFilters = computed(() => [
    { key: 'clase', label: 'Clase de vacuna', options: props.filterOptions.clase },
    { key: 'alcance', label: 'Alcance', options: props.filterOptions.alcance },
    { key: 'desde', label: 'Desde', type: 'date' },
    { key: 'hasta', label: 'Hasta', type: 'date' },
]);

const SCOPES = [
    { value: 'todo', label: 'Todo el ganado' },
    { value: 'lote', label: 'Un lote' },
    { value: 'grupo', label: 'Un grupo de vacas' },
    { value: 'individual', label: 'Una sola vaca' },
];
const SCOPE_BADGES = {
    todo: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-400',
    lote: 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-300',
    grupo: 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300',
    individual: 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300',
};

const formModalOpen = ref(false);
const editing = ref(null);
const loadingOptions = ref(false);
const optionsError = ref('');
const options = ref({ lotes: [], animals: [] });
const search = ref('');
const detailOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref('');
const detail = ref(null);
const pendingAction = ref(null);
const actionProcessing = ref(false);

const form = useForm({
    vaccination_date: new Date().toISOString().slice(0, 10),
    vaccine_name: '',
    vaccine_class: '',
    vaccine_class_other: '',
    vaccinator: '',
    scope: 'todo',
    lote_id: '',
    animal_ids: [],
    animal_id: '',
    notes: '',
});

const filteredAnimals = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();
    if (!term) return options.value.animals;
    return options.value.animals.filter((animal) =>
        [animal.ear_tag, animal.name, animal.breed, animal.sex].filter(Boolean).join(' ').toLocaleLowerCase().includes(term),
    );
});

const selectedLote = computed(() => options.value.lotes.find((lote) => String(lote.id) === String(form.lote_id)) || null);

const needsClassDetail = computed(() => form.vaccine_class === 'Otra');
function scopeLabel(scope) {
    return SCOPES.find((item) => item.value === scope)?.label || scope;
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function animalsCountLabel(vaccination) {
    const count = vaccination.animals_count ?? 0;
    if (vaccination.scope === 'individual') return count === 1 ? '1 vaca' : `${count} vacas`;
    return `${count} animales`;
}

async function openCreateModal() {
    editing.value = null;
    formModalOpen.value = true;
    loadingOptions.value = true;
    optionsError.value = '';
    search.value = '';
    form.reset();
    form.clearErrors();
    form.vaccination_date = new Date().toISOString().slice(0, 10);
    form.vaccine_class = props.vaccineClasses[0] || '';
    form.scope = 'todo';

    try {
        const { data } = await axios.get('/vacunaciones/create', { headers: { Accept: 'application/json' } });
        options.value = { lotes: data.lotes, animals: data.animals };
        loadingOptions.value = false;
        await nextTick();
        document.getElementById('vaccination-date')?.focus();
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        optionsError.value = 'No se pudieron cargar los datos para registrar la vacunación. Inténtalo de nuevo.';
        loadingOptions.value = false;
    }
}

async function openEditModal(vaccination) {
    editing.value = vaccination;
    formModalOpen.value = true;
    loadingOptions.value = true;
    optionsError.value = '';
    search.value = '';
    form.clearErrors();

    try {
        const { data } = await axios.get(`/vacunaciones/${vaccination.id}/edit`, { headers: { Accept: 'application/json' } });
        const record = data.vaccination;
        form.vaccination_date = String(record.vaccination_date || '').slice(0, 10);
        form.vaccine_name = record.vaccine_name || '';
        form.vaccine_class = props.vaccineClasses.includes(record.vaccine_class) ? record.vaccine_class : 'Otra';
        form.vaccine_class_other = form.vaccine_class === 'Otra' && record.vaccine_class ? record.vaccine_class : '';
        form.vaccinator = record.vaccinator || '';
        form.scope = record.scope || 'todo';
        form.lote_id = record.lote_id ?? '';
        form.notes = record.notes || '';
        form.animal_ids = (record.animals || []).map((animal) => animal.id);
        form.animal_id = form.scope === 'individual' ? (record.animals?.[0]?.id ?? '') : '';
        options.value = { lotes: data.lotes, animals: data.animals };
        loadingOptions.value = false;
        await nextTick();
        document.getElementById('vaccination-date')?.focus();
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        optionsError.value = 'No se pudo cargar el registro de vacunación. Inténtalo de nuevo.';
        loadingOptions.value = false;
    }
}

function closeFormModal() {
    if (form.processing || loadingOptions.value) return;
    formModalOpen.value = false;
    editing.value = null;
}

function selectAllFiltered() {
    const ids = filteredAnimals.value.map((animal) => animal.id);
    form.animal_ids = [...new Set([...form.animal_ids, ...ids])];
}

function clearSelection() {
    form.animal_ids = [];
}

function submitForm() {
    const submitOptions = {
        preserveScroll: true,
        onSuccess: () => {
            formModalOpen.value = false;
            editing.value = null;
        },
    };

    form.transform((data) => {
        const payload = {
            vaccination_date: data.vaccination_date,
            vaccine_name: data.vaccine_name,
            vaccine_class: data.vaccine_class === 'Otra' ? (data.vaccine_class_other.trim() || 'Otra') : data.vaccine_class,
            vaccinator: data.vaccinator,
            scope: data.scope,
            lote_id: data.scope === 'lote' ? data.lote_id : null,
            animal_ids: data.scope === 'grupo'
                ? data.animal_ids
                : (data.scope === 'individual' && data.animal_id ? [data.animal_id] : []),
            notes: data.notes,
        };

        if (editing.value) payload._method = 'put';

        return payload;
    }).post(editing.value ? `/vacunaciones/${editing.value.id}` : '/vacunaciones', submitOptions);
}

async function openDetail(vaccination) {
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = '';
    detail.value = null;

    try {
        const { data } = await axios.get(`/vacunaciones/${vaccination.id}`, { headers: { Accept: 'application/json' } });
        detail.value = data.vaccination;
        detailLoading.value = false;
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        detailError.value = 'No se pudo cargar el detalle de la vacunación. Inténtalo de nuevo.';
        detailLoading.value = false;
    }
}

function closeDetail() {
    if (detailLoading.value) return;
    detailOpen.value = false;
    detail.value = null;
}

function confirmDelete(vaccination) {
    pendingAction.value = {
        title: 'Eliminar registro de vacunación',
        message: `¿Eliminar la vacunación del ${formatDate(vaccination.vaccination_date)} (${vaccination.vaccine_name})? Esta acción quita el registro y los animales asociados.`,
        button: 'Eliminar registro',
        url: `/vacunaciones/${vaccination.id}`,
    };
}

function closeActionModal() {
    if (actionProcessing.value) return;
    pendingAction.value = null;
}

function submitAction() {
    if (!pendingAction.value || actionProcessing.value) return;
    actionProcessing.value = true;

    router.delete(pendingAction.value.url, {
        preserveScroll: true,
        onFinish: () => {
            actionProcessing.value = false;
            pendingAction.value = null;
        },
    });
}
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
                <UserMenu />
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <Link href="/dashboard" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Panel principal</Link>
            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-100">Vacunaciones</h1>
                    <p class="mt-1 text-stone-600 dark:text-stone-400">Historial sanitario del ganado: fecha, vacuna, vacunador y alcance.</p>
                </div>
                <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">+ Nueva vacunación</button>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-4 py-3 text-sm text-green-800 dark:text-green-400">{{ page.props.flash.success }}</div>

            <ListToolbar
                :model-value="listSearch"
                placeholder="Buscar por vacuna, clase, vacunador o lote"
                :filters="listFilters"
                :active-filters="filters"
                @update:model-value="updateSearch"
                @apply="applyFilters"
                @clear="clearFilters"
            />

            <div v-if="vaccinations.data.length" class="mt-6 overflow-x-auto rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-stone-50 dark:bg-stone-950 text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Tipo de vacuna</th>
                            <th class="px-5 py-3">Clase</th>
                            <th class="px-5 py-3">Vacunador</th>
                            <th class="px-5 py-3">Alcance</th>
                            <th class="px-5 py-3">Animales</th>
                            <th class="px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="vaccination in vaccinations.data" :key="vaccination.id">
                            <td class="px-5 py-4 font-medium text-stone-900 dark:text-stone-100">{{ formatDate(vaccination.vaccination_date) }}</td>
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">
                                <span class="block font-medium text-stone-900 dark:text-stone-100">{{ vaccination.vaccine_name }}</span>
                                <span v-if="vaccination.notes" class="mt-0.5 block max-w-[220px] truncate text-stone-500 dark:text-stone-400">{{ vaccination.notes }}</span>
                            </td>
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">{{ vaccination.vaccine_class }}</td>
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">{{ vaccination.vaccinator }}</td>
                            <td class="px-5 py-4">
                                <span :class="SCOPE_BADGES[vaccination.scope] || 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">{{ scopeLabel(vaccination.scope) }}</span>
                                <span v-if="vaccination.scope === 'lote'" class="mt-1 block text-xs text-stone-500 dark:text-stone-400">Lote: {{ vaccination.lote?.name || 'eliminado' }}</span>
                            </td>
                            <td class="px-5 py-4 text-stone-700 dark:text-stone-300">{{ animalsCountLabel(vaccination) }}</td>
                            <td class="px-5 py-4">
                                <div class="flex gap-3">
                                    <button type="button" class="font-medium text-green-800 dark:text-green-400 hover:underline" @click="openDetail(vaccination)">Ver</button>
                                    <button type="button" class="font-medium text-green-800 dark:text-green-400 hover:underline" @click="openEditModal(vaccination)">Editar</button>
                                    <button type="button" class="font-medium text-red-700 dark:text-red-400 hover:underline" @click="confirmDelete(vaccination)">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="listSearch || Object.values(filters).some(Boolean)" class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin resultados</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Ninguna vacunación coincide con la búsqueda o los filtros aplicados.</p>
                <button type="button" class="mt-5 rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950" @click="clearFilters">Limpiar búsqueda y filtros</button>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Aún no hay vacunaciones registradas</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Registra la primera aplicación para llevar el control sanitario de tu ganado.</p>
                <button type="button" class="mt-5 rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">Registrar vacunación</button>
            </div>

            <nav v-if="vaccinations.links.length > 3" class="mt-5 flex flex-wrap gap-2" aria-label="Paginación">
                <Link v-for="link in vaccinations.links" :key="link.label" :href="link.url || '#'" preserve-scroll
                    :class="link.active ? 'border-green-800 bg-green-800 text-white' : 'border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950'"
                    class="rounded-lg border px-3 py-2 text-sm"
                    v-html="link.label" />
            </nav>
        </main>

        <Teleport to="body">
            <div v-if="formModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeFormModal" @keydown.esc="closeFormModal">
                <section role="dialog" aria-modal="true" aria-labelledby="vaccination-form-title" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-stone-900 shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 dark:border-stone-800 px-6 pb-5 pt-6 sm:px-8 sm:pt-8">
                        <div>
                            <h2 id="vaccination-form-title" tabindex="-1" class="text-xl font-bold text-stone-900 dark:text-stone-100">{{ editing ? 'Editar vacunación' : 'Registrar vacunación' }}</h2>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ editing ? `Aplicación del ${formatDate(editing.vaccination_date)}` : 'Completa los datos de la aplicación y su alcance.' }}</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="form.processing || loadingOptions" class="rounded-lg px-2 py-1 text-xl text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" @click="closeFormModal">×</button>
                    </div>

                    <p v-if="optionsError" class="mx-6 mt-4 rounded-lg bg-red-50 dark:bg-red-950 px-4 py-3 text-sm text-red-700 dark:text-red-400 sm:mx-8">{{ optionsError }}</p>
                    <p v-else-if="loadingOptions" class="px-6 py-8 text-center text-sm text-stone-600 dark:text-stone-400 sm:px-8">Cargando datos...</p>

                    <form v-else class="overflow-y-auto px-6 pb-6 pt-5 space-y-6 sm:px-8 sm:pb-8" @submit.prevent="submitForm">
                        <section>
                            <h3 class="mb-4 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Datos de la vacuna</h3>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="vaccination-date" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Fecha de la vacunación *</label>
                                    <input id="vaccination-date" v-model="form.vaccination_date" type="date" required :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.vaccination_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.vaccination_date }}</p>
                                </div>
                                <div>
                                    <label for="vaccination-vaccinator" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Vacunador *</label>
                                    <input id="vaccination-vaccinator" v-model="form.vaccinator" required maxlength="120" placeholder="Nombre del veterinario o persona que aplica" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.vaccinator" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.vaccinator }}</p>
                                </div>
                                <div>
                                    <label for="vaccination-vaccine-name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Tipo de vacuna *</label>
                                    <input id="vaccination-vaccine-name" v-model="form.vaccine_name" list="vaccine-suggestions" required maxlength="120" placeholder="Ej. Aftosa, Clostridiosis 8, Brucelosis" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <datalist id="vaccine-suggestions">
                                        <option v-for="suggestion in props.vaccineSuggestions" :key="suggestion" :value="suggestion" />
                                    </datalist>
                                    <p v-if="form.errors.vaccine_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.vaccine_name }}</p>
                                </div>
                                <div>
                                    <label for="vaccination-class" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Clase de vacuna *</label>
                                    <select id="vaccination-class" v-model="form.vaccine_class" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                        <option value="" disabled>Selecciona la clase</option>
                                        <option v-for="vaccineClass in props.vaccineClasses" :key="vaccineClass" :value="vaccineClass">{{ vaccineClass }}</option>
                                    </select>
                                    <p v-if="form.errors.vaccine_class" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.vaccine_class }}</p>
                                </div>
                                <div v-if="needsClassDetail" class="sm:col-span-2">
                                    <label for="vaccination-class-other" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Describe la clase de vacuna *</label>
                                    <input id="vaccination-class-other" v-model="form.vaccine_class_other" required maxlength="60" placeholder="Ej. Antiparasitaria interna" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.vaccine_class" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.vaccine_class }}</p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-1 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Alcance de la vacunación</h3>
                            <p class="mb-3 mt-2 text-sm text-stone-600 dark:text-stone-400">Selecciona si se vacunó todo el ganado, un lote, un grupo de vacas o una sola vaca.</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label v-for="scope in SCOPES" :key="scope.value" class="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm" :class="form.scope === scope.value ? 'border-green-700 dark:border-green-500 bg-green-50 dark:bg-green-950 text-green-900 dark:text-green-300' : 'border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950'">
                                    <input v-model="form.scope" type="radio" name="scope" :value="scope.value" class="border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                                    <span class="font-medium">{{ scope.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.scope" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.scope }}</p>

                            <div v-if="form.scope === 'todo'" class="mt-4 rounded-lg bg-stone-50 dark:bg-stone-950 px-4 py-3 text-sm text-stone-700 dark:text-stone-300">
                                Se aplicará a <span class="font-semibold">{{ options.animals.length }}</span> {{ options.animals.length === 1 ? 'animal disponible' : 'animales disponibles' }} de tu cuenta (sin ventas registradas).
                            </div>

                            <div v-if="form.scope === 'lote'" class="mt-4">
                                <label for="vaccination-lote" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Lote a vacunar *</label>
                                <select id="vaccination-lote" v-model="form.lote_id" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona un lote</option>
                                    <option v-for="lote in options.lotes" :key="lote.id" :value="lote.id">{{ lote.name }} · {{ lote.animals_count }} {{ lote.animals_count === 1 ? 'animal' : 'animales' }}</option>
                                </select>
                                <p v-if="form.errors.lote_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.lote_id }}</p>
                                <p v-if="selectedLote" class="mt-2 text-sm text-stone-600 dark:text-stone-400">Este lote tiene {{ selectedLote.animals_count }} {{ selectedLote.animals_count === 1 ? 'animal' : 'animales' }}.</p>
                                <p v-if="!options.lotes.length" class="mt-2 text-sm text-stone-500 dark:text-stone-400">No tienes lotes creados. Créalos desde la sección de lotes.</p>
                            </div>

                            <div v-if="form.scope === 'grupo'" class="mt-4">
                                <label for="vaccination-group-search" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Selecciona las vacas del grupo *</label>
                                <input id="vaccination-group-search" v-model="search" type="search" placeholder="Buscar por arete, nombre, raza o sexo" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <div v-if="filteredAnimals.length" class="mt-3 grid max-h-56 gap-1 overflow-y-auto sm:grid-cols-2">
                                    <label v-for="animal in filteredAnimals" :key="animal.id" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-stone-50 dark:hover:bg-stone-950">
                                        <input v-model="form.animal_ids" type="checkbox" :value="animal.id" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                                        <span class="text-sm"><span class="font-medium text-stone-900 dark:text-stone-100">{{ animal.name || 'Sin nombre' }}</span><span class="ml-2 text-stone-500 dark:text-stone-400">{{ animal.ear_tag }} · {{ animal.breed || animal.sex }}</span></span>
                                    </label>
                                </div>
                                <p v-else class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ options.animals.length ? 'No hay coincidencias.' : 'No hay animales disponibles para vacunar.' }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <button type="button" class="text-sm font-medium text-green-800 dark:text-green-400 hover:underline" @click="selectAllFiltered">Seleccionar todos los filtrados</button>
                                    <button type="button" class="text-sm font-medium text-stone-500 dark:text-stone-400 hover:underline" @click="clearSelection">Limpiar</button>
                                    <span class="text-sm text-stone-600 dark:text-stone-400">{{ form.animal_ids.length }} {{ form.animal_ids.length === 1 ? 'seleccionada' : 'seleccionadas' }}</span>
                                </div>
                                <p v-if="form.errors.animal_ids" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.animal_ids }}</p>
                            </div>

                            <div v-if="form.scope === 'individual'" class="mt-4">
                                <label for="vaccination-animal" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Vaca a vacunar *</label>
                                <select id="vaccination-animal" v-model="form.animal_id" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona una vaca</option>
                                    <option v-for="animal in options.animals" :key="animal.id" :value="animal.id">{{ animal.ear_tag }}{{ animal.name ? ` · ${animal.name}` : '' }} · {{ animal.breed || animal.sex }}</option>
                                </select>
                                <p v-if="form.errors.animal_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.animal_ids }}</p>
                                <p v-if="!options.animals.length" class="mt-2 text-sm text-stone-500 dark:text-stone-400">No hay animales disponibles para vacunar.</p>
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-4 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Observaciones</h3>
                            <label for="vaccination-notes" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Notas</label>
                            <textarea id="vaccination-notes" v-model="form.notes" rows="3" maxlength="5000" placeholder="Dosis, vía de aplicación, refuerzos pendientes..." class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.notes }}</p>
                        </section>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="form.processing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="closeFormModal">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ form.processing ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Guardar vacunación') }}</button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="detailOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeDetail" @keydown.esc="closeDetail">
                <section role="dialog" aria-modal="true" aria-labelledby="vaccination-detail-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-stone-900 shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 dark:border-stone-800 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="vaccination-detail-title" tabindex="-1" class="text-xl font-bold text-stone-900 dark:text-stone-100">Detalle de la vacunación</h2>
                            <p v-if="detail" class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ formatDate(detail.vaccination_date) }} · {{ detail.vaccine_name }}</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="detailLoading" class="rounded-lg px-2 py-1 text-xl text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" @click="closeDetail">×</button>
                    </div>

                    <p v-if="detailError" class="mx-6 mt-4 rounded-lg bg-red-50 dark:bg-red-950 px-4 py-3 text-sm text-red-700 dark:text-red-400">{{ detailError }}</p>
                    <p v-else-if="detailLoading" class="px-6 py-8 text-center text-sm text-stone-600 dark:text-stone-400">Cargando detalle...</p>

                    <div v-else-if="detail" class="overflow-y-auto px-6 pb-6 pt-5">
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Fecha</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ formatDate(detail.vaccination_date) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Tipo de vacuna</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ detail.vaccine_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Clase de vacuna</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ detail.vaccine_class }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Vacunador</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ detail.vaccinator }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Alcance</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ scopeLabel(detail.scope) }}<span v-if="detail.scope === 'lote'"> · {{ detail.lote?.name || 'Lote eliminado' }}</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Animales vacunados</dt>
                                <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100">{{ detail.animals_count }}</dd>
                            </div>
                        </dl>

                        <p v-if="detail.notes" class="mt-4 rounded-lg bg-stone-50 dark:bg-stone-950 px-4 py-3 text-sm text-stone-700 dark:text-stone-300">{{ detail.notes }}</p>

                        <h3 class="mt-6 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Animales incluidos</h3>
                        <div v-if="detail.animals?.length" class="mt-3 grid max-h-64 gap-1 overflow-y-auto">
                            <article v-for="animal in detail.animals" :key="animal.id" class="rounded-lg border border-stone-100 dark:border-stone-800 px-3 py-2">
                                <p class="text-sm font-medium text-stone-900 dark:text-stone-100">{{ animal.name || 'Sin nombre' }} <span class="text-stone-500 dark:text-stone-400">· Arete {{ animal.ear_tag }}</span></p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ animal.finca?.name || 'Finca archivada' }} · {{ animal.breed || animal.sex }}<span v-if="animal.ventas?.length"> · Vendido</span></p>
                            </article>
                        </div>
                        <p v-else class="mt-3 text-sm text-stone-500 dark:text-stone-400">Este registro no tiene animales asociados.</p>

                        <div class="mt-6 flex justify-end">
                            <button type="button" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950" @click="closeDetail">Cerrar</button>
                        </div>
                    </div>
                </section>
            </div>

            <div v-if="pendingAction" class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeActionModal" @keydown.esc="closeActionModal">
                <section role="dialog" aria-modal="true" aria-labelledby="vaccination-action-title" class="w-full max-w-md rounded-2xl bg-white dark:bg-stone-900 p-6 shadow-xl">
                    <h2 id="vaccination-action-title" class="text-xl font-bold text-stone-900 dark:text-stone-100">{{ pendingAction.title }}</h2>
                    <p class="mt-3 text-sm text-stone-600 dark:text-stone-400">{{ pendingAction.message }}</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="actionProcessing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="closeActionModal">Cancelar</button>
                        <button type="button" :disabled="actionProcessing" class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-600 disabled:opacity-50" @click="submitAction">{{ actionProcessing ? 'Procesando...' : pendingAction.button }}</button>
                    </div>
                </section>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import ListToolbar from '../../Components/ListToolbar.vue';
import UserMenu from '../../Components/UserMenu.vue';
import { useListFilters } from '../../Composables/useListFilters';
import { areaUnitLabel, formatArea, toDisplayArea, toStorageArea } from '../../Composables/usePreferences';

defineProps({
    fincas: { type: Array, required: true },
});

const page = usePage();

const { search, filters, updateSearch, applyFilters, clearFilters } = useListFilters('/fincas', ['estado']);

const listFilters = computed(() => [
    {
        key: 'estado',
        label: 'Estado',
        options: [
            { value: 'activa', label: 'Activas' },
            { value: 'inactiva', label: 'Inactivas' },
        ],
    },
]);

const isCreateModalOpen = ref(false);
const editingFinca = ref(null);
const pendingAction = ref(null);
const actionProcessing = ref(false);
const editForm = useForm({
    name: '',
    location: '',
    area_hectares: '',
    description: '',
    is_active: true,
});

function openCreateModal() {
    editForm.reset();
    editForm.clearErrors();
    isCreateModalOpen.value = true;
    nextTick(() => document.getElementById('edit-finca-name')?.focus());
}

function closeCreateModal() {
    if (editForm.processing) return;
    isCreateModalOpen.value = false;
}

function openEditModal(finca) {
    isCreateModalOpen.value = false;
    editingFinca.value = finca;
    editForm.name = finca.name;
    editForm.location = finca.location ?? '';
    editForm.area_hectares = toDisplayArea(finca.area_hectares);
    editForm.description = finca.description ?? '';
    editForm.is_active = finca.is_active;
    editForm.clearErrors();
    nextTick(() => document.getElementById('edit-finca-name')?.focus());
}

function closeEditModal() {
    if (editForm.processing) return;
    editingFinca.value = null;
}

function submitFinca() {
    const options = {
        preserveScroll: true,
        transform: (data) => ({ ...data, area_hectares: toStorageArea(data.area_hectares) }),
        onSuccess: () => {
            isCreateModalOpen.value = false;
            editingFinca.value = null;
        },
    };

    if (editingFinca.value) {
        editForm.put(`/fincas/${editingFinca.value.id}`, options);
    } else {
        editForm.post('/fincas', options);
    }
}

function confirmStatusChange(finca) {
    const activating = !finca.is_active;
    pendingAction.value = {
        title: activating ? 'Activar finca' : 'Desactivar finca',
        message: `¿Deseas ${activating ? 'activar' : 'desactivar'} ${finca.name}?`,
        button: activating ? 'Activar finca' : 'Desactivar finca',
        url: `/fincas/${finca.id}/estado`,
        method: 'patch',
        activating,
    };
}

function confirmArchive(finca) {
    pendingAction.value = {
        title: 'Archivar finca',
        message: `¿Archivar ${finca.name}? El historial de sus animales se conservará.`,
        button: 'Archivar finca',
        url: `/fincas/${finca.id}`,
        method: 'delete',
        activating: false,
    };
}

function closeActionModal() {
    if (actionProcessing.value) return;
    pendingAction.value = null;
}

function submitAction() {
    if (!pendingAction.value || actionProcessing.value) return;
    actionProcessing.value = true;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            actionProcessing.value = false;
            pendingAction.value = null;
        },
    };

    if (pendingAction.value.method === 'patch') {
        router.patch(pendingAction.value.url, {}, options);
    } else {
        router.delete(pendingAction.value.url, options);
    }
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
                    <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-100">Fincas</h1>
                    <p class="mt-1 text-stone-600 dark:text-stone-400">Organiza tus propiedades y conserva su historial.</p>
                </div>
                <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">+ Nueva finca</button>
            </div>

            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-4 py-3 text-sm text-green-800 dark:text-green-400">
                {{ page.props.flash.success }}
            </div>

            <ListToolbar
                :model-value="search"
                placeholder="Buscar por nombre o ubicación"
                :filters="listFilters"
                :active-filters="filters"
                @update:model-value="updateSearch"
                @apply="applyFilters"
                @clear="clearFilters"
            />

            <div v-if="fincas.length" class="mt-6 overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
                <div class="divide-y divide-stone-100 dark:divide-stone-800">
                    <article v-for="finca in fincas" :key="finca.id" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">{{ finca.name }}</h2>
                                <span :class="finca.is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-400' : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400'" class="rounded-full px-2.5 py-1 text-xs font-medium">
                                    {{ finca.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ finca.location || 'Sin ubicación' }}<span v-if="finca.area_hectares"> · {{ formatArea(finca.area_hectares) }}</span></p>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ finca.animals_count }} animales asociados</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm font-medium text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950" @click="openEditModal(finca)">Editar</button>
                            <button type="button" @click="confirmStatusChange(finca)" class="rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm font-medium text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">
                                {{ finca.is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                            <button type="button" @click="confirmArchive(finca)" class="rounded-lg border border-red-200 dark:border-red-800 px-3 py-2 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950">Archivar</button>
                        </div>
                    </article>
                </div>
            </div>
            <div v-else-if="search || Object.values(filters).some(Boolean)" class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin resultados</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Ninguna finca coincide con la búsqueda o los filtros aplicados.</p>
                <button type="button" class="mt-5 rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950" @click="clearFilters">Limpiar búsqueda y filtros</button>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Aún no tienes fincas</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Crea la primera para comenzar a registrar tus animales.</p>
                <button type="button" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">Crear finca</button>
            </div>
        </main>
        <Teleport to="body">
            <div v-if="pendingAction" class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeActionModal" @keydown.esc="closeActionModal">
                <section role="dialog" aria-modal="true" aria-labelledby="finca-action-title" class="w-full max-w-md rounded-2xl bg-white dark:bg-stone-900 p-6 shadow-xl">
                    <h2 id="finca-action-title" class="text-xl font-bold text-stone-900 dark:text-stone-100">{{ pendingAction.title }}</h2>
                    <p class="mt-3 text-sm text-stone-600 dark:text-stone-400">{{ pendingAction.message }}</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="actionProcessing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="closeActionModal">Cancelar</button>
                        <button type="button" :disabled="actionProcessing" :class="pendingAction.method === 'delete' || !pendingAction.activating ? 'bg-red-700 hover:bg-red-600' : 'bg-green-800 hover:bg-green-700'" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50" @click="submitAction">{{ actionProcessing ? 'Procesando...' : pendingAction.button }}</button>
                    </div>
                </section>
            </div>
            <div v-if="editingFinca || isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="editingFinca ? closeEditModal() : closeCreateModal()" @keydown.esc="editingFinca ? closeEditModal() : closeCreateModal()">
                <section role="dialog" aria-modal="true" aria-labelledby="edit-finca-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:bg-stone-900 shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 dark:border-stone-800 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="edit-finca-title" tabindex="-1" class="text-xl font-bold text-stone-900 dark:text-stone-100">{{ editingFinca ? 'Editar finca' : 'Registrar finca' }}</h2>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ editingFinca ? `Actualiza los datos de ${editingFinca.name}.` : 'Completa los datos de la nueva finca.' }}</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="editForm.processing" class="rounded-lg px-2 py-1 text-xl text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800 disabled:opacity-50" @click="editingFinca ? closeEditModal() : closeCreateModal()">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitFinca">
                        <div>
                            <label for="edit-finca-name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre de la finca *</label>
                            <input id="edit-finca-name" v-model="editForm.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="edit-finca-location" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Ubicación</label>
                                <input id="edit-finca-location" v-model="editForm.location" maxlength="255" placeholder="Municipio, departamento" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="editForm.errors.location" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.location }}</p>
                            </div>
                            <div>
                                <label for="edit-finca-area" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Extensión ({{ areaUnitLabel }})</label>
                                <input id="edit-finca-area" v-model="editForm.area_hectares" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="editForm.errors.area_hectares" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.area_hectares }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="edit-finca-description" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Descripción</label>
                            <textarea id="edit-finca-description" v-model="editForm.description" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                            <p v-if="editForm.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.description }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                            <input v-model="editForm.is_active" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                            Finca activa
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="editForm.processing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="editingFinca ? closeEditModal() : closeCreateModal()">Cancelar</button>
                            <button type="submit" :disabled="editForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ editForm.processing ? 'Guardando...' : (editingFinca ? 'Guardar cambios' : 'Guardar finca') }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </div>
</template>

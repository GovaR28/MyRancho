<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

defineProps({
    propietarios: { type: Array, required: true },
});

const page = usePage();
const isCreateModalOpen = ref(false);
const editingOwner = ref(null);
const pendingAction = ref(null);
const actionProcessing = ref(false);
const form = useForm({
    name: '',
    relationship: '',
    phone: '',
    notes: '',
    is_active: true,
});
const editForm = useForm({
    name: '',
    relationship: '',
    phone: '',
    notes: '',
    is_active: true,
});

function openCreateModal() {
    isCreateModalOpen.value = true;
    nextTick(() => document.getElementById('new-owner-name')?.focus());
}

function closeCreateModal() {
    if (form.processing) return;
    isCreateModalOpen.value = false;
}

function submit() {
    form.post('/propietarios', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
}

function openEditModal(owner) {
    editingOwner.value = owner;
    editForm.name = owner.name;
    editForm.relationship = owner.relationship ?? '';
    editForm.phone = owner.phone ?? '';
    editForm.notes = owner.notes ?? '';
    editForm.is_active = owner.is_active;
    editForm.clearErrors();
    nextTick(() => document.getElementById('edit-owner-name')?.focus());
}

function closeEditModal() {
    if (editForm.processing) return;
    editingOwner.value = null;
}

function submitEdit() {
    editForm.put(`/propietarios/${editingOwner.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingOwner.value = null;
        },
    });
}

function confirmStatusChange(owner) {
    const activating = !owner.is_active;
    pendingAction.value = {
        title: activating ? 'Activar propietario' : 'Desactivar propietario',
        message: activating
            ? `¿Deseas activar a ${owner.name}?`
            : `¿Deseas desactivar a ${owner.name}? Los animales conservarán el vínculo, pero no se podrá asignar a nuevos animales.`,
        button: activating ? 'Activar propietario' : 'Desactivar propietario',
        url: `/propietarios/${owner.id}/estado`,
        activating,
    };
}

function closeActionModal() {
    if (actionProcessing.value) return;
    pendingAction.value = null;
}

function submitAction() {
    if (!pendingAction.value || actionProcessing.value) return;
    actionProcessing.value = true;
    router.patch(pendingAction.value.url, {}, {
        preserveScroll: true,
        onFinish: () => {
            actionProcessing.value = false;
            pendingAction.value = null;
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
                    <h1 class="text-3xl font-bold text-stone-900">Propietarios de ganado</h1>
                    <p class="mt-1 text-stone-600">Registra familiares u otras personas dueñas de animales en tus fincas.</p>
                </div>
                <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">+ Agregar propietario</button>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ page.props.flash.success }}</div>

            <div v-if="propietarios.length" class="mt-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <div class="divide-y divide-stone-100">
                    <article v-for="owner in propietarios" :key="owner.id" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-stone-900">{{ owner.name }}</h2>
                                <span :class="owner.is_active ? 'bg-green-100 text-green-800' : 'bg-stone-100 text-stone-600'" class="rounded-full px-2.5 py-1 text-xs font-medium">
                                    {{ owner.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-stone-600">{{ owner.relationship || 'Sin parentesco especificado' }}<span v-if="owner.phone"> · {{ owner.phone }}</span></p>
                            <p class="mt-1 text-sm text-stone-500">{{ owner.animals_count }} animales asociados</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50" @click="openEditModal(owner)">Editar</button>
                            <button type="button" @click="confirmStatusChange(owner)" class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">
                                {{ owner.is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </div>
                    </article>
                </div>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900">Aún no hay propietarios registrados</h2>
                <p class="mt-2 text-sm text-stone-600">Agrégalos para asignarles sus animales, aunque estén en una finca tuya.</p>
                <button type="button" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">Agregar propietario</button>
            </div>
        </main>
        <Teleport to="body">
            <div v-if="pendingAction" class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeActionModal" @keydown.esc="closeActionModal">
                <section role="dialog" aria-modal="true" aria-labelledby="owner-action-title" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h2 id="owner-action-title" class="text-xl font-bold text-stone-900">{{ pendingAction.title }}</h2>
                    <p class="mt-3 text-sm text-stone-600">{{ pendingAction.message }}</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="actionProcessing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeActionModal">Cancelar</button>
                        <button type="button" :disabled="actionProcessing" :class="pendingAction.activating ? 'bg-green-800 hover:bg-green-700' : 'bg-red-700 hover:bg-red-600'" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50" @click="submitAction">{{ actionProcessing ? 'Procesando...' : pendingAction.button }}</button>
                    </div>
                </section>
            </div>
            <div v-if="editingOwner" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeEditModal" @keydown.esc="closeEditModal">
                <section role="dialog" aria-modal="true" aria-labelledby="edit-owner-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="edit-owner-title" tabindex="-1" class="text-xl font-bold text-stone-900">Editar propietario</h2>
                            <p class="mt-1 text-sm text-stone-600">Actualiza los datos de {{ editingOwner.name }}.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100" @click="closeEditModal">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitEdit">
                        <div>
                            <label for="edit-owner-name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre completo *</label>
                            <input id="edit-owner-name" v-model="editForm.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="edit-owner-relationship" class="mb-1.5 block text-sm font-medium text-stone-700">Parentesco o relación</label>
                                <input id="edit-owner-relationship" v-model="editForm.relationship" maxlength="100" placeholder="Hermana, socio, vecino..." class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="editForm.errors.relationship" class="mt-1 text-sm text-red-600">{{ editForm.errors.relationship }}</p>
                            </div>
                            <div>
                                <label for="edit-owner-phone" class="mb-1.5 block text-sm font-medium text-stone-700">Teléfono</label>
                                <input id="edit-owner-phone" v-model="editForm.phone" type="tel" maxlength="50" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="editForm.errors.phone" class="mt-1 text-sm text-red-600">{{ editForm.errors.phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="edit-owner-notes" class="mb-1.5 block text-sm font-medium text-stone-700">Notas</label>
                            <textarea id="edit-owner-notes" v-model="editForm.notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                            <p v-if="editForm.errors.notes" class="mt-1 text-sm text-red-600">{{ editForm.errors.notes }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700">
                            <input v-model="editForm.is_active" type="checkbox" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                            Propietario activo
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="editForm.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeEditModal">Cancelar</button>
                            <button type="submit" :disabled="editForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ editForm.processing ? 'Guardando...' : 'Guardar cambios' }}</button>
                        </div>
                    </form>
                </section>
            </div>
            <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeCreateModal" @keydown.esc="closeCreateModal">
                <section role="dialog" aria-modal="true" aria-labelledby="new-owner-modal-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="new-owner-modal-title" tabindex="-1" class="text-xl font-bold text-stone-900">Agregar propietario</h2>
                            <p class="mt-1 text-sm text-stone-600">Registra un familiar u otra persona propietaria de ganado.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100" @click="closeCreateModal">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submit">
                        <div>
                            <label for="new-owner-name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre completo *</label>
                            <input id="new-owner-name" v-model="form.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="new-owner-relationship" class="mb-1.5 block text-sm font-medium text-stone-700">Parentesco o relación</label>
                                <input id="new-owner-relationship" v-model="form.relationship" maxlength="100" placeholder="Hermana, socio, vecino..." class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="form.errors.relationship" class="mt-1 text-sm text-red-600">{{ form.errors.relationship }}</p>
                            </div>
                            <div>
                                <label for="new-owner-phone" class="mb-1.5 block text-sm font-medium text-stone-700">Teléfono</label>
                                <input id="new-owner-phone" v-model="form.phone" type="tel" maxlength="50" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="new-owner-notes" class="mb-1.5 block text-sm font-medium text-stone-700">Notas</label>
                            <textarea id="new-owner-notes" v-model="form.notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                            Propietario activo
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="form.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeCreateModal">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ form.processing ? 'Guardando...' : 'Guardar propietario' }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </div>
</template>

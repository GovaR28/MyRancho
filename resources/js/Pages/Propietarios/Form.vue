<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import HeaderActions from '../../Components/HeaderActions.vue';

const props = defineProps({
    propietario: { type: Object, default: null },
});

const form = useForm({
    name: props.propietario?.name ?? '',
    relationship: props.propietario?.relationship ?? '',
    phone: props.propietario?.phone ?? '',
    notes: props.propietario?.notes ?? '',
    is_active: props.propietario?.is_active ?? true,
});

function submit() {
    if (props.propietario) {
        form.put(`/propietarios/${props.propietario.id}`);
    } else {
        form.post('/propietarios');
    }
}
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
                <HeaderActions />
            </div>
        </header>
        <main class="mx-auto max-w-3xl px-4 py-8">
            <Link href="/propietarios" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Volver a propietarios</Link>
            <div class="mt-5 rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm sm:p-8">
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-100">{{ propietario ? 'Editar propietario' : 'Agregar propietario' }}</h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Este contacto pertenece a tu cuenta; podrás asignarlo a los animales registrados en tus fincas.</p>
                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre completo *</label>
                        <input id="name" v-model="form.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="relationship" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Parentesco o relación</label>
                            <input id="relationship" v-model="form.relationship" maxlength="100" placeholder="Hermana, socio, vecino..." class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="form.errors.relationship" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.relationship }}</p>
                        </div>
                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Teléfono</label>
                            <input id="phone" v-model="form.phone" type="tel" maxlength="50" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Notas</label>
                        <textarea id="notes" v-model="form.notes" rows="4" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.notes }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                        Propietario activo
                    </label>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-green-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar propietario' }}
                        </button>
                        <Link href="/propietarios" class="rounded-lg border border-stone-300 dark:border-stone-700 px-5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Cancelar</Link>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>

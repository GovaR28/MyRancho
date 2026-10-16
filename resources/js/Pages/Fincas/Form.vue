<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { areaUnitLabel, toDisplayArea, toStorageArea } from '../../Composables/usePreferences';
import HeaderActions from '../../Components/HeaderActions.vue';

const props = defineProps({
    finca: { type: Object, default: null },
});

const form = useForm({
    name: props.finca?.name ?? '',
    location: props.finca?.location ?? '',
    area_hectares: toDisplayArea(props.finca?.area_hectares),
    description: props.finca?.description ?? '',
    is_active: props.finca?.is_active ?? true,
});

function submit() {
    const options = {
        transform: (data) => ({ ...data, area_hectares: toStorageArea(data.area_hectares) }),
    };

    if (props.finca) {
        form.put(`/fincas/${props.finca.id}`, options);
    } else {
        form.post('/fincas', options);
    }
}

const page = usePage();
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
            <Link href="/fincas" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Volver a fincas</Link>
            <div class="mt-5 rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm sm:p-8">
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-100">{{ finca ? 'Editar finca' : 'Registrar finca' }}</h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Los campos marcados con * son obligatorios.</p>
                <div v-if="page.props.flash?.success" class="mt-4 rounded-lg bg-green-50 dark:bg-green-950 px-4 py-3 text-sm text-green-800 dark:text-green-400">{{ page.props.flash.success }}</div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre de la finca *</label>
                        <input id="name" v-model="form.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="location" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Ubicación</label>
                            <input id="location" v-model="form.location" maxlength="255" placeholder="Municipio, departamento" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="form.errors.location" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.location }}</p>
                        </div>
                        <div>
                            <label for="area_hectares" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Extensión ({{ areaUnitLabel }})</label>
                            <input id="area_hectares" v-model="form.area_hectares" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="form.errors.area_hectares" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.area_hectares }}</p>
                        </div>
                    </div>
                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Descripción</label>
                        <textarea id="description" v-model="form.description" rows="4" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.description }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                        Finca activa
                    </label>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-green-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar finca' }}
                        </button>
                        <Link href="/fincas" class="rounded-lg border border-stone-300 dark:border-stone-700 px-5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Cancelar</Link>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>

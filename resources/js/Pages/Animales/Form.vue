<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';
import { areaUnitLabel, toDisplayWeight, toStorageArea, toStorageWeight, weightUnitLabel } from '../../Composables/usePreferences';

const props = defineProps({
    animal: { type: Object, default: null },
    fincas: { type: Array, required: true },
    mothers: { type: Array, required: true },
    fathers: { type: Array, required: true },
    propietarios: { type: Array, required: true },
    nextEarTag: { type: String, default: '' },
    nextOffspringEarTag: { type: String, default: '' },
});

const form = useForm({
    finca_id: props.animal?.finca_id ?? '',
    propietario_id: props.animal?.propietario_id ?? '',
    ear_tag: props.animal?.ear_tag ?? '',
    name: props.animal?.name ?? '',
    sex: props.animal?.sex ?? '',
    acquisition_type: props.animal?.acquisition_type ?? '',
    purchase_source: props.animal?.purchase_source ?? '',
    breed: props.animal?.breed ?? props.animal?.purchased_breed ?? '',
    color: props.animal?.color ?? '',
    birth_date: props.animal?.birth_date ?? '',
    birth_weight_lb: toDisplayWeight(props.animal?.birth_weight_kg),
    reproduction_method: props.animal?.reproduction_method ?? '',
    reproduction_method_other: props.animal?.reproduction_method_other ?? '',
    purchase_price: props.animal?.purchase_price ?? '',
    purchase_weight_lb: toDisplayWeight(props.animal?.purchase_weight_kg),
    purchase_date: props.animal?.purchase_date ?? '',
    seller_name: props.animal?.seller_name ?? '',
    age_at_purchase_months: props.animal?.age_at_purchase_months ?? '',
    came_with_offspring: false,
    offspring_ear_tag: '',
    offspring_sex: '',
    offspring_name: '',
    offspring_birth_date: '',
    offspring_weight_lb: '',
    record_type: props.animal?.record_type ?? 'comercial',
    registration_number: props.animal?.registration_number ?? '',
    mother_id: props.animal?.mother_unknown ? 'unknown' : (props.animal?.mother_id ?? ''),
    father_id: props.animal?.father_unknown ? 'unknown' : (props.animal?.father_id ?? ''),
    photo: null,
    notes: props.animal?.notes ?? '',
});

const activeCreateModal = ref(null);
const propietarioForm = useForm({
    name: '',
    relationship: '',
    phone: '',
    notes: '',
    is_active: true,
    return_to_animal: props.animal?.id ?? 'create',
});
const fincaForm = useForm({
    name: '',
    location: '',
    area_hectares: '',
    description: '',
    is_active: true,
    return_to_animal: props.animal?.id ?? 'create',
});

watch(() => [form.acquisition_type, form.sex], ([acquisitionType, sex]) => {
    if (acquisitionType !== 'purchased' || sex !== 'hembra') {
        form.came_with_offspring = false;
        form.offspring_ear_tag = '';
        form.offspring_sex = '';
        form.offspring_name = '';
        form.offspring_birth_date = '';
        form.offspring_weight_lb = '';
    }
});

function openCreateModal(type) {
    activeCreateModal.value = type;
    nextTick(() => document.getElementById(`${type}-modal-title`)?.focus());
}

function closeCreateModal() {
    if (propietarioForm.processing || fincaForm.processing) return;
    activeCreateModal.value = null;
}

function submitPropietario() {
    propietarioForm.post('/propietarios', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            form.propietario_id = page.props.flash.created_propietario_id;
            activeCreateModal.value = null;
            propietarioForm.reset();
        },
    });
}

function submitFinca() {
    fincaForm.post('/fincas', {
        preserveState: true,
        preserveScroll: true,
        transform: (data) => ({ ...data, area_hectares: toStorageArea(data.area_hectares) }),
        onSuccess: (page) => {
            form.finca_id = page.props.flash.created_finca_id;
            activeCreateModal.value = null;
            fincaForm.reset();
        },
    });
}

function submit() {
    const options = {
        forceFormData: true,
        transform: (data) => ({
            ...data,
            birth_weight_kg: toStorageWeight(data.birth_weight_lb),
            purchase_weight_kg: toStorageWeight(data.purchase_weight_lb),
            offspring_weight_kg: toStorageWeight(data.offspring_weight_lb),
            birth_weight_lb: undefined,
            purchase_weight_lb: undefined,
            offspring_weight_lb: undefined,
            mother_unknown: data.mother_id === 'unknown',
            mother_id: data.mother_id === 'unknown' ? '' : data.mother_id,
            father_unknown: data.father_id === 'unknown',
            father_id: data.father_id === 'unknown' ? '' : data.father_id,
        }),
    };
    if (props.animal) {
        form.put(`/animales/${props.animal.id}`, options);
    } else {
        form.post('/animales', options);
    }
}

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
            <div class="mx-auto max-w-4xl px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
            </div>
        </header>
        <main class="mx-auto max-w-4xl px-4 py-8">
            <Link href="/animales" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Volver a animales</Link>
            <div class="mt-5 rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm sm:p-8">
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-100">{{ animal ? 'Editar ficha del animal' : 'Registrar animal' }}</h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ animal ? 'Puedes ajustar el arete y los datos de la ficha.' : 'El número de arete se asigna automáticamente como consecutivo.' }}</p>
                <div v-if="page.props.flash?.success" class="mt-4 rounded-lg bg-green-50 dark:bg-green-950 px-4 py-3 text-sm text-green-800 dark:text-green-400">{{ page.props.flash.success }}</div>
                <div v-if="!fincas.length" class="mt-5 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950 p-4 text-sm text-amber-900 dark:text-amber-300">
                    No hay fincas activas disponibles. <button type="button" class="font-semibold underline" @click="openCreateModal('finca')">Registra una finca</button> para continuar.
                </div>
                <div v-if="!propietarios.length" class="mt-5 rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-950 p-4 text-sm text-sky-900 dark:text-sky-300">
                    Puedes registrar el ganado a tu nombre o <button type="button" class="font-semibold underline" @click="openCreateModal('propietario')">agregar un familiar como propietario</button>.
                </div>

                <form class="mt-6 space-y-6" @submit.prevent="submit">
                    <section>
                        <h2 class="mb-4 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Identificación</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div v-if="animal">
                                <label for="ear_tag" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Arete / identificador *</label>
                                <input id="ear_tag" v-model="form.ear_tag" required maxlength="100" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.ear_tag" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.ear_tag }}</p>
                            </div>
                            <div v-else class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-3 py-2.5">
                                <p class="text-xs font-medium uppercase tracking-wide text-green-800 dark:text-green-400">Arete asignado automáticamente</p>
                                <p class="mt-1 text-2xl font-bold text-green-900 dark:text-green-300">N° {{ nextEarTag }}</p>
                            </div>
                            <div>
                                <label for="finca_id" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Finca *</label>
                                <select id="finca_id" v-model="form.finca_id" required :disabled="!fincas.length" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona una finca</option>
                                    <option v-for="finca in fincas" :key="finca.id" :value="finca.id">{{ finca.name }}{{ finca.deleted_at ? ' (archivada)' : '' }}{{ !finca.deleted_at && !finca.is_active ? ' (inactiva)' : '' }}</option>
                                </select>
                                <p v-if="form.errors.finca_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.finca_id }}</p>
                            </div>
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <label for="propietario_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Propietario del animal</label>
                                    <button type="button" class="text-xs font-semibold text-green-700 dark:text-green-400 hover:underline" @click="openCreateModal('propietario')">+ Agregar familiar</button>
                                </div>
                                <select id="propietario_id" v-model="form.propietario_id" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="">Yo (titular de la cuenta)</option>
                                    <option v-for="owner in propietarios" :key="owner.id" :value="owner.id">
                                        {{ owner.name }}{{ owner.relationship ? ` · ${owner.relationship}` : '' }}{{ owner.deleted_at || !owner.is_active ? ' (inactivo)' : '' }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Si el animal es tuyo, déjalo a tu nombre. La finca y el dueño del ganado son independientes.</p>
                                <p v-if="form.errors.propietario_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.propietario_id }}</p>
                            </div>
                            <div>
                                <label for="name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre</label>
                                <input id="name" v-model="form.name" maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label for="sex" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Sexo *</label>
                                <select id="sex" v-model="form.sex" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona</option>
                                    <option value="hembra">Hembra</option>
                                    <option value="macho">Macho</option>
                                </select>
                                <p v-if="form.errors.sex" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.sex }}</p>
                            </div>
                            <div>
                                <label for="acquisition_type" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Tipo de ingreso *</label>
                                <select id="acquisition_type" v-model="form.acquisition_type" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona</option>
                                    <option value="born">Nacido en finca</option>
                                    <option value="purchased">Comprado</option>
                                </select>
                                <p v-if="form.errors.acquisition_type" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.acquisition_type }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'purchased'">
                                <label for="purchase_source" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">¿Dónde lo compraste? *</label>
                                <select id="purchase_source" v-model="form.purchase_source" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="" disabled>Selecciona</option>
                                    <option value="hacienda">Hacienda</option>
                                    <option value="feria">Feria</option>
                                    <option value="particular">Particular (vecino, otra hacienda)</option>
                                </select>
                                <p v-if="form.errors.purchase_source" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.purchase_source }}</p>
                            </div>
                            <div>
                                <label for="breed" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Raza</label>
                                <input id="breed" v-model="form.breed" maxlength="120" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.breed" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.breed }}</p>
                            </div>
                            <div>
                                <label for="color" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Color</label>
                                <input id="color" v-model="form.color" maxlength="120" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.color" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.color }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born'">
                                <label for="birth_date" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Fecha de nacimiento</label>
                                <input id="birth_date" v-model="form.birth_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.birth_date }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born'">
                                <label for="birth_weight_lb" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Peso al nacer ({{ weightUnitLabel }})</label>
                                <input id="birth_weight_lb" v-model="form.birth_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.birth_weight_kg" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.birth_weight_kg }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born'">
                                <label for="reproduction_method" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Método de reproducción</label>
                                <select id="reproduction_method" v-model="form.reproduction_method" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="">No especificado</option>
                                    <option value="natural">Monta natural</option>
                                    <option value="insemination">Inseminación artificial</option>
                                    <option value="embryo_transfer">Transferencia de embrión</option>
                                    <option value="other">Otra</option>
                                </select>
                                <p v-if="form.errors.reproduction_method" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.reproduction_method }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born' && form.reproduction_method === 'other'">
                                <label for="reproduction_method_other" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Describe el método *</label>
                                <input id="reproduction_method_other" v-model="form.reproduction_method_other" maxlength="120" placeholder="Ej. Transferencia de embriones casera, compra de preñez" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.reproduction_method_other" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.reproduction_method_other }}</p>
                            </div>
                            <div>
                                <label for="photo" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Foto (máximo 5 MB)</label>
                                <input id="photo" type="file" accept="image/*" @input="form.photo = $event.target.files[0]" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-green-50 dark:file:bg-green-950 file:px-3 file:py-1.5 file:font-medium file:text-green-800 dark:file:text-green-400">
                                <p v-if="animal?.photo_path" class="mt-1 text-xs text-stone-500 dark:text-stone-400">Ya hay una foto; subir otra la reemplazará.</p>
                                <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.photo }}</p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 border-b border-stone-100 dark:border-stone-800 pb-2 text-base font-semibold text-stone-800 dark:text-stone-200">Registro y genealogía</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="record_type" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Tipo de registro *</label>
                                <select id="record_type" v-model="form.record_type" required class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="comercial">Comercial</option>
                                    <option value="puro">Puro</option>
                                    <option value="asociacion">Registrado en asociación</option>
                                </select>
                                <p v-if="form.errors.record_type" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.record_type }}</p>
                            </div>
                            <div v-if="form.record_type === 'asociacion' || form.record_type === 'puro'">
                                <label for="registration_number" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Número de registro</label>
                                <input id="registration_number" v-model="form.registration_number" maxlength="120" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="form.errors.registration_number" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.registration_number }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born'">
                                <label for="mother_id" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Madre</label>
                                <select id="mother_id" v-model="form.mother_id" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="">No especificada</option>
                                    <option value="unknown">Desconocida</option>
                                    <option v-for="mother in mothers" :key="mother.id" :value="mother.id">{{ mother.ear_tag }}{{ mother.name ? ` · ${mother.name}` : '' }}{{ mother.deleted_at ? ' (archivada)' : '' }}</option>
                                </select>
                                <p v-if="form.errors.mother_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.mother_id }}</p>
                            </div>
                            <div v-if="form.acquisition_type === 'born'">
                                <label for="father_id" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Padre</label>
                                <select id="father_id" v-model="form.father_id" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <option value="">No especificado</option>
                                    <option value="unknown">Desconocido</option>
                                    <option v-for="father in fathers" :key="father.id" :value="father.id">{{ father.ear_tag }}{{ father.name ? ` · ${father.name}` : '' }}{{ father.deleted_at ? ' (archivado)' : '' }}</option>
                                </select>
                                <p v-if="form.errors.father_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.father_id }}</p>
                            </div>
                            <template v-if="form.acquisition_type === 'purchased'">
                                <div>
                                    <label for="purchase_price" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Precio de compra</label>
                                    <input id="purchase_price" v-model="form.purchase_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.purchase_price" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.purchase_price }}</p>
                                </div>
                                <div>
                                    <label for="purchase_weight_lb" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Peso al comprar ({{ weightUnitLabel }})</label>
                                    <input id="purchase_weight_lb" v-model="form.purchase_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.purchase_weight_kg" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.purchase_weight_kg }}</p>
                                </div>
                                <div>
                                    <label for="purchase_date" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Fecha de compra</label>
                                    <input id="purchase_date" v-model="form.purchase_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.purchase_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.purchase_date }}</p>
                                </div>
                                <div>
                                    <label for="seller_name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Vendedor</label>
                                    <input id="seller_name" v-model="form.seller_name" maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.seller_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.seller_name }}</p>
                                </div>
                                <div>
                                    <label for="age_at_purchase_months" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Edad al momento de compra (meses)</label>
                                    <input id="age_at_purchase_months" v-model="form.age_at_purchase_months" type="number" min="0" max="1000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                    <p v-if="form.errors.age_at_purchase_months" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.age_at_purchase_months }}</p>
                                </div>
                                <div v-if="!animal && form.sex === 'hembra'" class="sm:col-span-2 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950 p-4">
                                    <label class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-300">
                                        <input v-model="form.came_with_offspring" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                                        La compré con su cría
                                    </label>
                                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Se crearán las fichas de la vaca y de su cría, vinculada como hija.</p>
                                    <p v-if="form.errors.came_with_offspring" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.came_with_offspring }}</p>
                                    <div v-if="form.came_with_offspring" class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col justify-center rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-3 py-2">
                                            <p class="text-xs font-medium uppercase tracking-wide text-green-800 dark:text-green-400">Arete de la cría (automático)</p>
                                            <p class="mt-0.5 text-lg font-bold text-green-900 dark:text-green-300">N° {{ nextOffspringEarTag }}</p>
                                        </div>
                                        <div>
                                            <label for="offspring_sex" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Sexo de la cría *</label>
                                            <select id="offspring_sex" v-model="form.offspring_sex" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                                <option value="" disabled>Selecciona</option>
                                                <option value="hembra">Hembra</option>
                                                <option value="macho">Macho</option>
                                            </select>
                                            <p v-if="form.errors.offspring_sex" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.offspring_sex }}</p>
                                        </div>
                                        <div>
                                            <label for="offspring_name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre de la cría</label>
                                            <input id="offspring_name" v-model="form.offspring_name" maxlength="255" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                            <p v-if="form.errors.offspring_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.offspring_name }}</p>
                                        </div>
                                        <div>
                                            <label for="offspring_birth_date" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Fecha de nacimiento de la cría</label>
                                            <input id="offspring_birth_date" v-model="form.offspring_birth_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                            <p v-if="form.errors.offspring_birth_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.offspring_birth_date }}</p>
                                        </div>
                                        <div>
                                            <label for="offspring_weight_lb" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Peso de la cría ({{ weightUnitLabel }})</label>
                                            <input id="offspring_weight_lb" v-model="form.offspring_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                            <p v-if="form.errors.offspring_weight_kg" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.offspring_weight_kg }}</p>
                                        </div>
                                        <p class="text-xs text-stone-500 dark:text-stone-400 sm:col-span-2">La cría quedará ligada a esta vaca como su madre y compartirá finca, vendedor y fecha de compra.</p>
                                    </div>
                                </div>
                            </template>
                            <div class="sm:col-span-2">
                                <label for="notes" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Notas</label>
                                <textarea id="notes" v-model="form.notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.notes }}</p>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" :disabled="form.processing || !fincas.length" class="rounded-lg bg-green-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar animal' }}
                        </button>
                        <Link href="/animales" class="rounded-lg border border-stone-300 dark:border-stone-700 px-5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Cancelar</Link>
                    </div>
                </form>
            </div>
        </main>
        <Teleport to="body">
            <div v-if="activeCreateModal === 'propietario'" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeCreateModal" @keydown.esc="closeCreateModal">
                <section role="dialog" aria-modal="true" aria-labelledby="propietario-modal-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:bg-stone-900 shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 dark:border-stone-800 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="propietario-modal-title" tabindex="-1" class="text-xl font-bold text-stone-900 dark:text-stone-100">Agregar familiar</h2>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Registra un propietario sin salir del formulario del animal.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" class="rounded-lg px-2 py-1 text-xl text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" @click="closeCreateModal">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitPropietario">
                        <div>
                            <label for="modal_propietario_name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre completo *</label>
                            <input id="modal_propietario_name" v-model="propietarioForm.name" required maxlength="255" autofocus class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="propietarioForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ propietarioForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="modal_propietario_relationship" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Parentesco o relación</label>
                                <input id="modal_propietario_relationship" v-model="propietarioForm.relationship" maxlength="100" placeholder="Hermana, socio, vecino..." class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="propietarioForm.errors.relationship" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ propietarioForm.errors.relationship }}</p>
                            </div>
                            <div>
                                <label for="modal_propietario_phone" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Teléfono</label>
                                <input id="modal_propietario_phone" v-model="propietarioForm.phone" type="tel" maxlength="50" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="propietarioForm.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ propietarioForm.errors.phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="modal_propietario_notes" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Notas</label>
                            <textarea id="modal_propietario_notes" v-model="propietarioForm.notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                            <p v-if="propietarioForm.errors.notes" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ propietarioForm.errors.notes }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                            <input v-model="propietarioForm.is_active" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                            Propietario activo
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="propietarioForm.processing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="closeCreateModal">Cancelar</button>
                            <button type="submit" :disabled="propietarioForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ propietarioForm.processing ? 'Guardando...' : 'Guardar propietario' }}</button>
                        </div>
                    </form>
                </section>
            </div>
            <div v-if="activeCreateModal === 'finca'" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeCreateModal" @keydown.esc="closeCreateModal">
                <section role="dialog" aria-modal="true" aria-labelledby="finca-modal-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:bg-stone-900 shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 dark:border-stone-800 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="finca-modal-title" tabindex="-1" class="text-xl font-bold text-stone-900 dark:text-stone-100">Registrar finca</h2>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Completa los datos sin salir del formulario del animal.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" class="rounded-lg px-2 py-1 text-xl text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" @click="closeCreateModal">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitFinca">
                        <div>
                            <label for="modal_finca_name" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Nombre de la finca *</label>
                            <input id="modal_finca_name" v-model="fincaForm.name" required maxlength="255" autofocus class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                            <p v-if="fincaForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fincaForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="modal_finca_location" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Ubicación</label>
                                <input id="modal_finca_location" v-model="fincaForm.location" maxlength="255" placeholder="Municipio, departamento" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="fincaForm.errors.location" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fincaForm.errors.location }}</p>
                            </div>
                            <div>
                                <label for="modal_finca_area" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Extensión ({{ areaUnitLabel }})</label>
                                <input id="modal_finca_area" v-model="fincaForm.area_hectares" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900">
                                <p v-if="fincaForm.errors.area_hectares" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fincaForm.errors.area_hectares }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="modal_finca_description" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Descripción</label>
                            <textarea id="modal_finca_description" v-model="fincaForm.description" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2.5 outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"></textarea>
                            <p v-if="fincaForm.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fincaForm.errors.description }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                            <input v-model="fincaForm.is_active" type="checkbox" class="rounded border-stone-300 dark:border-stone-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                            Finca activa
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="fincaForm.processing" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 disabled:opacity-50" @click="closeCreateModal">Cancelar</button>
                            <button type="submit" :disabled="fincaForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ fincaForm.processing ? 'Guardando...' : 'Guardar finca' }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </div>
</template>

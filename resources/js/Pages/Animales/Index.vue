<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { nextTick, ref, watch } from 'vue';

defineProps({
    animals: { type: Object, required: true },
});

const poundsPerKilogram = 2.2046226218;
const toPounds = (kilograms) => kilograms === null || kilograms === undefined || kilograms === ''
    ? ''
    : Number((Number(kilograms) * poundsPerKilogram).toFixed(2));
const toKilograms = (pounds) => pounds === null || pounds === undefined || pounds === ''
    ? null
    : Number((Number(pounds) / poundsPerKilogram).toFixed(2));

const page = usePage();
const animalModalOpen = ref(false);
const previewAnimal = ref(null);
const photoPreviewDialog = ref(null);
const isFincaModalOpen = ref(false);
const editingAnimal = ref(null);
const loadingAnimal = ref(false);
const animalLoadError = ref('');
const pendingAction = ref(null);
const actionProcessing = ref(false);
const editForm = useForm({
    finca_id: '',
    propietario_id: '',
    ear_tag: '',
    name: '',
    sex: '',
    acquisition_type: '',
    purchase_source: '',
    breed: '',
    color: '',
    birth_date: '',
    birth_weight_lb: '',
    reproduction_method: '',
    reproduction_method_other: '',
    purchase_price: '',
    purchase_weight_lb: '',
    purchase_date: '',
    seller_name: '',
    age_at_purchase_months: '',
    came_with_offspring: false,
    offspring_ear_tag: '',
    offspring_sex: '',
    offspring_name: '',
    offspring_birth_date: '',
    offspring_weight_lb: '',
    record_type: 'comercial',
    registration_number: '',
    mother_id: '',
    father_id: '',
    photo: null,
    notes: '',
});
const editOptions = ref({ fincas: [], propietarios: [], mothers: [], fathers: [] });
const fincaForm = useForm({
    name: '',
    location: '',
    area_hectares: '',
    description: '',
    is_active: true,
    return_to_animal: 'index',
});
const saleForm = useForm({
    animal_ids: [],
    sale_date: new Date().toISOString().slice(0, 10),
    total_price: '',
    buyer_name: '',
    notes: '',
});
const sellingAnimal = ref(null);

async function openPhotoPreview(animal) {
    previewAnimal.value = animal;
    await nextTick();
    photoPreviewDialog.value?.focus();
}

function closePhotoPreview() {
    previewAnimal.value = null;
}

watch(() => [editForm.acquisition_type, editForm.sex], ([acquisitionType, sex]) => {
    if (acquisitionType !== 'purchased' || sex !== 'hembra') {
        editForm.came_with_offspring = false;
        editForm.offspring_ear_tag = '';
        editForm.offspring_sex = '';
        editForm.offspring_name = '';
        editForm.offspring_birth_date = '';
        editForm.offspring_weight_lb = '';
    }
});

function openSaleModal(animal) {
    sellingAnimal.value = animal;
    saleForm.reset();
    saleForm.clearErrors();
    saleForm.animal_ids = [animal.id];
    saleForm.sale_date = new Date().toISOString().slice(0, 10);
}

function closeSaleModal() {
    if (saleForm.processing) return;
    sellingAnimal.value = null;
}

function submitSale() {
    saleForm.post('/ventas', {
        preserveScroll: true,
        onSuccess: () => {
            sellingAnimal.value = null;
            saleForm.reset();
        },
    });
}

function openFincaModal() {
    fincaForm.reset();
    fincaForm.clearErrors();
    isFincaModalOpen.value = true;
    nextTick(() => document.getElementById('animal-list-finca-name')?.focus());
}

function closeFincaModal() {
    if (fincaForm.processing) return;
    isFincaModalOpen.value = false;
}

function submitFinca() {
    fincaForm.post('/fincas', {
        preserveScroll: true,
        onSuccess: () => {
            isFincaModalOpen.value = false;
            fincaForm.reset();
        },
    });
}

async function openCreateModal() {
    editingAnimal.value = null;
    animalModalOpen.value = true;
    loadingAnimal.value = true;
    animalLoadError.value = '';
    editForm.reset();
    editForm.transform((data) => data);
    editForm.clearErrors();
    editForm.record_type = 'comercial';
    editForm.acquisition_type = '';
    editForm.purchase_source = '';
    editForm.birth_weight_lb = '';
    editForm.reproduction_method = '';
    editForm.reproduction_method_other = '';
    editForm.purchase_price = '';
    editForm.purchase_weight_lb = '';
    editForm.purchase_date = '';
    editForm.seller_name = '';
    editForm.age_at_purchase_months = '';
    editForm.came_with_offspring = false;
    editForm.offspring_ear_tag = '';
    editForm.offspring_sex = '';
    editForm.offspring_name = '';
    editForm.offspring_birth_date = '';
    editForm.offspring_weight_lb = '';

    try {
        const { data } = await axios.get('/animales/create', {
            headers: { Accept: 'application/json' },
        });
        editOptions.value = {
            fincas: data.fincas,
            propietarios: data.propietarios,
            mothers: data.mothers,
            fathers: data.fathers,
        };
        loadingAnimal.value = false;
        await nextTick();
        document.getElementById('edit-animal-ear-tag')?.focus();
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        animalLoadError.value = 'No se pudieron cargar los datos para registrar el animal. Inténtalo de nuevo.';
        loadingAnimal.value = false;
    }
}

async function openEditModal(animal) {
    editingAnimal.value = animal;
    animalModalOpen.value = true;
    loadingAnimal.value = true;
    animalLoadError.value = '';

    try {
        const { data } = await axios.get(`/animales/${animal.id}/edit`, {
            headers: { Accept: 'application/json' },
        });
        for (const key of Object.keys(editForm.data())) {
            editForm[key] = data.animal[key] ?? '';
        }
        editForm.breed = data.animal.breed || data.animal.purchased_breed || '';
        editForm.birth_weight_lb = toPounds(data.animal.birth_weight_kg);
        editForm.purchase_weight_lb = toPounds(data.animal.purchase_weight_kg);
        editForm.came_with_offspring = false;
        editForm.offspring_ear_tag = '';
        editForm.offspring_sex = '';
        editForm.offspring_name = '';
        editForm.offspring_birth_date = '';
        editForm.offspring_weight_lb = '';
        editForm.mother_id = data.animal.mother_unknown ? 'unknown' : (data.animal.mother_id ?? '');
        editForm.father_id = data.animal.father_unknown ? 'unknown' : (data.animal.father_id ?? '');
        editForm.photo = null;
        editForm.clearErrors();
        editOptions.value = {
            fincas: data.fincas,
            propietarios: data.propietarios,
            mothers: data.mothers,
            fathers: data.fathers,
        };
        loadingAnimal.value = false;
        await nextTick();
        document.getElementById('edit-animal-ear-tag')?.focus();
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        animalLoadError.value = 'No se pudo cargar la ficha del animal. Inténtalo de nuevo.';
        loadingAnimal.value = false;
    }
}

function closeEditModal() {
    if (editForm.processing || loadingAnimal.value) return;
    animalModalOpen.value = false;
    editingAnimal.value = null;
}

function submitEdit() {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            animalModalOpen.value = false;
            editingAnimal.value = null;
            editForm.transform((data) => data);
        },
    };

    if (editingAnimal.value) {
        editForm.transform((data) => ({
            ...data,
            _method: 'put',
            birth_weight_kg: toKilograms(data.birth_weight_lb),
            purchase_weight_kg: toKilograms(data.purchase_weight_lb),
            offspring_weight_kg: toKilograms(data.offspring_weight_lb),
            birth_weight_lb: undefined,
            purchase_weight_lb: undefined,
            offspring_weight_lb: undefined,
            mother_unknown: data.mother_id === 'unknown',
            mother_id: data.mother_id === 'unknown' ? '' : data.mother_id,
            father_unknown: data.father_id === 'unknown',
            father_id: data.father_id === 'unknown' ? '' : data.father_id,
        })).post(`/animales/${editingAnimal.value.id}`, options);
    } else {
        editForm.transform((data) => ({
            ...data,
            birth_weight_kg: toKilograms(data.birth_weight_lb),
            purchase_weight_kg: toKilograms(data.purchase_weight_lb),
            offspring_weight_kg: toKilograms(data.offspring_weight_lb),
            birth_weight_lb: undefined,
            purchase_weight_lb: undefined,
            offspring_weight_lb: undefined,
            mother_unknown: data.mother_id === 'unknown',
            mother_id: data.mother_id === 'unknown' ? '' : data.mother_id,
            father_unknown: data.father_id === 'unknown',
            father_id: data.father_id === 'unknown' ? '' : data.father_id,
        })).post('/animales', options);
    }
}

function age(birthDate) {
    if (!birthDate) return 'Sin fecha registrada';

    const [year, month, day] = String(birthDate).slice(0, 10).split('-').map(Number);
    if (!year || !month || !day) return 'Sin fecha registrada';

    const birth = new Date(year, month - 1, day);
    if (Number.isNaN(birth.getTime())) return 'Sin fecha registrada';

    const now = new Date();
    let months = (now.getFullYear() - birth.getFullYear()) * 12 + now.getMonth() - birth.getMonth();
    if (now.getDate() < birth.getDate()) months -= 1;
    if (months < 0) return 'Menos de un mes';
    if (months < 12) return `${Math.max(months, 0)} meses`;

    const years = Math.floor(months / 12);
    const remainder = months % 12;
    return remainder ? `${years} a ${remainder} m` : `${years} años`;
}

function confirmArchive(animal) {
    pendingAction.value = {
        title: 'Archivar animal',
        message: `¿Archivar a ${animal.name || animal.ear_tag}? Sus vínculos genealógicos se conservarán.`,
        button: 'Archivar animal',
        url: `/animales/${animal.id}`,
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

    router.delete(pendingAction.value.url, options);
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
                    <h1 class="text-3xl font-bold text-stone-900">Animales</h1>
                    <p class="mt-1 text-stone-600">Fichas individuales, edad y vínculos genealógicos.</p>
                </div>
                <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">+ Nuevo animal</button>
            </div>
            <div v-if="page.props.flash?.success" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ page.props.flash.success }}</div>

            <div v-if="animals.data.length" class="mt-6 overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Animal / arete</th>
                            <th class="px-5 py-3">Finca</th>
                            <th class="px-5 py-3">Propietario</th>
                            <th class="px-5 py-3">Sexo / edad</th>
                            <th class="px-5 py-3">Raza</th>
                            <th class="px-5 py-3">Genealogía</th>
                            <th class="px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="animal in animals.data" :key="animal.id">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="animal.photo_url"
                                        type="button"
                                        :aria-label="`Ampliar foto de ${animal.name || animal.ear_tag}`"
                                        class="shrink-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2"
                                        @click="openPhotoPreview(animal)"
                                    >
                                        <img :src="animal.photo_url" :alt="`Foto de ${animal.name || animal.ear_tag}`" class="h-11 w-11 rounded-lg object-cover">
                                    </button>
                                    <div>
                                        <p class="font-semibold text-stone-900">{{ animal.name || 'Sin nombre' }} <span v-if="animal.ventas?.length" class="ml-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">Vendido</span></p>
                                        <p class="mt-0.5 text-stone-500">Arete: {{ animal.ear_tag }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-stone-700">{{ animal.finca?.name || 'Finca archivada' }}</td>
                            <td class="px-5 py-4 text-stone-700">{{ animal.propietario?.name || 'Yo (titular de la cuenta)' }}</td>
                            <td class="px-5 py-4 text-stone-700">
                                <span class="capitalize">{{ animal.sex }}</span>
                                <span class="block text-stone-500">
                                    <template v-if="animal.acquisition_type === 'purchased'">
                                        Tiempo desde compra: {{ age(animal.purchase_date) }}
                                    </template>
                                    <template v-else>Edad: {{ age(animal.birth_date) }}</template>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-stone-700">{{ animal.breed || '—' }}</td>
                            <td class="px-5 py-4 text-stone-600">
                                <span class="block">Madre: {{ animal.mother?.ear_tag || '—' }}</span>
                                <span class="block">Padre: {{ animal.father?.ear_tag || '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex gap-3">
                                    <button v-if="!animal.ventas?.length" type="button" class="font-medium text-green-800 hover:underline" @click="openSaleModal(animal)">Vender</button>
                                    <button type="button" class="font-medium text-green-800 hover:underline" @click="openEditModal(animal)">Editar</button>
                                    <button v-if="!animal.ventas?.length" type="button" @click="confirmArchive(animal)" class="font-medium text-red-700 hover:underline">Archivar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-stone-900">Aún no hay animales registrados</h2>
                <p class="mt-2 text-sm text-stone-600">Necesitas una finca activa para agregar el primer animal.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <button type="button" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700" @click="openCreateModal">Registrar animal</button>
                    <button type="button" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50" @click="openFincaModal">Crear finca</button>
                </div>
            </div>

            <nav v-if="animals.links.length > 3" class="mt-5 flex flex-wrap gap-2" aria-label="Paginación">
                <Link v-for="link in animals.links" :key="link.label" :href="link.url || '#'" preserve-scroll
                    :class="link.active ? 'border-green-800 bg-green-800 text-white' : 'border-stone-300 bg-white text-stone-700 hover:bg-stone-50'"
                    class="rounded-lg border px-3 py-2 text-sm"
                    v-html="link.label" />
            </nav>
        </main>
        <Teleport to="body">
            <div
                v-if="previewAnimal"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-stone-950/85 p-4"
                role="presentation"
                @click.self="closePhotoPreview"
                @keydown.esc="closePhotoPreview"
            >
                <section
                    ref="photoPreviewDialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="animal-photo-preview-title"
                    tabindex="-1"
                    class="relative flex max-h-[92vh] w-full max-w-5xl flex-col items-center outline-none"
                >
                    <div class="mb-3 flex w-full items-center justify-between gap-4 text-white">
                        <h2 id="animal-photo-preview-title" class="text-lg font-semibold">
                            {{ previewAnimal.name || 'Sin nombre' }} · Arete {{ previewAnimal.ear_tag }}
                        </h2>
                        <button
                            type="button"
                            aria-label="Cerrar vista previa"
                            class="rounded-lg px-3 py-1 text-2xl leading-none text-white hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white"
                            @click="closePhotoPreview"
                        >×</button>
                    </div>
                    <img
                        :src="previewAnimal.photo_url"
                        :alt="`Foto ampliada de ${previewAnimal.name || previewAnimal.ear_tag}`"
                        class="max-h-[82vh] max-w-full rounded-xl object-contain shadow-2xl"
                    >
                </section>
            </div>
            <div v-if="sellingAnimal" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeSaleModal" @keydown.esc="closeSaleModal">
                <section role="dialog" aria-modal="true" aria-labelledby="sell-animal-title" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="sell-animal-title" class="text-xl font-bold text-stone-900">Registrar salida del animal</h2>
                            <p class="mt-1 text-sm text-stone-600">{{ sellingAnimal.name || 'Sin nombre' }} · Arete {{ sellingAnimal.ear_tag }}</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="saleForm.processing" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100" @click="closeSaleModal">×</button>
                    </div>
                    <form class="mt-5 space-y-4" @submit.prevent="submitSale">
                        <div>
                            <label for="animal-sale-date" class="mb-1.5 block text-sm font-medium text-stone-700">Fecha de salida *</label>
                            <input id="animal-sale-date" v-model="saleForm.sale_date" type="date" required class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.sale_date" class="mt-1 text-sm text-red-600">{{ saleForm.errors.sale_date }}</p>
                        </div>
                        <div>
                            <label for="animal-sale-price" class="mb-1.5 block text-sm font-medium text-stone-700">Precio de venta *</label>
                            <input id="animal-sale-price" v-model="saleForm.total_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.total_price" class="mt-1 text-sm text-red-600">{{ saleForm.errors.total_price }}</p>
                        </div>
                        <div>
                            <label for="animal-sale-buyer" class="mb-1.5 block text-sm font-medium text-stone-700">Persona compradora *</label>
                            <input id="animal-sale-buyer" v-model="saleForm.buyer_name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="saleForm.errors.buyer_name" class="mt-1 text-sm text-red-600">{{ saleForm.errors.buyer_name }}</p>
                        </div>
                        <div>
                            <label for="animal-sale-notes" class="mb-1.5 block text-sm font-medium text-stone-700">Notas</label>
                            <textarea id="animal-sale-notes" v-model="saleForm.notes" rows="2" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="saleForm.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50" @click="closeSaleModal">Cancelar</button>
                            <button type="submit" :disabled="saleForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ saleForm.processing ? 'Guardando...' : 'Registrar salida' }}</button>
                        </div>
                    </form>
                </section>
            </div>
            <div v-if="isFincaModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeFincaModal" @keydown.esc="closeFincaModal">
                <section role="dialog" aria-modal="true" aria-labelledby="animal-list-finca-title" class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 px-6 pb-4 pt-6">
                        <div>
                            <h2 id="animal-list-finca-title" tabindex="-1" class="text-xl font-bold text-stone-900">Registrar finca</h2>
                            <p class="mt-1 text-sm text-stone-600">Completa los datos para agregar una finca.</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="fincaForm.processing" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100 disabled:opacity-50" @click="closeFincaModal">×</button>
                    </div>
                    <form class="overflow-y-auto px-6 pb-6 pt-5 space-y-4" @submit.prevent="submitFinca">
                        <div>
                            <label for="animal-list-finca-name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre de la finca *</label>
                            <input id="animal-list-finca-name" v-model="fincaForm.name" required maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                            <p v-if="fincaForm.errors.name" class="mt-1 text-sm text-red-600">{{ fincaForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="animal-list-finca-location" class="mb-1.5 block text-sm font-medium text-stone-700">Ubicación</label>
                                <input id="animal-list-finca-location" v-model="fincaForm.location" maxlength="255" placeholder="Municipio, departamento" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="fincaForm.errors.location" class="mt-1 text-sm text-red-600">{{ fincaForm.errors.location }}</p>
                            </div>
                            <div>
                                <label for="animal-list-finca-area" class="mb-1.5 block text-sm font-medium text-stone-700">Extensión (hectáreas)</label>
                                <input id="animal-list-finca-area" v-model="fincaForm.area_hectares" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                <p v-if="fincaForm.errors.area_hectares" class="mt-1 text-sm text-red-600">{{ fincaForm.errors.area_hectares }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="animal-list-finca-description" class="mb-1.5 block text-sm font-medium text-stone-700">Descripción</label>
                            <textarea id="animal-list-finca-description" v-model="fincaForm.description" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                            <p v-if="fincaForm.errors.description" class="mt-1 text-sm text-red-600">{{ fincaForm.errors.description }}</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-700">
                            <input v-model="fincaForm.is_active" type="checkbox" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                            Finca activa
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="fincaForm.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeFincaModal">Cancelar</button>
                            <button type="submit" :disabled="fincaForm.processing" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ fincaForm.processing ? 'Guardando...' : 'Guardar finca' }}</button>
                        </div>
                    </form>
                </section>
            </div>
            <div v-if="pendingAction" class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeActionModal" @keydown.esc="closeActionModal">
                <section role="dialog" aria-modal="true" aria-labelledby="animal-action-title" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h2 id="animal-action-title" class="text-xl font-bold text-stone-900">{{ pendingAction.title }}</h2>
                    <p class="mt-3 text-sm text-stone-600">{{ pendingAction.message }}</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="actionProcessing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeActionModal">Cancelar</button>
                        <button type="button" :disabled="actionProcessing" class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-600 disabled:opacity-50" @click="submitAction">{{ actionProcessing ? 'Procesando...' : pendingAction.button }}</button>
                    </div>
                </section>
            </div>
            <div v-if="animalModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4" @click.self="closeEditModal" @keydown.esc="closeEditModal">
                <section role="dialog" aria-modal="true" aria-labelledby="edit-animal-title" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-stone-100 px-6 pb-5 pt-6 sm:px-8 sm:pt-8">
                        <div>
                            <h2 id="edit-animal-title" tabindex="-1" class="text-xl font-bold text-stone-900">{{ editingAnimal ? 'Editar ficha del animal' : 'Registrar animal' }}</h2>
                            <p class="mt-1 text-sm text-stone-600">{{ editingAnimal ? `Arete: ${editingAnimal.ear_tag}` : 'Completa los datos para registrar un animal.' }}</p>
                        </div>
                        <button type="button" aria-label="Cerrar" :disabled="editForm.processing || loadingAnimal" class="rounded-lg px-2 py-1 text-xl text-stone-500 hover:bg-stone-100" @click="closeEditModal">×</button>
                    </div>
                    <p v-if="animalLoadError" class="mx-6 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 sm:mx-8">{{ animalLoadError }}</p>
                    <p v-else-if="loadingAnimal" class="px-6 py-8 text-center text-sm text-stone-600 sm:px-8">Cargando ficha...</p>
                    <form v-else class="overflow-y-auto px-6 pb-6 pt-5 space-y-6 sm:px-8 sm:pb-8" @submit.prevent="submitEdit">
                        <section>
                            <h3 class="mb-4 border-b border-stone-100 pb-2 text-base font-semibold text-stone-800">Identificación</h3>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-animal-ear-tag" class="mb-1.5 block text-sm font-medium text-stone-700">Arete / identificador *</label>
                                    <input id="edit-animal-ear-tag" v-model="editForm.ear_tag" required maxlength="100" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.ear_tag" class="mt-1 text-sm text-red-600">{{ editForm.errors.ear_tag }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-finca" class="mb-1.5 block text-sm font-medium text-stone-700">Finca *</label>
                                    <select id="edit-animal-finca" v-model="editForm.finca_id" required class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="" disabled>Selecciona una finca</option>
                                        <option v-for="finca in editOptions.fincas" :key="finca.id" :value="finca.id">{{ finca.name }}{{ finca.deleted_at ? ' (archivada)' : '' }}{{ !finca.deleted_at && !finca.is_active ? ' (inactiva)' : '' }}</option>
                                    </select>
                                    <p v-if="editForm.errors.finca_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.finca_id }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-owner" class="mb-1.5 block text-sm font-medium text-stone-700">Propietario del animal</label>
                                    <select id="edit-animal-owner" v-model="editForm.propietario_id" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="">Yo (titular de la cuenta)</option>
                                        <option v-for="owner in editOptions.propietarios" :key="owner.id" :value="owner.id">{{ owner.name }}{{ owner.relationship ? ` · ${owner.relationship}` : '' }}{{ owner.deleted_at || !owner.is_active ? ' (inactivo)' : '' }}</option>
                                    </select>
                                    <p v-if="editForm.errors.propietario_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.propietario_id }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre</label>
                                    <input id="edit-animal-name" v-model="editForm.name" maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-sex" class="mb-1.5 block text-sm font-medium text-stone-700">Sexo *</label>
                                    <select id="edit-animal-sex" v-model="editForm.sex" required class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="" disabled>Selecciona</option>
                                        <option value="hembra">Hembra</option>
                                        <option value="macho">Macho</option>
                                    </select>
                                    <p v-if="editForm.errors.sex" class="mt-1 text-sm text-red-600">{{ editForm.errors.sex }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-acquisition" class="mb-1.5 block text-sm font-medium text-stone-700">Tipo de ingreso *</label>
                                    <select id="edit-animal-acquisition" v-model="editForm.acquisition_type" required class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="" disabled>Selecciona</option>
                                        <option value="born">Nacido en finca</option>
                                        <option value="purchased">Comprado</option>
                                    </select>
                                    <p v-if="editForm.errors.acquisition_type" class="mt-1 text-sm text-red-600">{{ editForm.errors.acquisition_type }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'purchased'">
                                    <label for="edit-animal-purchase-source" class="mb-1.5 block text-sm font-medium text-stone-700">¿Dónde lo compraste? *</label>
                                    <select id="edit-animal-purchase-source" v-model="editForm.purchase_source" required class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="" disabled>Selecciona</option>
                                        <option value="hacienda">Hacienda</option>
                                        <option value="feria">Feria</option>
                                        <option value="particular">Particular (vecino, otra hacienda)</option>
                                    </select>
                                    <p v-if="editForm.errors.purchase_source" class="mt-1 text-sm text-red-600">{{ editForm.errors.purchase_source }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-breed" class="mb-1.5 block text-sm font-medium text-stone-700">Raza</label>
                                    <input id="edit-animal-breed" v-model="editForm.breed" maxlength="120" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.breed" class="mt-1 text-sm text-red-600">{{ editForm.errors.breed }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-color" class="mb-1.5 block text-sm font-medium text-stone-700">Color</label>
                                    <input id="edit-animal-color" v-model="editForm.color" maxlength="120" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.color" class="mt-1 text-sm text-red-600">{{ editForm.errors.color }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born'">
                                    <label for="edit-animal-birth-date" class="mb-1.5 block text-sm font-medium text-stone-700">Fecha de nacimiento</label>
                                    <input id="edit-animal-birth-date" v-model="editForm.birth_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.birth_date" class="mt-1 text-sm text-red-600">{{ editForm.errors.birth_date }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born'">
                                    <label for="edit-animal-birth-weight" class="mb-1.5 block text-sm font-medium text-stone-700">Peso al nacer (libras)</label>
                                    <input id="edit-animal-birth-weight" v-model="editForm.birth_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.birth_weight_kg" class="mt-1 text-sm text-red-600">{{ editForm.errors.birth_weight_kg }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born'">
                                    <label for="edit-animal-reproduction" class="mb-1.5 block text-sm font-medium text-stone-700">Método de reproducción</label>
                                    <select id="edit-animal-reproduction" v-model="editForm.reproduction_method" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="">No especificado</option>
                                        <option value="natural">Monta natural</option>
                                        <option value="insemination">Inseminación artificial</option>
                                        <option value="embryo_transfer">Transferencia de embrión</option>
                                        <option value="other">Otra</option>
                                    </select>
                                    <p v-if="editForm.errors.reproduction_method" class="mt-1 text-sm text-red-600">{{ editForm.errors.reproduction_method }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born' && editForm.reproduction_method === 'other'">
                                    <label for="edit-animal-reproduction-other" class="mb-1.5 block text-sm font-medium text-stone-700">Describe el método *</label>
                                    <input id="edit-animal-reproduction-other" v-model="editForm.reproduction_method_other" maxlength="120" placeholder="Ej. Transferencia de embriones casera, compra de preñez" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.reproduction_method_other" class="mt-1 text-sm text-red-600">{{ editForm.errors.reproduction_method_other }}</p>
                                </div>
                                <div>
                                    <label for="edit-animal-photo" class="mb-1.5 block text-sm font-medium text-stone-700">Foto (máximo 5 MB)</label>
                                    <input id="edit-animal-photo" type="file" accept="image/*" @input="editForm.photo = $event.target.files[0]" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-green-50 file:px-3 file:py-1.5 file:font-medium file:text-green-800">
                                    <p v-if="editingAnimal?.photo_path" class="mt-1 text-xs text-stone-500">Ya hay una foto; subir otra la reemplazará.</p>
                                    <p v-if="editForm.errors.photo" class="mt-1 text-sm text-red-600">{{ editForm.errors.photo }}</p>
                                </div>
                            </div>
                        </section>
                        <section>
                            <h3 class="mb-4 border-b border-stone-100 pb-2 text-base font-semibold text-stone-800">Registro y genealogía</h3>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-animal-record-type" class="mb-1.5 block text-sm font-medium text-stone-700">Tipo de registro *</label>
                                    <select id="edit-animal-record-type" v-model="editForm.record_type" required class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="comercial">Comercial</option>
                                        <option value="puro">Puro</option>
                                        <option value="asociacion">Registrado en asociación</option>
                                    </select>
                                    <p v-if="editForm.errors.record_type" class="mt-1 text-sm text-red-600">{{ editForm.errors.record_type }}</p>
                                </div>
                                <div v-if="editForm.record_type === 'asociacion' || editForm.record_type === 'puro'">
                                    <label for="edit-animal-registration" class="mb-1.5 block text-sm font-medium text-stone-700">Número de registro</label>
                                    <input id="edit-animal-registration" v-model="editForm.registration_number" maxlength="120" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                    <p v-if="editForm.errors.registration_number" class="mt-1 text-sm text-red-600">{{ editForm.errors.registration_number }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born'">
                                    <label for="edit-animal-mother" class="mb-1.5 block text-sm font-medium text-stone-700">Madre</label>
                                    <select id="edit-animal-mother" v-model="editForm.mother_id" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="">No especificada</option>
                                        <option value="unknown">Desconocida</option>
                                        <option v-for="mother in editOptions.mothers" :key="mother.id" :value="mother.id">{{ mother.ear_tag }}{{ mother.name ? ` · ${mother.name}` : '' }}{{ mother.deleted_at ? ' (archivada)' : '' }}</option>
                                    </select>
                                    <p v-if="editForm.errors.mother_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.mother_id }}</p>
                                </div>
                                <div v-if="editForm.acquisition_type === 'born'">
                                    <label for="edit-animal-father" class="mb-1.5 block text-sm font-medium text-stone-700">Padre</label>
                                    <select id="edit-animal-father" v-model="editForm.father_id" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <option value="">No especificado</option>
                                        <option value="unknown">Desconocido</option>
                                        <option v-for="father in editOptions.fathers" :key="father.id" :value="father.id">{{ father.ear_tag }}{{ father.name ? ` · ${father.name}` : '' }}{{ father.deleted_at ? ' (archivado)' : '' }}</option>
                                    </select>
                                    <p v-if="editForm.errors.father_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.father_id }}</p>
                                </div>
                                <template v-if="editForm.acquisition_type === 'purchased'">
                                    <div>
                                        <label for="edit-animal-purchase-price" class="mb-1.5 block text-sm font-medium text-stone-700">Precio de compra</label>
                                        <input id="edit-animal-purchase-price" v-model="editForm.purchase_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <p v-if="editForm.errors.purchase_price" class="mt-1 text-sm text-red-600">{{ editForm.errors.purchase_price }}</p>
                                    </div>
                                    <div>
                                        <label for="edit-animal-purchase-weight" class="mb-1.5 block text-sm font-medium text-stone-700">Peso al comprar (libras)</label>
                                        <input id="edit-animal-purchase-weight" v-model="editForm.purchase_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <p v-if="editForm.errors.purchase_weight_kg" class="mt-1 text-sm text-red-600">{{ editForm.errors.purchase_weight_kg }}</p>
                                    </div>
                                    <div>
                                        <label for="edit-animal-purchase-date" class="mb-1.5 block text-sm font-medium text-stone-700">Fecha de compra</label>
                                        <input id="edit-animal-purchase-date" v-model="editForm.purchase_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <p v-if="editForm.errors.purchase_date" class="mt-1 text-sm text-red-600">{{ editForm.errors.purchase_date }}</p>
                                    </div>
                                    <div>
                                        <label for="edit-animal-seller" class="mb-1.5 block text-sm font-medium text-stone-700">Vendedor</label>
                                        <input id="edit-animal-seller" v-model="editForm.seller_name" maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <p v-if="editForm.errors.seller_name" class="mt-1 text-sm text-red-600">{{ editForm.errors.seller_name }}</p>
                                    </div>
                                    <div>
                                        <label for="edit-animal-age-purchase" class="mb-1.5 block text-sm font-medium text-stone-700">Edad al momento de compra (meses)</label>
                                        <input id="edit-animal-age-purchase" v-model="editForm.age_at_purchase_months" type="number" min="0" max="1000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                        <p v-if="editForm.errors.age_at_purchase_months" class="mt-1 text-sm text-red-600">{{ editForm.errors.age_at_purchase_months }}</p>
                                    </div>
                                    <div v-if="!editingAnimal && editForm.sex === 'hembra'" class="sm:col-span-2 rounded-lg border border-stone-200 bg-stone-50 p-4">
                                        <label class="flex items-center gap-2 text-sm font-medium text-stone-700">
                                            <input v-model="editForm.came_with_offspring" type="checkbox" class="rounded border-stone-300 text-green-700 focus:ring-green-600">
                                            La compré con su cría
                                        </label>
                                        <p class="mt-1 text-xs text-stone-500">Se crearán las fichas de la vaca y de su cría, vinculada como hija.</p>
                                        <p v-if="editForm.errors.came_with_offspring" class="mt-1 text-sm text-red-600">{{ editForm.errors.came_with_offspring }}</p>
                                        <div v-if="editForm.came_with_offspring" class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="offspring_ear_tag" class="mb-1.5 block text-sm font-medium text-stone-700">Arete de la cría *</label>
                                                <input id="offspring_ear_tag" v-model="editForm.offspring_ear_tag" maxlength="100" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                                <p v-if="editForm.errors.offspring_ear_tag" class="mt-1 text-sm text-red-600">{{ editForm.errors.offspring_ear_tag }}</p>
                                            </div>
                                            <div>
                                                <label for="offspring_sex" class="mb-1.5 block text-sm font-medium text-stone-700">Sexo de la cría *</label>
                                                <select id="offspring_sex" v-model="editForm.offspring_sex" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                                    <option value="" disabled>Selecciona</option>
                                                    <option value="hembra">Hembra</option>
                                                    <option value="macho">Macho</option>
                                                </select>
                                                <p v-if="editForm.errors.offspring_sex" class="mt-1 text-sm text-red-600">{{ editForm.errors.offspring_sex }}</p>
                                            </div>
                                            <div>
                                                <label for="offspring_name" class="mb-1.5 block text-sm font-medium text-stone-700">Nombre de la cría</label>
                                                <input id="offspring_name" v-model="editForm.offspring_name" maxlength="255" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                                <p v-if="editForm.errors.offspring_name" class="mt-1 text-sm text-red-600">{{ editForm.errors.offspring_name }}</p>
                                            </div>
                                            <div>
                                                <label for="offspring_birth_date" class="mb-1.5 block text-sm font-medium text-stone-700">Fecha de nacimiento de la cría</label>
                                                <input id="offspring_birth_date" v-model="editForm.offspring_birth_date" type="date" :max="new Date().toISOString().slice(0, 10)" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                                <p v-if="editForm.errors.offspring_birth_date" class="mt-1 text-sm text-red-600">{{ editForm.errors.offspring_birth_date }}</p>
                                            </div>
                                            <div>
                                                <label for="offspring_weight_lb" class="mb-1.5 block text-sm font-medium text-stone-700">Peso de la cría (libras)</label>
                                                <input id="offspring_weight_lb" v-model="editForm.offspring_weight_lb" type="number" min="0" step="0.01" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100">
                                                <p v-if="editForm.errors.offspring_weight_kg" class="mt-1 text-sm text-red-600">{{ editForm.errors.offspring_weight_kg }}</p>
                                            </div>
                                            <p class="text-xs text-stone-500 sm:col-span-2">La cría quedará ligada a esta vaca como su madre y compartirá finca, vendedor y fecha de compra.</p>
                                        </div>
                                    </div>
                                </template>
                                <div class="sm:col-span-2">
                                    <label for="edit-animal-notes" class="mb-1.5 block text-sm font-medium text-stone-700">Notas</label>
                                    <textarea id="edit-animal-notes" v-model="editForm.notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-stone-300 px-3 py-2.5 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100"></textarea>
                                    <p v-if="editForm.errors.notes" class="mt-1 text-sm text-red-600">{{ editForm.errors.notes }}</p>
                                </div>
                            </div>
                        </section>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" :disabled="editForm.processing" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50" @click="closeEditModal">Cancelar</button>
                            <button type="submit" :disabled="editForm.processing || !editOptions.fincas.length" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ editForm.processing ? 'Guardando...' : (editingAnimal ? 'Guardar cambios' : 'Guardar animal') }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </div>
</template>

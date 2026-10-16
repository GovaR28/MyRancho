<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Buscar…' },
    filters: { type: Array, default: () => [] },
    activeFilters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue', 'apply', 'clear']);

const open = ref(false);
const menu = ref(null);
const draft = ref({ ...props.activeFilters });
const search = ref(props.modelValue);
let debounceId = null;

const activeCount = computed(() =>
    Object.values(props.activeFilters).filter((value) => value !== '' && value !== null && value !== undefined).length,
);

const chips = computed(() =>
    props.filters
        .filter((filter) => props.activeFilters[filter.key])
        .map((filter) => {
            const raw = props.activeFilters[filter.key];
            const option = filter.type !== 'date' ? filter.options.find((item) => String(item.value) === String(raw)) : null;

            return {
                key: filter.key,
                label: filter.label,
                value: option ? option.label : raw,
            };
        }),
);

function removeChip(key) {
    const next = { ...props.activeFilters };
    delete next[key];
    emit('apply', next);
}

watch(
    () => props.modelValue,
    (value) => {
        search.value = value;
    },
);

watch(search, (value) => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => emit('update:modelValue', value.trim()), 300);
});

watch(
    () => props.activeFilters,
    (value) => {
        if (!open.value) draft.value = { ...value };
    },
    { deep: true },
);

function toggle() {
    draft.value = { ...props.activeFilters };
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function apply() {
    emit('apply', { ...draft.value });
    open.value = false;
}

function clear() {
    draft.value = {};
    emit('clear');
    open.value = false;
}

function onDocumentClick(event) {
    if (open.value && menu.value && !menu.value.contains(event.target)) close();
}

function onKeydown(event) {
    if (event.key === 'Escape') close();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    clearTimeout(debounceId);
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <label for="list-search" class="sr-only">Buscar</label>
            <input
                id="list-search"
                v-model="search"
                type="search"
                :placeholder="placeholder"
                class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"
            >
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
        </div>

        <div v-if="filters.length" ref="menu" class="relative inline-block text-left">
            <button
                type="button"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-sm font-medium text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950 sm:w-auto"
                :aria-expanded="open"
                aria-haspopup="true"
                @click="toggle"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtros
                <span v-if="activeCount" class="rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-semibold text-green-800 dark:text-green-400">{{ activeCount }}</span>
            </button>

            <div
                v-if="open"
                class="absolute right-0 z-30 mt-2 w-72 rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-lg"
                role="menu"
            >
                <div class="space-y-4">
                    <div v-for="filter in filters" :key="filter.key">
                        <label :for="`filter-${filter.key}`" class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ filter.label }}</label>
                        <select
                            v-if="filter.type !== 'date'"
                            :id="`filter-${filter.key}`"
                            v-model="draft[filter.key]"
                            class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-3 py-2 text-sm outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"
                        >
                            <option value="">Todos</option>
                            <option v-for="option in filter.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <input
                            v-else
                            :id="`filter-${filter.key}`"
                            v-model="draft[filter.key]"
                            type="date"
                            class="w-full rounded-lg border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm outline-none focus:border-green-700 dark:focus:border-green-500 focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900"
                        >
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-3 border-t border-stone-100 dark:border-stone-800 pt-4">
                    <button type="button" class="text-sm font-medium text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300" @click="clear">Limpiar</button>
                    <button type="button" class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700" @click="apply">Aplicar</button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="chips.length" class="mt-3 flex flex-wrap gap-2">
        <span v-for="chip in chips" :key="chip.key" class="inline-flex items-center gap-1.5 rounded-full border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-3 py-1 text-xs font-medium text-green-800 dark:text-green-400">
            {{ chip.label }}: {{ chip.value }}
            <button type="button" class="-mr-1 rounded-full p-0.5 hover:bg-green-100 dark:hover:bg-green-900" :aria-label="`Quitar filtro ${chip.label}`" @click="removeChip(chip.key)">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </span>
    </div>
</template>

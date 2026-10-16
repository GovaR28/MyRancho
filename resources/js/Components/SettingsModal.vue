<script setup>
import { onBeforeUnmount, onMounted, computed } from 'vue';
import { useTheme } from '../Composables/useTheme';
import {
    ACCENT_OPTIONS,
    AREA_UNIT_OPTIONS,
    CURRENCIES,
    DATE_FORMAT_OPTIONS,
    DENSITY_OPTIONS,
    TEXT_SIZE_OPTIONS,
    WEIGHT_UNIT_OPTIONS,
    preferences,
    resetPreferences,
} from '../Composables/usePreferences';

const emit = defineEmits(['close']);

const { isDark, toggle: toggleTheme } = useTheme();

// Clases estáticas (Tailwind debe verlas literalmente) para las muestras de color.
const ACCENT_SWATCH = {
    green: 'bg-green-700',
    blue: 'bg-blue-700',
    indigo: 'bg-indigo-700',
    violet: 'bg-violet-700',
    teal: 'bg-teal-700',
    rose: 'bg-rose-700',
    amber: 'bg-amber-600',
};

const currencyOptions = computed(() =>
    Object.entries(CURRENCIES).map(([value, config]) => ({ value, label: config.label })),
);

function onKeydown(event) {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-stone-950/60 p-4 py-10 backdrop-blur-sm"
        @click.self="emit('close')"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-800 dark:bg-stone-900"
            role="dialog"
            aria-modal="true"
            aria-label="Configuración"
        >
            <header class="flex items-center justify-between border-b border-stone-100 px-5 py-4 dark:border-stone-800">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Configuración</h2>
                <button
                    type="button"
                    aria-label="Cerrar"
                    class="rounded-lg px-2 py-1 text-xl leading-none text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                    @click="emit('close')"
                >
                    ×
                </button>
            </header>

            <div class="max-h-[70vh] space-y-7 overflow-y-auto px-5 py-5">
                <!-- Apariencia -->
                <section class="space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">Apariencia</h3>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Modo oscuro</span>
                        <button
                            type="button"
                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                            :class="isDark ? 'bg-green-700' : 'bg-stone-300 dark:bg-stone-600'"
                            role="switch"
                            :aria-checked="isDark"
                            @click="toggleTheme"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                :class="isDark ? 'translate-x-[18px]' : 'translate-x-0.5'"
                            ></span>
                        </button>
                    </div>

                    <div>
                        <span class="mb-2 block text-sm font-medium text-stone-700 dark:text-stone-300">Color de acento</span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="option in ACCENT_OPTIONS"
                                :key="option.value"
                                type="button"
                                :title="option.label"
                                :aria-label="option.label"
                                :aria-pressed="preferences.accent === option.value"
                                class="h-8 w-8 rounded-full ring-offset-2 transition focus:outline-none focus:ring-2 focus:ring-stone-400 dark:ring-offset-stone-900"
                                :class="[ACCENT_SWATCH[option.value], preferences.accent === option.value ? 'ring-2 ring-stone-900 dark:ring-stone-100' : '']"
                                @click="preferences.accent = option.value"
                            ></button>
                        </div>
                    </div>

                    <div>
                        <span class="mb-2 block text-sm font-medium text-stone-700 dark:text-stone-300">Densidad</span>
                        <div class="inline-flex rounded-lg border border-stone-200 p-0.5 dark:border-stone-700">
                            <button
                                v-for="option in DENSITY_OPTIONS"
                                :key="option.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="preferences.density === option.value
                                    ? 'bg-green-800 text-white'
                                    : 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800'"
                                @click="preferences.density = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="mb-2 block text-sm font-medium text-stone-700 dark:text-stone-300">Tamaño de texto</span>
                        <div class="inline-flex rounded-lg border border-stone-200 p-0.5 dark:border-stone-700">
                            <button
                                v-for="option in TEXT_SIZE_OPTIONS"
                                :key="option.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="preferences.textSize === option.value
                                    ? 'bg-green-800 text-white'
                                    : 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800'"
                                @click="preferences.textSize = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Unidades y formato -->
                <section class="space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">Unidades y formato</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Moneda</span>
                            <select
                                v-model="preferences.currency"
                                class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-green-500 dark:focus:ring-green-900"
                            >
                                <option v-for="option in currencyOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Peso</span>
                            <select
                                v-model="preferences.weightUnit"
                                class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-green-500 dark:focus:ring-green-900"
                            >
                                <option v-for="option in WEIGHT_UNIT_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Superficie</span>
                            <select
                                v-model="preferences.areaUnit"
                                class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-green-500 dark:focus:ring-green-900"
                            >
                                <option v-for="option in AREA_UNIT_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-stone-700 dark:text-stone-300">Formato de fecha</span>
                            <select
                                v-model="preferences.dateFormat"
                                class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 outline-none focus:border-green-700 focus:ring-2 focus:ring-green-100 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-green-500 dark:focus:ring-green-900"
                            >
                                <option v-for="option in DATE_FORMAT_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>
                </section>

                <div class="flex justify-between border-t border-stone-100 pt-4 dark:border-stone-800">
                    <button
                        type="button"
                        class="text-sm font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200"
                        @click="resetPreferences"
                    >
                        Restablecer valores
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
                        @click="emit('close')"
                    >
                        Listo
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

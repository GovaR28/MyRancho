import { computed, reactive, watch } from 'vue';

const STORAGE_KEY = 'myrancho-prefs';

const round = (value, decimals = 2) => Number(Number(value).toFixed(decimals));

const POUNDS_PER_KILOGRAM = 2.2046226218;
const HECTARES_PER_ACRE = 0.40468564224;

export const DEFAULT_PREFERENCES = {
    accent: 'green',
    density: 'normal',
    textSize: 'md',
    currency: 'COP',
    weightUnit: 'lb',
    areaUnit: 'ha',
    dateFormat: 'dd/mm/yyyy',
};

export const CURRENCIES = {
    COP: { label: 'Peso colombiano (COP)', symbol: '$', locale: 'es-CO' },
    MXN: { label: 'Peso mexicano (MXN)', symbol: '$', locale: 'es-MX' },
    ARS: { label: 'Peso argentino (ARS)', symbol: '$', locale: 'es-AR' },
    CLP: { label: 'Peso chileno (CLP)', symbol: '$', locale: 'es-CL' },
    PEN: { label: 'Sol peruano (PEN)', symbol: 'S/', locale: 'es-PE' },
    USD: { label: 'Dólar estadounidense (USD)', symbol: '$', locale: 'en-US' },
    EUR: { label: 'Euro (EUR)', symbol: '€', locale: 'es-ES' },
};

export const ACCENT_OPTIONS = [
    { value: 'green', label: 'Verde' },
    { value: 'blue', label: 'Azul' },
    { value: 'indigo', label: 'Índigo' },
    { value: 'violet', label: 'Violeta' },
    { value: 'teal', label: 'Turquesa' },
    { value: 'rose', label: 'Rosa' },
    { value: 'amber', label: 'Ámbar' },
];

export const DENSITY_OPTIONS = [
    { value: 'compact', label: 'Compacta' },
    { value: 'normal', label: 'Normal' },
    { value: 'comfortable', label: 'Cómoda' },
];

export const TEXT_SIZE_OPTIONS = [
    { value: 'sm', label: 'Pequeño' },
    { value: 'md', label: 'Normal' },
    { value: 'lg', label: 'Grande' },
];

export const WEIGHT_UNIT_OPTIONS = [
    { value: 'kg', label: 'Kilogramos (kg)' },
    { value: 'lb', label: 'Libras (lb)' },
];

export const AREA_UNIT_OPTIONS = [
    { value: 'ha', label: 'Hectáreas (ha)' },
    { value: 'acre', label: 'Acres' },
];

export const DATE_FORMAT_OPTIONS = [
    { value: 'dd/mm/yyyy', label: 'DD/MM/AAAA' },
    { value: 'mm/dd/yyyy', label: 'MM/DD/AAAA' },
    { value: 'yyyy-mm-dd', label: 'AAAA-MM-DD' },
];

function load() {
    try {
        return { ...DEFAULT_PREFERENCES, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
    } catch (e) {
        return { ...DEFAULT_PREFERENCES };
    }
}

export const preferences = reactive(
    typeof localStorage === 'undefined' ? { ...DEFAULT_PREFERENCES } : load(),
);

function applyToRoot() {
    if (typeof document === 'undefined') return;

    const root = document.documentElement;
    root.dataset.accent = preferences.accent;
    root.dataset.density = preferences.density;
    root.dataset.textSize = preferences.textSize;
}

applyToRoot();

watch(
    preferences,
    () => {
        applyToRoot();

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        } catch (e) {
            /* almacenamiento no disponible */
        }
    },
    { deep: true },
);

export function resetPreferences() {
    Object.assign(preferences, DEFAULT_PREFERENCES);
}

/* ---- Etiquetas ---- */
export const weightUnitLabel = computed(() => (preferences.weightUnit === 'lb' ? 'libras' : 'kilogramos'));
export const areaUnitLabel = computed(() => (preferences.areaUnit === 'acre' ? 'acres' : 'hectáreas'));

/* ---- Peso (se guarda siempre en kilogramos) ---- */
export function toDisplayWeight(kilograms) {
    if (kilograms === null || kilograms === undefined || kilograms === '') return '';

    const value = Number(kilograms);
    if (!Number.isFinite(value)) return '';

    return round(preferences.weightUnit === 'lb' ? value * POUNDS_PER_KILOGRAM : value);
}

export function toStorageWeight(value) {
    if (value === null || value === undefined || value === '') return null;

    const number = Number(value);
    if (!Number.isFinite(number)) return null;

    return round(preferences.weightUnit === 'lb' ? number / POUNDS_PER_KILOGRAM : number);
}

/* ---- Superficie (se guarda siempre en hectáreas) ---- */
export function toDisplayArea(hectares) {
    if (hectares === null || hectares === undefined || hectares === '') return '';

    const value = Number(hectares);
    if (!Number.isFinite(value)) return '';

    return round(preferences.areaUnit === 'acre' ? value / HECTARES_PER_ACRE : value);
}

export function toStorageArea(value) {
    if (value === null || value === undefined || value === '') return null;

    const number = Number(value);
    if (!Number.isFinite(number)) return null;

    return round(preferences.areaUnit === 'acre' ? number * HECTARES_PER_ACRE : number);
}

export function formatArea(hectares) {
    const display = toDisplayArea(hectares);
    if (display === '') return '';

    return `${display} ${preferences.areaUnit === 'acre' ? 'acres' : 'ha'}`;
}

/* ---- Moneda ---- */
export function formatMoney(value) {
    const currency = CURRENCIES[preferences.currency] ?? CURRENCIES.COP;
    const number = Number(value);

    if (!Number.isFinite(number)) return '—';

    return (
        currency.symbol +
        number.toLocaleString(currency.locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

/* ---- Fechas ---- */
export function formatDate(value) {
    if (!value) return '—';

    const [year, month, day] = String(value).slice(0, 10).split('-');
    if (!year || !month || !day) return value;

    switch (preferences.dateFormat) {
        case 'mm/dd/yyyy':
            return `${month}/${day}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return `${day}/${month}/${year}`;
    }
}

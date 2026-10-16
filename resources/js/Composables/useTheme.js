import { ref } from 'vue';

const STORAGE_KEY = 'myrancho-theme';

// El script inline en app.blade.php ya deja el estado correcto al cargar la página,
// así que partimos de la clase actual de <html> para evitar parpadeos.
const isDark = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
);

function apply(dark) {
    isDark.value = dark;

    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', dark);
    }

    try {
        localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
    } catch (e) {
        /* almacenamiento no disponible */
    }
}

/**
 * Estado compartido del tema (claro/oscuro) con persistencia en localStorage.
 */
export function useTheme() {
    return {
        isDark,
        setDark: apply,
        toggle: () => apply(!isDark.value),
    };
}

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SettingsModal from './SettingsModal.vue';
import { useTheme } from '../Composables/useTheme';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const { isDark, toggle: toggleTheme } = useTheme();

const open = ref(false);
const settingsOpen = ref(false);
const menu = ref(null);

const initials = computed(() => {
    const name = (user.value?.name || '').trim();

    if (!name) return 'MR';

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

function toggleMenu() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function openSettings() {
    close();
    settingsOpen.value = true;
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
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="menu" class="relative">
        <button
            type="button"
            class="flex items-center gap-2 rounded-full border border-stone-200 bg-white py-1.5 pl-1.5 pr-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800"
            :aria-expanded="open"
            aria-haspopup="true"
            @click="toggleMenu"
        >
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-800 text-xs font-semibold uppercase text-white dark:bg-green-700">
                {{ initials }}
            </span>
            <span class="hidden max-w-[10rem] truncate sm:block">{{ user?.name || 'Mi cuenta' }}</span>
            <svg
                class="h-4 w-4 text-stone-400 transition-transform dark:text-stone-500"
                :class="{ 'rotate-180': open }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-800 dark:bg-stone-900"
            role="menu"
        >
            <div class="border-b border-stone-100 px-4 py-3 dark:border-stone-800">
                <p class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ user?.name || 'Mi cuenta' }}</p>
                <p v-if="user?.email" class="truncate text-xs text-stone-500 dark:text-stone-400">{{ user.email }}</p>
            </div>

            <div class="p-1.5">
                <Link
                    href="/perfil"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
                    role="menuitem"
                    @click="close"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Perfil de usuario
                </Link>

                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
                    role="menuitem"
                    @click="openSettings"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración
                </button>

                <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">Apariencia</p>

                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
                    role="menuitemcheckbox"
                    :aria-checked="isDark"
                    @click="toggleTheme"
                >
                    <span class="flex items-center gap-3">
                        <svg v-if="isDark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Modo oscuro
                    </span>
                    <span
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                        :class="isDark ? 'bg-green-700' : 'bg-stone-300 dark:bg-stone-600'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                            :class="isDark ? 'translate-x-[18px]' : 'translate-x-0.5'"
                        ></span>
                    </span>
                </button>
            </div>

            <div class="border-t border-stone-100 p-1.5 dark:border-stone-800">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950"
                    role="menuitem"
                    @click="close(); router.clearHistory?.()"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1" />
                    </svg>
                    Cerrar sesión
                </Link>
            </div>
        </div>

        <SettingsModal v-if="settingsOpen" @close="settingsOpen = false" />
    </div>
</template>

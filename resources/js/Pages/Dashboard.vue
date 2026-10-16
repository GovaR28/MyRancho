<script setup>
import { Link } from '@inertiajs/vue3';
import HeaderActions from '../Components/HeaderActions.vue';

defineProps({
    fincasCount: { type: Number, default: 0 },
    activeFincasCount: { type: Number, default: 0 },
    animalsCount: { type: Number, default: 0 },
    animalsNeedingPhotoCount: { type: Number, default: 0 },
});
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
                <HeaderActions />
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-10">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-green-700 dark:text-green-400">Resumen del rancho</p>
                <h1 class="mt-2 text-3xl font-bold text-stone-900 dark:text-stone-100">Panel principal</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">Administra tus fincas y lleva el historial de cada animal.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm">
                    <p class="text-sm text-stone-500 dark:text-stone-400">Fincas registradas</p>
                    <p class="mt-2 text-3xl font-bold text-stone-900 dark:text-stone-100">{{ fincasCount }}</p>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ activeFincasCount }} activas</p>
                </article>
                <article class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm">
                    <p class="text-sm text-stone-500 dark:text-stone-400">Animales registrados</p>
                    <p class="mt-2 text-3xl font-bold text-stone-900 dark:text-stone-100">{{ animalsCount }}</p>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Con arete e historial individual</p>
                </article>
                <article class="flex flex-col justify-between rounded-2xl bg-green-800 p-6 text-white shadow-sm">
                    <div>
                        <p class="text-lg font-semibold">¿Listo para empezar?</p>
                        <p class="mt-1 text-sm text-green-100">Registra primero una finca o agrega tu ganado.</p>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <Link href="/fincas/create" class="rounded-lg bg-white dark:bg-stone-900 px-4 py-2 text-sm font-semibold text-green-800 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950">Nueva finca</Link>
                        <Link href="/animales/create" class="rounded-lg border border-green-200 dark:border-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Nuevo animal</Link>
                    </div>
                </article>
            </div>

            <Link
                v-if="animalsNeedingPhotoCount > 0"
                href="/animales?foto=pendiente"
                class="mt-6 flex items-center justify-between gap-4 rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950 px-5 py-4 shadow-sm hover:bg-amber-100 dark:hover:bg-amber-900/50"
            >
                <div>
                    <p class="font-semibold text-amber-900 dark:text-amber-200">
                        {{ animalsNeedingPhotoCount }} {{ animalsNeedingPhotoCount === 1 ? 'animal necesita' : 'animales necesitan' }} actualizar la foto
                    </p>
                    <p class="mt-0.5 text-sm text-amber-800 dark:text-amber-300">Los chivos cambian mucho antes de los 2 años. Revisa y sube una foto reciente.</p>
                </div>
                <span class="whitespace-nowrap rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white">Ver</span>
            </Link>

            <section class="mt-8 rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Gestión</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    <Link href="/fincas" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Ver fincas</Link>
                    <Link href="/animales" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Ver animales y genealogía</Link>
                    <Link href="/propietarios" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Ver propietarios</Link>
                    <Link href="/lotes" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Administrar lotes</Link>
                    <Link href="/vacunaciones" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Vacunaciones del ganado</Link>
                    <Link href="/ventas" class="rounded-lg border border-stone-300 dark:border-stone-700 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-950">Ventas y salidas</Link>
                </div>
            </section>
        </main>
    </div>
</template>

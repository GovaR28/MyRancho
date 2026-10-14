<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    fincasCount: { type: Number, default: 0 },
    activeFincasCount: { type: Number, default: 0 },
    animalsCount: { type: Number, default: 0 },
});

const user = usePage().props.auth?.user;
</script>

<template>
    <div class="min-h-screen bg-stone-50">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800">MyRancho</Link>
                <div class="flex items-center gap-4 text-sm text-stone-600">
                    <span v-if="user">{{ user.name }}</span>
                    <Link href="/logout" method="post" as="button" @click="router.clearHistory()" class="font-medium text-green-700 hover:text-green-600">
                        Cerrar sesión
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-10">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-green-700">Resumen del rancho</p>
                <h1 class="mt-2 text-3xl font-bold text-stone-900">Panel principal</h1>
                <p class="mt-2 text-stone-600">Administra tus fincas y lleva el historial de cada animal.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Fincas registradas</p>
                    <p class="mt-2 text-3xl font-bold text-stone-900">{{ fincasCount }}</p>
                    <p class="mt-1 text-sm text-stone-500">{{ activeFincasCount }} activas</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Animales registrados</p>
                    <p class="mt-2 text-3xl font-bold text-stone-900">{{ animalsCount }}</p>
                    <p class="mt-1 text-sm text-stone-500">Con arete e historial individual</p>
                </article>
                <article class="flex flex-col justify-between rounded-2xl bg-green-800 p-6 text-white shadow-sm">
                    <div>
                        <p class="text-lg font-semibold">¿Listo para empezar?</p>
                        <p class="mt-1 text-sm text-green-100">Registra primero una finca o agrega tu ganado.</p>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <Link href="/fincas/create" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-green-800 hover:bg-green-50">Nueva finca</Link>
                        <Link href="/animales/create" class="rounded-lg border border-green-200 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Nuevo animal</Link>
                    </div>
                </article>
            </div>

            <section class="mt-8 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Gestión</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    <Link href="/fincas" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Ver fincas</Link>
                    <Link href="/animales" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Ver animales y genealogía</Link>
                    <Link href="/propietarios" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Ver propietarios</Link>
                    <Link href="/lotes" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Administrar lotes</Link>
                    <Link href="/vacunaciones" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Vacunaciones del ganado</Link>
                    <Link href="/ventas" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Ventas y salidas</Link>
                </div>
            </section>
        </main>
    </div>
</template>

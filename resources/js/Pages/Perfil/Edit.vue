<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import HeaderActions from '../../Components/HeaderActions.vue';

const page = usePage();
const user = page.props.auth?.user;

const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/perfil', {
        preserveScroll: true,
        onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <header class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/dashboard" class="text-xl font-bold text-green-800 dark:text-green-400">MyRancho</Link>
                <HeaderActions />
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <Link href="/dashboard" class="text-sm font-medium text-green-700 dark:text-green-400 hover:underline">← Panel principal</Link>

            <div class="mt-5">
                <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-100">Perfil de usuario</h1>
                <p class="mt-1 text-stone-600 dark:text-stone-400">Actualiza tus datos personales y tu contraseña.</p>
            </div>

            <div
                v-if="flashSuccess"
                class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-400"
            >
                {{ flashSuccess }}
            </div>

            <form class="mt-6 max-w-2xl space-y-6" @submit.prevent="submit">
                <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Datos personales</h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Estos datos se muestran en tu cuenta.</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="name" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Nombre completo</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                required
                                class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3.5 py-3 text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-green-700 focus:bg-white focus:ring-4 focus:ring-green-700/10 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-green-500 dark:focus:bg-stone-900 dark:focus:ring-green-400/10"
                                placeholder="Tu nombre"
                            >
                            <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Correo electrónico</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3.5 py-3 text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-green-700 focus:bg-white focus:ring-4 focus:ring-green-700/10 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-green-500 dark:focus:bg-stone-900 dark:focus:ring-green-400/10"
                                placeholder="tu@ejemplo.com"
                            >
                            <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Cambiar contraseña</h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Déjalo en blanco si no quieres cambiarla.</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="current_password" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Contraseña actual</label>
                            <input
                                id="current_password"
                                v-model="form.current_password"
                                type="password"
                                autocomplete="current-password"
                                class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3.5 py-3 text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-green-700 focus:bg-white focus:ring-4 focus:ring-green-700/10 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-green-500 dark:focus:bg-stone-900 dark:focus:ring-green-400/10"
                                placeholder="••••••••"
                            >
                            <p v-if="form.errors.current_password" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.current_password }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Nueva contraseña</label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3.5 py-3 text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-green-700 focus:bg-white focus:ring-4 focus:ring-green-700/10 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-green-500 dark:focus:bg-stone-900 dark:focus:ring-green-400/10"
                                    placeholder="Mínimo 8 caracteres"
                                >
                                <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Confirmar contraseña</label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3.5 py-3 text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-green-700 focus:bg-white focus:ring-4 focus:ring-green-700/10 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-green-500 dark:focus:bg-stone-900 dark:focus:ring-green-400/10"
                                    placeholder="Repite la contraseña"
                                >
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-green-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-green-700 dark:hover:bg-green-600"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                    <Link href="/dashboard" class="text-sm font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">Cancelar</Link>
                </div>
            </form>
        </main>
    </div>
</template>

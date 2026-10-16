<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800 px-4 py-12 sm:px-6">
        <div aria-hidden="true" class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-green-200/50 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-28 -right-20 h-80 w-80 rounded-full bg-amber-200/40 blur-3xl"></div>

        <section class="relative w-full max-w-lg rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-7 shadow-xl shadow-stone-900/5 sm:p-10">
            <header class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-green-800 text-2xl text-white shadow-lg shadow-green-900/20">
                    <span aria-hidden="true">M</span>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-green-900 dark:text-green-300">Únete a MyRancho</h1>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Crea tu cuenta para administrar tu rancho</p>
            </header>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Nombre completo</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        required
                        autofocus
                        class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-950 px-3.5 py-3 text-stone-900 dark:text-stone-100 outline-none transition placeholder:text-stone-400 dark:placeholder:text-stone-500 focus:border-green-700 dark:focus:border-green-500 focus:bg-white dark:focus:bg-stone-900 focus:ring-4 focus:ring-green-700/10 dark:focus:ring-green-400/10"
                        placeholder="Tu nombre"
                        :aria-invalid="Boolean(form.errors.name)"
                        aria-describedby="name-error"
                    >
                    <p v-if="form.errors.name" id="name-error" class="mt-1.5 text-sm text-red-700 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Correo electrónico</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-950 px-3.5 py-3 text-stone-900 dark:text-stone-100 outline-none transition placeholder:text-stone-400 dark:placeholder:text-stone-500 focus:border-green-700 dark:focus:border-green-500 focus:bg-white dark:focus:bg-stone-900 focus:ring-4 focus:ring-green-700/10 dark:focus:ring-green-400/10"
                        placeholder="tu@ejemplo.com"
                        :aria-invalid="Boolean(form.errors.email)"
                        aria-describedby="email-error"
                    >
                    <p v-if="form.errors.email" id="email-error" class="mt-1.5 text-sm text-red-700 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Contraseña</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-950 px-3.5 py-3 text-stone-900 dark:text-stone-100 outline-none transition placeholder:text-stone-400 dark:placeholder:text-stone-500 focus:border-green-700 dark:focus:border-green-500 focus:bg-white dark:focus:bg-stone-900 focus:ring-4 focus:ring-green-700/10 dark:focus:ring-green-400/10"
                            placeholder="Mínimo 8 caracteres"
                            :aria-invalid="Boolean(form.errors.password)"
                            aria-describedby="password-error"
                        >
                        <p v-if="form.errors.password" id="password-error" class="mt-1.5 text-sm text-red-700 dark:text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-stone-700 dark:text-stone-300">Confirmar contraseña</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-950 px-3.5 py-3 text-stone-900 dark:text-stone-100 outline-none transition placeholder:text-stone-400 dark:placeholder:text-stone-500 focus:border-green-700 dark:focus:border-green-500 focus:bg-white dark:focus:bg-stone-900 focus:ring-4 focus:ring-green-700/10 dark:focus:ring-green-400/10"
                            placeholder="Repite tu contraseña"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-semibold text-white shadow-md shadow-green-900/15 transition hover:bg-green-900 focus:outline-none focus:ring-4 focus:ring-green-700/20 dark:focus:ring-green-400/20 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span v-if="form.processing">Creando cuenta...</span>
                    <span v-else>Crear mi cuenta</span>
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-stone-600 dark:text-stone-400">
                ¿Ya tienes una cuenta?
                <Link href="/login" class="font-semibold text-green-800 dark:text-green-400 underline-offset-4 hover:underline">Inicia sesión</Link>
            </p>
        </section>
    </main>
</template>

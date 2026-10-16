<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-stone-100 dark:bg-stone-800 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white dark:bg-stone-900 p-10 rounded-xl shadow-lg border border-stone-200 dark:border-stone-800">
            
            <!-- Cabecera MyRancho -->
            <div class="text-center">
                <h2 class="mt-2 text-4xl font-extrabold text-green-800 dark:text-green-400 tracking-tight">
                    MyRancho
                </h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                    Sistema de Administración Ganadera
                </p>
            </div>

            <!-- Formulario -->
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4 shadow-sm">
                    <div>
                        <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Correo Electrónico</label>
                        <input id="email" v-model="form.email" type="email" required 
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-stone-300 dark:border-stone-700 rounded-md focus:outline-none focus:ring-green-600 focus:border-green-600 focus:z-10 sm:text-sm" 
                            placeholder="usuario@ejemplo.com">
                        <div v-if="form.errors.email" class="text-red-500 dark:text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Contraseña</label>
                        <input id="password" v-model="form.password" type="password" required 
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-stone-300 dark:border-stone-700 rounded-md focus:outline-none focus:ring-green-600 focus:border-green-600 focus:z-10 sm:text-sm" 
                            placeholder="••••••••">
                        <div v-if="form.errors.password" class="text-red-500 dark:text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" v-model="form.remember" type="checkbox" 
                            class="h-4 w-4 text-green-700 dark:text-green-400 focus:ring-green-600 border-stone-300 dark:border-stone-700 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-stone-900 dark:text-stone-100">
                            Recordarme
                        </label>
                    </div>

                    <div class="text-sm">
                        <!-- Reemplaza con tu ruta de recuperación -->
                        <Link href="/forgot-password" class="font-medium text-green-700 dark:text-green-400 hover:text-green-600 dark:hover:text-green-400">
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                </div>

                <div>
                    <button type="submit" :disabled="form.processing"
                        class="group relative w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition-colors disabled:opacity-50">
                        Ingresar al sistema
                    </button>
                </div>
                
                <div class="text-center mt-4">
                    <span class="text-sm text-stone-600 dark:text-stone-400">¿No tienes cuenta? </span>
                    <!-- Reemplaza con tu ruta de registro -->
                    <Link href="/register" class="text-sm font-medium text-green-700 dark:text-green-400 hover:text-green-600 dark:hover:text-green-400">
                        Regístrate aquí
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
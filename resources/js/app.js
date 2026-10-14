import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

let dashboardRefreshStarted = false;

const refreshDashboardAfterHistoryRestore = () => {
    const path = window.location.pathname.replace(/\/+$/, '');

    if (!dashboardRefreshStarted && path.endsWith('/dashboard')) {
        dashboardRefreshStarted = true;
        document.documentElement.style.visibility = 'hidden';
        window.location.reload();
    }
};

window.addEventListener('popstate', refreshDashboardAfterHistoryRestore);
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        refreshDashboardAfterHistoryRestore();
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - MyRancho` : 'MyRancho'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#15803d',
    },
});

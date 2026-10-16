<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'MyRancho') }}</title>
        <script>
            (function () {
                var root = document.documentElement;

                try {
                    var theme = localStorage.getItem('myrancho-theme');
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (theme === 'dark' || (theme === null && prefersDark)) {
                        root.classList.add('dark');
                    }

                    var prefs = JSON.parse(localStorage.getItem('myrancho-prefs') || '{}');

                    if (prefs.accent) root.dataset.accent = prefs.accent;
                    if (prefs.density) root.dataset.density = prefs.density;
                    if (prefs.textSize) root.dataset.textSize = prefs.textSize;
                } catch (e) {
                    /* almacenamiento no disponible */
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EWS RSUD Depati Bahrin') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Apply dark mode immediately -->
        <script>
            (function() {
                const savedTheme = localStorage.getItem('theme');
                const theme = savedTheme || 'light';
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.body.classList.add('dark', 'bg-gray-900');
                }
            })();
        </script>
    </head>
    <body class="font-outfit text-gray-900 antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <!-- Logo + Title -->
            <div class="text-center" x-data>
                <a href="/" class="inline-flex flex-col items-center" wire:navigate>
                    <img src="{{ asset('images/logo-rsud-depati-bahrin.png') }}" alt="Logo RSUD" class="h-16 w-16 object-contain">
                    <span class="mt-4 text-xl font-bold text-gray-800 dark:text-white">{{ config('app.name', 'EWS RSUD Depati Bahrin') }}</span>
                    <span class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">Early Warning Score System</span>
                </a>
            </div>

            <!-- Form Card -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white dark:bg-gray-800 shadow-theme-lg overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">
                &copy; {{ date('Y') }} RSUD Depati Bahrin. All rights reserved.
            </p>
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="text-center">
                <a href="/" class="inline-flex flex-col items-center" wire:navigate>
                    <img
                        src="{{ asset('images/logo-rsud-depati-bahrin.png') }}"
                        alt="Logo RSUD Depati Bahrin"
                        class="h-20 w-20 object-contain"
                    >
                    <span class="mt-4 text-xl font-bold text-gray-800">{{ config('app.name', 'EWS RSUD Depati Bahrin') }}</span>
                    <span class="mt-1 text-sm font-medium text-gray-500">Early Warning Score System</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

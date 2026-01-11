<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Team Builder - {{ config('app.name', 'Pokemon Database') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('/images/special-ball-96.png') }}" type="image/png">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/teambuilder.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Pokemon Team Builder
                    </h1>
                </div>
                <div>
                    @auth
                        <a
                            href="{{ url('/') }}"
                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Back to Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div id="app"></div>
        </main>
    </div>
</body>
</html>

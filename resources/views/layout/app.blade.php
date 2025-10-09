<!DOCTYPE html>
<html data-theme="nmst" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'NMST' }} | No More Screen Time</title>

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-base-200 text-base-content font-inter">
    <div class="md:flex md:flex-col md:min-h-screen">
        <livewire:navbar />

        @yield('body')
    </div>
    <livewire:notifications.toast />
    @stack('scripts')
    @stack('modals')
</body>
</html>

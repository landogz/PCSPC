<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <x-brand.favicon />
    <x-layout.theme-boot />
    <script>
        (function () {
            const sidebarState = localStorage.getItem('hr-sidebar');
            if (sidebarState !== 'expanded') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/hrivo/css/index.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/hrivo/css/brand.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg text-text font-sans antialiased relative" data-page-title="{{ html_entity_decode(trim($__env->yieldContent('page-title', 'Dashboard')), ENT_QUOTES, 'UTF-8') }}">
    <x-layout.sidebar />
    <x-layout.topbar :title="html_entity_decode(trim($__env->yieldContent('page-title', 'Dashboard')), ENT_QUOTES, 'UTF-8')" />

    <main id="main-content" class="pt-20 ml-0 lg:ml-64 transition-all duration-300">
        <div class="p-3 md:p-5 space-y-4 md:space-y-5 pb-20! lg:pb-5!">
            @yield('content')
        </div>
    </main>

    <x-layout.account-modals />
    <x-layout.search-mega />
</body>
</html>

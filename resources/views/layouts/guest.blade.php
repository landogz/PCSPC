<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <x-brand.favicon />
    <x-layout.theme-boot />
    <link rel="stylesheet" href="{{ asset('vendor/hrivo/css/index.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/hrivo/css/brand.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg text-text font-sans antialiased" data-page-title="@yield('page-title', 'Sign In')">
    @yield('content')
</body>
</html>

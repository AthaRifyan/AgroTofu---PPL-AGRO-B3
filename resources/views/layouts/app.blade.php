<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fff8f5">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AgroTofu AI') }}</title>

        <!-- Font & ikon dimuat lokal lewat Vite (lihat resources/css/app.css) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-on-surface antialiased">
        @include('layouts.navigation')

        <div class="lg:pl-72">
            <main class="mx-auto w-full max-w-6xl px-4 pb-32 pt-5 sm:px-6 lg:px-8 lg:pb-12 lg:pt-28">
                {{-- Slot $header tetap didukung untuk halaman lama --}}
                @isset($header)
                    <div class="mb-5 text-headline-md text-on-surface">{{ $header }}</div>
                @endisset

                <x-flash />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>

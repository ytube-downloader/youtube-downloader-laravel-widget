<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Video Downloader'))</title>
    <meta name="description" content="@yield('description', 'Multi-platform video downloader supporting MP4 and MP3 outputs.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-100 antialiased">
    <div class="relative flex min-h-full flex-col">
        <header class="sticky top-0 z-50 border-b border-slate-800 bg-slate-950/90 backdrop-blur">
            <nav class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight text-cyan-300">
                    {{ config('app.name', 'Video Downloader') }}
                </a>
                <div class="hidden items-center gap-6 text-sm font-medium text-slate-300 md:flex">
                    <a href="#features" class="hover:text-white transition-colors">Features</a>
                    <a href="#card-grid" class="hover:text-white transition-colors">Downloaders</a>
                    <a href="#faq" class="hover:text-white transition-colors">FAQ</a>
                    <a href="#status" class="hover:text-white transition-colors">Status</a>
                </div>
            </nav>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t border-slate-800 bg-slate-950">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-12 text-sm text-slate-400 md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ now()->year }} {{ config('app.name', 'Video Downloader') }}. Built with Laravel & Tailwind CSS.</p>
                <div class="flex items-center gap-4">
                    <a href="#features" class="hover:text-white transition-colors">Features</a>
                    <a href="#card-grid" class="hover:text-white transition-colors">Download Cards</a>
                    <a href="#faq" class="hover:text-white transition-colors">FAQ</a>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
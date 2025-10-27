<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Video Downloader'))</title>
    <meta name="description" content="@yield('description', 'Multi-platform video downloader supporting MP4 and MP3 outputs.')">

    <script>
        (() => {
            const storageKey = 'theme-preference';
            const classList = document.documentElement.classList;
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;

            const getStoredTheme = () => {
                try {
                    return localStorage.getItem(storageKey);
                } catch (error) {
                    console.error('Unable to access localStorage:', error);
                    return null;
                }
            };

            const storedTheme = getStoredTheme();

            if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                classList.add('dark');
            } else {
                classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
    <div class="relative flex min-h-full flex-col">
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur transition-colors dark:border-slate-800 dark:bg-slate-950/90">
            <nav class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight text-cyan-600 transition-colors dark:text-cyan-300">
                    {{ config('app.name', 'Video Downloader') }}
                </a>
                <div class="flex items-center gap-4">
                    <div class="hidden items-center gap-6 text-sm font-medium text-slate-600 transition-colors md:flex dark:text-slate-300">
                        <a href="#features" class="hover:text-slate-900 transition-colors dark:hover:text-white">Features</a>
                        <a href="#card-grid" class="hover:text-slate-900 transition-colors dark:hover:text-white">Downloaders</a>
                        <a href="#faq" class="hover:text-slate-900 transition-colors dark:hover:text-white">FAQ</a>
                        <a href="#status" class="hover:text-slate-900 transition-colors dark:hover:text-white">Status</a>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-cyan-400 hover:text-cyan-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-cyan-400 dark:hover:text-cyan-300 dark:focus-visible:ring-cyan-300"
                        aria-label="Toggle theme"
                        data-theme-toggle
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            aria-hidden="true"
                            data-theme-icon="light"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75V4.5m0 15v-2.25m5.25-5.25H19.5m-15 0h2.25m9.193-3.943 1.591-1.591m-11.186 11.186 1.591-1.591m0-7.004-1.591-1.591m11.186 11.186-1.591-1.591M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" />
                        </svg>
                        <svg
                            class="hidden h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            aria-hidden="true"
                            data-theme-icon="dark"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.75A9 9 0 0 1 11.25 3c0-.621.066-1.226.19-1.812a9 9 0 1 0 10.372 10.372c-.586.124-1.191.19-1.812.19Z" />
                        </svg>
                        <span class="sr-only">Toggle theme</span>
                    </button>
                </div>
            </nav>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-slate-100 transition-colors dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-12 text-sm text-slate-500 transition-colors md:flex-row md:items-center md:justify-between dark:text-slate-400">
                <p>&copy; {{ now()->year }} {{ config('app.name', 'Video Downloader') }}. Built with Laravel & Tailwind CSS.</p>
                <div class="flex items-center gap-4">
                    <a href="#features" class="hover:text-slate-900 transition-colors dark:hover:text-white">Features</a>
                    <a href="#card-grid" class="hover:text-slate-900 transition-colors dark:hover:text-white">Download Cards</a>
                    <a href="#faq" class="hover:text-slate-900 transition-colors dark:hover:text-white">FAQ</a>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
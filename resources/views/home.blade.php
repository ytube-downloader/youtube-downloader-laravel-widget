@extends('layouts.app')

@section('title', 'Universal Video Downloader')
@section('description', 'Paste any supported video link to download as MP4 or extract MP3 audio with instant status tracking.')

@section('content')
<section class="bg-gradient-to-b from-slate-100 via-white to-slate-200 pb-20 transition-colors dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 pt-16">
        <div class="flex flex-col gap-6 text-center">
            <span class="inline-flex items-center justify-center gap-2 self-center rounded-full border border-cyan-600/20 bg-cyan-100 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700 transition-colors dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-200">
                Multi-platform support
            </span>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 transition-colors md:text-6xl dark:text-white">
                Download videos or extract audio in seconds
            </h1>
            <p class="text-base text-slate-600 transition-colors md:text-lg dark:text-slate-300">
                Paste a link from YouTube, TikTok, Instagram, Facebook, Twitter/X, Vimeo, or Dailymotion.
                Choose MP4 video quality or MP3 bitrate, convert, and track progress in real-time.
            </p>
        </div>

        <div class="mx-auto w-full max-w-4xl space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 transition-colors dark:border-slate-800 dark:bg-slate-900/70">
                <form id="lookup-form" class="space-y-4">
                    <label for="video-url" class="block text-sm font-medium text-slate-700 transition-colors dark:text-slate-200">Paste video URL</label>
                    <div class="flex flex-col gap-4 md:flex-row">
                        <div class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 transition-all focus-within:border-cyan-400 focus-within:bg-slate-50 focus-within:ring-2 focus-within:ring-cyan-500/40 dark:border-slate-800 dark:bg-slate-950/60 dark:focus-within:bg-slate-900">
                            <input
                                type="url"
                                id="video-url"
                                name="url"
                                required
                                placeholder="https://www.youtube.com/watch?v=video-id"
                                class="w-full bg-transparent text-base text-slate-900 placeholder:text-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder:text-slate-500"
                            >
                        </div>
                        <button
                            type="submit"
                            id="lookup-submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:text-slate-950 dark:disabled:bg-slate-700 dark:disabled:text-slate-400"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0a7.5 7.5 0 1 0-10.607-10.607 7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            Fetch details
                        </button>
                    </div>
                </form>
                <p id="feedback" class="hidden text-sm text-rose-400"></p>
            </div>

            <div id="video-info-card" class="hidden rounded-2xl border border-slate-200 bg-white p-6 transition-colors dark:border-slate-800 dark:bg-slate-900/60 backdrop-blur">
                <div class="flex flex-col gap-6 md:flex-row">
                    <div class="aspect-video w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 transition-colors md:w-64 dark:border-slate-800 dark:bg-slate-950">
                        <img id="video-thumbnail" src="" alt="Video thumbnail" class="h-full w-full object-cover">
                    </div>
                    <div class="flex-1 space-y-4 text-sm text-slate-600 transition-colors dark:text-slate-200">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-cyan-700 transition-colors dark:text-cyan-300">Preview</p>
                            <h2 id="video-title" class="text-lg font-semibold text-slate-900 transition-colors dark:text-white">—</h2>
                            <p id="video-metadata" class="text-xs text-slate-500 transition-colors dark:text-slate-400">—</p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition-colors dark:border-slate-800 dark:bg-slate-950/70">
                                <p class="text-xs font-medium uppercase text-slate-500 transition-colors dark:text-slate-400">Available video qualities</p>
                                <p id="video-qualities" class="mt-1 text-sm text-slate-700 transition-colors dark:text-slate-200">4K, 1440p, 1080p, 720p, 480p, 360p</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition-colors dark:border-slate-800 dark:bg-slate-950/70">
                                <p class="text-xs font-medium uppercase text-slate-500 transition-colors dark:text-slate-400">Available audio bitrates</p>
                                <p id="audio-qualities" class="mt-1 text-sm text-slate-700 transition-colors dark:text-slate-200">320, 256, 192, 128, 96 kbps</p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-cyan-200/80 bg-cyan-50 p-4 text-xs text-cyan-700 transition-colors dark:border-cyan-500/20 dark:bg-cyan-500/5 dark:text-cyan-200">
                            Ready to download. Choose MP4 video or MP3 audio below.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section id="card-grid" class="space-y-4">
            <div class="flex flex-col gap-2">
                <h3 class="text-2xl font-semibold text-slate-900 transition-colors dark:text-white">Choose your format</h3>
                <p class="text-sm text-slate-500 transition-colors dark:text-slate-400">Card unlocks after fetching video details.</p>
            </div>
            <article id="card-download" class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900/60">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 transition-colors dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500/20 via-cyan-500/10 to-emerald-500/20 text-cyan-500 dark:from-cyan-500/20 dark:via-cyan-500/10 dark:to-emerald-500/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5M7.5 9h9m-9 4.5h6m-6 4.5h12M4.5 9h.008v.008H4.5zm0 4.5h.008v.008H4.5zm0 4.5h.008v.008H4.5z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-cyan-700 transition-colors dark:text-cyan-300">Download console</span>
                            <h4 class="mt-1 text-xl font-semibold text-slate-900 transition-colors dark:text-white">Adaptive conversion widget</h4>
                        </div>
                    </div>
                    <div id="summary-state-pill" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 transition-colors dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200">
                        <span id="summary-state-dot" class="inline-flex h-2 w-2 rounded-full bg-slate-400 transition-colors"></span>
                        <span id="summary-state">Awaiting video lookup</span>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-600 transition-colors dark:text-slate-400">
                    Align downloads, conversions, and exports from a single adaptive panel. Configure once, then run repeated jobs without losing your context.
                </p>
                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900/50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 transition-colors dark:text-slate-400">Workflow mode</p>
                                    <p class="text-xs text-slate-500 transition-colors dark:text-slate-500">Switch modes to reveal the relevant controls without losing previous selections.</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm ring-1 ring-white/70 transition-colors dark:bg-slate-900 dark:text-slate-300 dark:ring-white/5">Step 1</span>
                            </div>
                            <label class="mt-4 block text-xs font-semibold uppercase text-slate-500 transition-colors dark:text-slate-400">
                                Mode
                                <select id="download-type" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100" disabled>
                                    <option value="video" selected>Video download</option>
                                    <option value="audio">Audio extract</option>
                                </select>
                            </label>
                        </div>
                        <div id="download-video-options" class="grid gap-4 sm:grid-cols-2">
                            <label class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 text-xs font-semibold uppercase text-slate-500 transition-all hover:border-cyan-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-cyan-500/40">
                                <span>Quality</span>
                                <select id="download-video-quality" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition-colors focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100" disabled>
                                    <option value="4k">4K (2160p)</option>
                                    <option value="1440p">1440p</option>
                                    <option value="1080p" selected>1080p</option>
                                    <option value="720p">720p</option>
                                    <option value="480p">480p</option>
                                    <option value="360p">360p</option>
                                </select>
                            </label>
                            <label class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 text-xs font-semibold uppercase text-slate-500 transition-all hover:border-cyan-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-cyan-500/40">
                                <span>Container / Codec</span>
                                <select id="download-video-format" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition-colors focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100" disabled>
                                    <option value="mp4" selected>MP4 (H.264)</option>
                                    <option value="webm">WEBM (VP9)</option>
                                </select>
                            </label>
                        </div>
                        <div id="download-audio-options" class="hidden grid gap-4 sm:grid-cols-2">
                            <label class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 text-xs font-semibold uppercase text-slate-500 transition-all hover:border-emerald-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-emerald-500/40">
                                <span>Bitrate</span>
                                <select id="download-audio-bitrate" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition-colors focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100" disabled>
                                    <option value="320">320 kbps (Studio)</option>
                                    <option value="256">256 kbps (Premium)</option>
                                    <option value="192" selected>192 kbps (High)</option>
                                    <option value="128">128 kbps (Standard)</option>
                                    <option value="96">96 kbps (Lite)</option>
                                </select>
                            </label>
                            <label class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 text-xs font-semibold uppercase text-slate-500 transition-all hover:border-emerald-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-emerald-500/40">
                                <span>Normalise audio</span>
                                <select id="download-audio-normalise" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition-colors focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100" disabled>
                                    <option value="false" selected>No</option>
                                    <option value="true">Yes</option>
                                </select>
                            </label>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900/50">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 transition-colors dark:text-slate-400">Launch job</p>
                                    <p class="text-xs text-slate-500 transition-colors dark:text-slate-500">We will enqueue the download, monitor progress, and surface the link automatically.</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm ring-1 ring-white/70 transition-colors dark:bg-slate-900 dark:text-slate-300 dark:ring-white/5">Step 2</span>
                            </div>
                            <button
                                id="start-download"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-cyan-500 px-4 py-3 text-sm font-semibold text-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:text-slate-950 dark:disabled:bg-slate-700 dark:disabled:text-slate-400"
                                data-loading="false"
                                aria-live="polite"
                                aria-busy="false"
                                disabled
                            >
                                <svg
                                    id="start-download-icon"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 7.5 7.5m0 0 7.5-7.5m-7.5 7.5V3" />
                                </svg>
                                <svg
                                    id="start-download-spinner"
                                    class="hidden h-4 w-4 download-spinner text-white transition-colors dark:text-slate-950"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z" />
                                </svg>
                                <span id="start-download-label" class="whitespace-nowrap" data-default-label="Download video">Download video</span>
                            </button>
                        </div>
                    </div>
                    <aside class="relative overflow-hidden rounded-2xl border border-cyan-500/40 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-6 text-white shadow-lg dark:border-cyan-500/30">
                        <div class="pointer-events-none absolute -right-16 top-14 h-40 w-40 rounded-full bg-cyan-500/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-12 left-10 h-40 w-40 rounded-full bg-emerald-500/20 blur-3xl"></div>
                        <div class="relative space-y-6">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-cyan-200">Live summary</p>
                                <span class="rounded-full border border-cyan-500/40 bg-cyan-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.35em] text-cyan-100">Auto refresh</span>
                            </div>
                            <dl class="space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Mode</dt>
                                    <dd id="summary-mode" class="font-semibold text-white">Video download</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Quality</dt>
                                    <dd id="summary-quality" class="font-semibold text-white">1080p</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Format</dt>
                                    <dd id="summary-format" class="font-semibold text-white">MP4 (H.264)</dd>
                                </div>
                                <div id="summary-audio-wrapper" class="flex items-center justify-between gap-3 hidden">
                                    <dt class="text-slate-400">Audio bitrate</dt>
                                    <dd id="summary-audio" class="font-semibold text-white">192 kbps (High)</dd>
                                </div>
                                <div id="summary-normalise-wrapper" class="flex items-center justify-between gap-3 hidden">
                                    <dt class="text-slate-400">Normalisation</dt>
                                    <dd id="summary-normalise" class="font-semibold text-white">No</dd>
                                </div>
                            </dl>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-xs text-slate-200">
                                We translate your selections into precise payloads for the Video Downloader API, orchestrate queue workers, and surface signed URLs as soon as they are ready.
                            </div>
                        </div>
                    </aside>
                </div>
            </article>
        </section>

        <section id="status" class="space-y-4">
            <div class="flex flex-col gap-2">
                <h3 class="text-2xl font-semibold text-slate-900 transition-colors dark:text-white">Download status</h3>
                <p class="text-sm text-slate-500 transition-colors dark:text-slate-400">We’ll keep polling while your download is processing.</p>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition-colors dark:border-slate-800 dark:bg-slate-900/60">
                <table class="min-w-full divide-y divide-slate-200 text-sm transition-colors dark:divide-slate-800">
                    <thead class="bg-slate-100 text-left text-xs uppercase tracking-wider text-slate-500 transition-colors dark:bg-slate-950/60 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Format</th>
                            <th class="px-4 py-3">Quality</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Updated</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="downloads-table" class="divide-y divide-slate-200 text-slate-600 transition-colors dark:divide-slate-800 dark:text-slate-200">
                        <tr id="downloads-empty">
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500 transition-colors dark:text-slate-500">
                                Downloads will appear here once you start a job.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="features" class="space-y-10">
            <div class="flex flex-col gap-2">
                <h3 class="text-2xl font-semibold text-slate-900 transition-colors dark:text-white">Why creators use this downloader</h3>
                <p class="text-sm text-slate-500 transition-colors dark:text-slate-400">High throughput conversions optimised for modern workflows.</p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 transition-colors dark:border-slate-800 dark:bg-slate-900/60">
                    <h4 class="text-lg font-semibold text-slate-900 transition-colors dark:text-white">High fidelity output</h4>
                    <p class="mt-2 text-sm text-slate-600 transition-colors dark:text-slate-400">Preserve original resolution and framerate, or select audio bitrates optimised for streaming and voice.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 transition-colors dark:border-slate-800 dark:bg-slate-900/60">
                    <h4 class="text-lg font-semibold text-slate-900 transition-colors dark:text-white">Multiple platforms</h4>
                    <p class="mt-2 text-sm text-slate-600 transition-colors dark:text-slate-400">Support for YouTube, TikTok, Instagram, Facebook, Twitter/X, Vimeo, Dailymotion and more via the integrated API.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 transition-colors dark:border-slate-800 dark:bg-slate-900/60">
                    <h4 class="text-lg font-semibold text-slate-900 transition-colors dark:text-white">Queue aware</h4>
                    <p class="mt-2 text-sm text-slate-600 transition-colors dark:text-slate-400">Background queue workers process downloads while you continue browsing. Status updates arrive automatically.</p>
                </div>
            </div>
        </section>

        <section id="faq" class="space-y-6">
            <div>
                <h3 class="text-2xl font-semibold text-slate-900 transition-colors dark:text-white">Frequently asked questions</h3>
                <p class="text-sm text-slate-500 transition-colors dark:text-slate-400">Answers to common questions from creators.</p>
            </div>
            <div class="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white transition-colors dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900/60">
                <details class="group transition-colors open:bg-slate-100 dark:open:bg-slate-900/80">
                    <summary class="flex cursor-pointer items-center justify-between px-6 py-4 text-sm font-semibold text-slate-700 transition-colors dark:text-slate-200">
                        Which platforms are supported?
                        <span class="transition group-open:rotate-180">&#9660;</span>
                    </summary>
                    <div class="px-6 pb-4 text-sm text-slate-600 transition-colors dark:text-slate-400">
                        Any video link recognised by the upstream API including YouTube, TikTok, Instagram, Facebook, Twitter/X, Vimeo, Dailymotion, and others.
                    </div>
                </details>
                <details class="group transition-colors open:bg-slate-100 dark:open:bg-slate-900/80">
                    <summary class="flex cursor-pointer items-center justify-between px-6 py-4 text-sm font-semibold text-slate-700 transition-colors dark:text-slate-200">
                        Can I monitor multiple downloads?
                        <span class="transition group-open:rotate-180">&#9660;</span>
                    </summary>
                    <div class="px-6 pb-4 text-sm text-slate-600 transition-colors dark:text-slate-400">
                        Yes. Each job is queued separately. The dashboard polls their status and surfaces download links as soon as the files are ready.
                    </div>
                </details>
                <details class="group transition-colors open:bg-slate-100 dark:open:bg-slate-900/80">
                    <summary class="flex cursor-pointer items-center justify-between px-6 py-4 text-sm font-semibold text-slate-700 transition-colors dark:text-slate-200">
                        Do I need to keep the page open?
                        <span class="transition group-open:rotate-180">&#9660;</span>
                    </summary>
                    <div class="px-6 pb-4 text-sm text-slate-600 transition-colors dark:text-slate-400">
                        Polling happens in the browser. Keep the tab open until a job reaches the completed state so the signed download link is captured.
                    </div>
                </details>
            </div>
        </section>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const state = {
        currentUrl: '',
        videoInfo: null,
        downloads: [],
        pollers: {},
        progressPollers: {},
    };

    const lookupForm = document.getElementById('lookup-form');
    const urlInput = document.getElementById('video-url');
    const feedback = document.getElementById('feedback');
    const infoCard = document.getElementById('video-info-card');
    const videoTitle = document.getElementById('video-title');
    const videoMeta = document.getElementById('video-metadata');
    const videoThumb = document.getElementById('video-thumbnail');
    const videoQualities = document.getElementById('video-qualities');
    const audioQualities = document.getElementById('audio-qualities');
    const downloadCard = document.getElementById('card-download');
    const downloadTypeSelect = document.getElementById('download-type');
    const videoOptions = document.getElementById('download-video-options');
    const audioOptions = document.getElementById('download-audio-options');
    const videoQualitySelect = document.getElementById('download-video-quality');
    const videoFormatSelect = document.getElementById('download-video-format');
    const audioBitrateSelect = document.getElementById('download-audio-bitrate');
    const audioNormaliseSelect = document.getElementById('download-audio-normalise');
    const startDownloadButton = document.getElementById('start-download');
    const startDownloadLabel = document.getElementById('start-download-label');
    const startDownloadIcon = document.getElementById('start-download-icon');
    const startDownloadSpinner = document.getElementById('start-download-spinner');
    const downloadsTable = document.getElementById('downloads-table');
    let downloadsEmpty = document.getElementById('downloads-empty');
    const summaryState = document.getElementById('summary-state');
    const summaryStatePill = document.getElementById('summary-state-pill');
    const summaryStateDot = document.getElementById('summary-state-dot');
    const summaryMode = document.getElementById('summary-mode');
    const summaryQuality = document.getElementById('summary-quality');
    const summaryFormat = document.getElementById('summary-format');
    const summaryAudioWrapper = document.getElementById('summary-audio-wrapper');
    const summaryAudio = document.getElementById('summary-audio');
    const summaryNormaliseWrapper = document.getElementById('summary-normalise-wrapper');
    const summaryNormalise = document.getElementById('summary-normalise');
    const downloadsEmptyTemplate = downloadsEmpty?.outerHTML ?? `
        <tr id="downloads-empty">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                Downloads will appear here once you start a job.
            </td>
        </tr>
    `;
    const STATUS_POLL_INTERVAL = 5000;
    const PROGRESS_POLL_INTERVAL = 3000;

    const defaultQualities = ['4k', '1440p', '1080p', '720p', '480p', '360p'];
    const defaultAudio = [320, 256, 192, 128, 96];

    setSummaryState({ label: 'Awaiting video lookup', tone: 'idle' });
    updateSummarySelections();

    lookupForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const url = urlInput.value.trim();
        if (!url) {
            showFeedback('Please paste a valid URL.', true);
            setSummaryState({ label: 'Paste a valid URL to continue.', tone: 'error' });
            return;
        }
        toggleLookupButton(true);
        showFeedback('Fetching metadata…', false);
        setSummaryState({ label: 'Fetching metadata…', tone: 'loading' });
        try {
            const response = await fetch(`/api/v1/video-info?${new URLSearchParams({ url })}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.error ?? 'Unable to fetch details.');
            }
            state.currentUrl = url;
            state.videoInfo = payload.data ?? {};
            populateVideoInfo(state.videoInfo);
            enableDownloadCard(true);
            setSummaryState({ label: 'Widget unlocked. Configure your job.', tone: 'ready' });
            showFeedback('Video details loaded. Configure your download below.', false);
        } catch (error) {
            enableDownloadCard(false);
            showFeedback(error.message ?? 'Unable to fetch details.', true);
            setSummaryState({ label: 'Lookup failed. Check the URL.', tone: 'error' });
            console.error(error);
        } finally {
            toggleLookupButton(false);
        }
    });

    downloadTypeSelect.addEventListener('change', () => {
        updateDownloadModeUI();
    });

    [videoQualitySelect, videoFormatSelect, audioBitrateSelect, audioNormaliseSelect].forEach((control) => {
        control?.addEventListener('change', () => {
            updateSummarySelections();
        });
    });

    startDownloadButton.addEventListener('click', () => {
        const mode = downloadTypeSelect.value;

        if (mode === 'audio') {
            startDownload({
                quality: 'audio',
                format: 'mp3',
                options: {
                    audio_quality: parseInt(audioBitrateSelect.value, 10),
                    normalize_audio: audioNormaliseSelect.value === 'true',
                },
            });
            return;
        }

        startDownload({
            quality: videoQualitySelect.value,
            format: videoFormatSelect.value,
            options: {},
        });
    });

    updateDownloadModeUI();
    enableDownloadCard(false);

    function toggleLookupButton(isLoading) {
        const button = document.getElementById('lookup-submit');
        button.disabled = isLoading;
        button.textContent = isLoading ? 'Fetching…' : 'Fetch details';
    }

    function setSummaryState({ label, tone = 'idle' }) {
        if (!summaryState || !summaryStatePill || !summaryStateDot) {
            return;
        }

        const toneStyles = {
            idle: {
                pill: 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200',
                dot: 'bg-slate-400',
            },
            ready: {
                pill: 'border-emerald-400/40 bg-emerald-500/10 text-emerald-600 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-200',
                dot: 'bg-emerald-400',
            },
            loading: {
                pill: 'border-amber-400/40 bg-amber-500/10 text-amber-600 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200',
                dot: 'bg-amber-400',
            },
            error: {
                pill: 'border-rose-400/40 bg-rose-500/10 text-rose-500 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200',
                dot: 'bg-rose-400',
            },
            queued: {
                pill: 'border-cyan-400/40 bg-cyan-500/10 text-cyan-500 dark:border-cyan-400/30 dark:bg-cyan-500/10 dark:text-cyan-200',
                dot: 'bg-cyan-400',
            },
            processing: {
                pill: 'border-indigo-400/40 bg-indigo-500/10 text-indigo-500 dark:border-indigo-400/30 dark:bg-indigo-500/10 dark:text-indigo-200',
                dot: 'bg-indigo-400',
            },
            success: {
                pill: 'border-emerald-400/50 bg-emerald-500/15 text-emerald-500 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200',
                dot: 'bg-emerald-300',
            },
        };

        const nextTone = toneStyles[tone] ? tone : 'idle';
        const previousTone = summaryStatePill.dataset.tone;

        const removeToneClasses = (element, classes) => {
            if (!element || !classes) {
                return;
            }
            classes
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .forEach((cls) => element.classList.remove(cls));
        };

        const addToneClasses = (element, classes) => {
            if (!element || !classes) {
                return;
            }
            element.classList.add(
                ...classes
                    .trim()
                    .split(/\s+/)
                    .filter(Boolean)
            );
        };

        if (previousTone && toneStyles[previousTone]) {
            removeToneClasses(summaryStatePill, toneStyles[previousTone].pill);
            removeToneClasses(summaryStateDot, toneStyles[previousTone].dot);
        }

        addToneClasses(summaryStatePill, toneStyles[nextTone].pill);
        addToneClasses(summaryStateDot, toneStyles[nextTone].dot);
        summaryStatePill.dataset.tone = nextTone;

        summaryState.textContent = label;
    }

    function showFeedback(message, isError) {
        if (!message) {
            feedback.classList.add('hidden');
            return;
        }
        feedback.textContent = message;
        feedback.classList.remove('hidden');
        feedback.classList.toggle('text-rose-400', isError);
        feedback.classList.toggle('text-emerald-400', !isError);
    }

    function getSelectedOptionLabel(select) {
        if (!select) {
            return '—';
        }

        const option = select.selectedOptions?.[0] ?? null;
        if (option?.textContent) {
            return option.textContent.trim();
        }

        const fallback = select.value ?? '';
        return fallback ? fallback.toString() : '—';
    }

    function updateSummarySelections() {
        if (!summaryMode) {
            return;
        }

        const mode = downloadTypeSelect?.value ?? 'video';
        const isAudio = mode === 'audio';

        summaryMode.textContent = isAudio ? 'Audio extract' : 'Video download';

        if (summaryQuality) {
            summaryQuality.textContent = isAudio
                ? 'Audio conversion'
                : getSelectedOptionLabel(videoQualitySelect);
        }

        if (summaryFormat) {
            summaryFormat.textContent = isAudio
                ? 'MP3 (Audio extract)'
                : getSelectedOptionLabel(videoFormatSelect);
        }

        if (summaryAudioWrapper) {
            summaryAudioWrapper.classList.toggle('hidden', !isAudio);
        }

        if (summaryNormaliseWrapper) {
            summaryNormaliseWrapper.classList.toggle('hidden', !isAudio);
        }

        if (isAudio) {
            if (summaryAudio) {
                summaryAudio.textContent = getSelectedOptionLabel(audioBitrateSelect);
            }

            if (summaryNormalise) {
                summaryNormalise.textContent = audioNormaliseSelect?.value === 'true' ? 'Yes' : 'No';
            }
        }
    }

    function updateSummaryFromRecord(record) {
        if (!record) {
            return;
        }

        const status = record.status ?? 'queued';

        switch (status) {
            case 'completed':
                setSummaryState({ label: 'Latest job completed successfully.', tone: 'success' });
                break;
            case 'failed':
                setSummaryState({ label: 'Latest job failed. Check status panel.', tone: 'error' });
                break;
            case 'processing':
                setSummaryState({ label: 'Processing download…', tone: 'processing' });
                break;
            default:
                setSummaryState({ label: 'Download queued. Monitoring status…', tone: 'queued' });
        }
    }

    function populateVideoInfo(info) {
        infoCard.classList.remove('hidden');
        const meta = info.info ?? {};
        videoTitle.textContent = meta.title ?? 'Untitled video';
        const parts = [];
        if (meta.duration) parts.push(meta.duration);
        if (meta.author) parts.push(meta.author);
        if (meta.upload_date) parts.push(meta.upload_date);
        videoMeta.textContent = parts.length ? parts.join(' • ') : 'Metadata unavailable';
        videoThumb.src = meta.image ?? meta.thumbnail ?? 'https://placehold.co/640x360?text=Preview';
        videoQualities.textContent = (meta.available_qualities ?? defaultQualities)
            .map((item) => item.toString().toUpperCase())
            .join(', ');
        audioQualities.textContent = (meta.available_audio_formats ?? defaultAudio)
            .map((item) => `${item} kbps`)
            .join(', ');
    }

    async function startDownload({ quality, format, options }) {
        if (!state.currentUrl) {
            showFeedback('Fetch video details first.', true);
            setSummaryState({ label: 'Lookup required before download.', tone: 'error' });
            return;
        }

        const payload = {
            url: state.currentUrl,
            quality: quality === 'audio' ? '1080p' : quality,
            format,
            options,
        };

        setDownloadButtonLoading(true);
        setSummaryState({ label: 'Submitting download request…', tone: 'loading' });
        showFeedback('Sending download request…', false);

        try {
            const response = await fetch('/api/v1/download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok || !(data.success ?? false)) {
                throw new Error(data.error ?? data.message ?? 'Unable to start download.');
            }

            const resource = data.data ?? data;

            if (!resource) {
                throw new Error('Download response missing payload.');
            }

            const record = mapResourceToRecord(resource);

            if (!record) {
                throw new Error('Unable to interpret download response.');
            }

            upsertDownloadRecord(record);
            renderDownloads();
            updateSummaryFromRecord(record);

            showFeedback('Download queued. Tracking status…', false);
            pollDownload(record.download_id);
            ensureProgressPoller(record);
        } catch (error) {
            showFeedback(error.message ?? 'Unable to start download.', true);
            setSummaryState({ label: 'Download request failed. Try again.', tone: 'error' });
            console.error(error);
        } finally {
            setDownloadButtonLoading(false);
        }
    }

    function mapResourceToRecord(resource) {
        if (!resource || typeof resource !== 'object') {
            return null;
        }

        const metadata = { ...(resource.metadata ?? {}) };
        const options = metadata.options ?? {};
        const format = String(resource.format ?? options.format ?? 'mp4').toLowerCase();

        const resolvedStoragePath =
            resource.storage_path ??
            resource.download_url ??
            metadata.download_url ??
            metadata.api_response?.download_url ??
            metadata.progress?.download_url ??
            null;

        if (resolvedStoragePath) {
            metadata.download_url = metadata.download_url ?? resolvedStoragePath;
        }

        const titleCandidates = [
            resource.video_title,
            metadata.info?.title,
            metadata.api_response?.info?.title,
            state.videoInfo?.info?.title,
            resource.video_url,
        ];
        const title =
            titleCandidates.find((candidate) => typeof candidate === 'string' && candidate.trim().length > 0) ??
            'Untitled download';

        const baseQuality = resource.quality ?? metadata.requested_quality ?? options.quality ?? null;
        const audioQuality = options.audio_quality ?? metadata.requested_audio_quality ?? null;

        const quality = isAudioFormat(format)
            ? (audioQuality ? `${audioQuality} kbps` : 'Audio')
            : (baseQuality ?? '—');

        const queuedAt = normalizeTimestamp(resource.queued_at ?? metadata.requested_at);
        const updatedAt = normalizeTimestamp(
            resource.completed_at ?? resource.started_at ?? resource.updated_at ?? queuedAt
        );

        const progressSourceValue = metadata.progress?.raw_value ?? metadata.progress?.percent ?? null;
        const rawProgressValue = progressSourceValue === null ? null : Number(progressSourceValue);
        const hasProgressValue = rawProgressValue !== null && Number.isFinite(rawProgressValue);
        const progressPercent = hasProgressValue ? computeProgressPercent(rawProgressValue) : null;
        const hasTerminalProgress = hasProgressValue && rawProgressValue >= 1000;

        if (metadata.progress) {
            metadata.progress = {
                ...metadata.progress,
                raw_value: hasProgressValue ? rawProgressValue : metadata.progress.raw_value ?? 0,
                percent: progressPercent,
            };
        }

        let status = normaliseStatus(resource.status);
        const hasResolvedDownload = Boolean(resolvedStoragePath);

        if (hasTerminalProgress && status !== 'failed') {
            status = 'completed';
        }

        if (hasResolvedDownload && status !== 'failed') {
            status = 'completed';
        }

        return {
            download_id: resource.download_id,
            title,
            format,
            quality,
            status,
            storage_path: resolvedStoragePath ?? null,
            queued_at: queuedAt,
            updated_at: updatedAt,
            error_message: resource.error_message ?? null,
            metadata,
        };
    }

    function upsertDownloadRecord(record) {
        if (!record || !record.download_id) {
            return;
        }

        const index = state.downloads.findIndex((item) => item.download_id === record.download_id);

        if (index === -1) {
            state.downloads = [record, ...state.downloads];
        } else {
            state.downloads[index] = {
                ...state.downloads[index],
                ...record,
            };
        }
    }

    function normalizeTimestamp(value) {
        if (!value) {
            return new Date().toISOString();
        }

        const date = value instanceof Date ? value : new Date(value);

        if (Number.isNaN(date.getTime())) {
            return new Date().toISOString();
        }

        return date.toISOString();
    }

    function normaliseStatus(status) {
        const normalised = (status ?? 'queued').toString().toLowerCase();

        if (normalised === 'pending') {
            return 'queued';
        }

        const allowed = new Set(['queued', 'processing', 'completed', 'failed']);

        return allowed.has(normalised) ? normalised : 'queued';
    }

    function computeProgressPercent(value) {
        if (!Number.isFinite(value)) {
            return null;
        }

        if (value <= 0) {
            return 0;
        }

        if (value >= 1000) {
            return 100;
        }

        if (value <= 100) {
            return Math.min(100, Math.max(0, Math.round(value)));
        }

        return Math.min(100, Math.round(value / 10));
    }

    function isAudioFormat(format) {
        return ['mp3', 'm4a', 'aac', 'ogg', 'wav', 'flac'].includes((format ?? '').toString().toLowerCase());
    }

    function recordHasDownloadUrl(record) {
        if (!record) {
            return false;
        }

        const metadataUrl =
            record.metadata?.download_url ??
            record.metadata?.progress?.download_url ??
            record.metadata?.progress?.payload?.download_url ??
            null;

        return Boolean(record.storage_path ?? metadataUrl);
    }

    function getSortedDownloads() {
        return [...state.downloads].sort((a, b) => {
            const left = new Date(a.queued_at ?? a.updated_at ?? 0).getTime();
            const right = new Date(b.queued_at ?? b.updated_at ?? 0).getTime();

            return right - left;
        });
    }

    function updateDownloadModeUI() {
        const mode = downloadTypeSelect?.value ?? 'video';
        const isAudio = mode === 'audio';
        const isLoading = startDownloadButton?.dataset?.loading === 'true';

        if (videoOptions) {
            videoOptions.classList.toggle('hidden', isAudio);
        }

        if (audioOptions) {
            audioOptions.classList.toggle('hidden', !isAudio);
        }
if (startDownloadLabel && !isLoading) {
    const nextLabel = isAudio ? 'Convert to audio' : 'Download video';
    startDownloadLabel.textContent = nextLabel;
    startDownloadLabel.dataset.defaultLabel = nextLabel;
}


        if (!startDownloadButton) {
            return;
        }

        startDownloadButton.classList.remove('bg-cyan-500', 'hover:bg-cyan-400', 'focus-visible:ring-cyan-300');
        startDownloadButton.classList.remove('bg-emerald-500', 'hover:bg-emerald-400', 'focus-visible:ring-emerald-300');

        if (isAudio) {
            startDownloadButton.classList.add('bg-emerald-500', 'hover:bg-emerald-400', 'focus-visible:ring-emerald-300');
        } else {
            startDownloadButton.classList.add('bg-cyan-500', 'hover:bg-cyan-400', 'focus-visible:ring-cyan-300');
        }

        updateSummarySelections();
    }

    function enableDownloadCard(enabled) {
        if (downloadCard) {
            downloadCard.classList.toggle('opacity-60', !enabled);
        }

        if (enabled) {
            setSummaryState({ label: 'Widget unlocked. Configure your job.', tone: 'ready' });
        }

        const isLoading = startDownloadButton?.dataset?.loading === 'true';
        const controlsShouldEnable = enabled && !isLoading;

        [
            downloadTypeSelect,
            videoQualitySelect,
            videoFormatSelect,
            audioBitrateSelect,
            audioNormaliseSelect,
            startDownloadButton,
        ].forEach((element) => {
            if (!element) {
                return;
            }

            element.disabled = !controlsShouldEnable;
        });

        if (isLoading) {
            setDownloadButtonLoading(true);
            return;
        }

        updateDownloadModeUI();
    }

    function setDownloadButtonLoading(isLoading) {
        const cardEnabled = downloadCard ? !downloadCard.classList.contains('opacity-60') : true;

        if (startDownloadButton) {
            startDownloadButton.dataset.loading = isLoading ? 'true' : 'false';
            startDownloadButton.classList.toggle('animate-pulse', isLoading);
            startDownloadButton.classList.toggle('cursor-wait', isLoading);
            startDownloadButton.disabled = isLoading ? true : !cardEnabled;
            startDownloadButton.setAttribute('aria-busy', String(isLoading));
        }

        if (downloadTypeSelect) {
            downloadTypeSelect.disabled = isLoading ? true : !cardEnabled;
        }

        [videoQualitySelect, videoFormatSelect, audioBitrateSelect, audioNormaliseSelect].forEach((control) => {
            if (!control) {
                return;
            }

            control.disabled = isLoading ? true : !cardEnabled;
        });

        if (startDownloadIcon) {
            startDownloadIcon.classList.toggle('hidden', isLoading);
        }

        if (startDownloadSpinner) {
            startDownloadSpinner.classList.toggle('hidden', !isLoading);
        }

        if (startDownloadLabel) {
            if (isLoading) {
                const defaultLabel = startDownloadLabel.dataset.defaultLabel ?? startDownloadLabel.textContent ?? '';
                startDownloadLabel.dataset.defaultLabel = defaultLabel;
                startDownloadLabel.textContent = 'Preparing download…';
            } else {
                const defaultLabel = startDownloadLabel.dataset.defaultLabel ?? '';
                if (defaultLabel) {
                    startDownloadLabel.textContent = defaultLabel;
                }
            }
        }

        updateDownloadModeUI();
    }

    function pollDownload(downloadId) {
        if (state.pollers[downloadId]) {
            return;
        }

        const fetchStatus = async () => {
            try {
                const response = await fetch(`/api/v1/download-status/${encodeURIComponent(downloadId)}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to fetch status.');
                }

                const payload = await response.json();
                updateDownloadRecord(payload);
            } catch (error) {
                console.error(error);
            }
        };

        const poller = setInterval(fetchStatus, STATUS_POLL_INTERVAL);
        state.pollers[downloadId] = poller;
        fetchStatus();
    }

    function ensureProgressPoller(record) {
        if (!record?.download_id) {
            return;
        }

        if (recordHasDownloadUrl(record)) {
            clearStatusPoller(record.download_id);
            clearProgressPoller(record.download_id);
            return;
        }

        if (['completed', 'failed'].includes(record.status)) {
            clearProgressPoller(record.download_id);
            return;
        }

        const metadata = record.metadata ?? {};
        const progressUrl = metadata.progress_url ?? metadata.progress_poll_url;

        if (!progressUrl || state.progressPollers[record.download_id]) {
            return;
        }

        const checkProgress = async () => {
            try {
                const response = await fetch(progressUrl, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to fetch progress.');
                }

                const payload = await response.json();
                applyProgressPayload(record.download_id, payload);
            } catch (error) {
                console.error(error);
            }
        };

        const poller = setInterval(checkProgress, PROGRESS_POLL_INTERVAL);
        state.progressPollers[record.download_id] = poller;
        checkProgress();
    }

    function clearProgressPoller(downloadId) {
        const poller = state.progressPollers[downloadId];
        if (poller) {
            clearInterval(poller);
            delete state.progressPollers[downloadId];
        }
    }

    function clearStatusPoller(downloadId) {
        const poller = state.pollers[downloadId];
        if (poller) {
            clearInterval(poller);
            delete state.pollers[downloadId];
        }
    }

    async function refreshDownloadStatus(downloadId) {
        if (!downloadId) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/download-status/${encodeURIComponent(downloadId)}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to fetch status.');
            }

            const payload = await response.json();
            updateDownloadRecord(payload);
        } catch (error) {
            console.error(error);
        }
    }

    function applyProgressPayload(downloadId, payload) {
        const index = state.downloads.findIndex((item) => item.download_id === downloadId);
        if (index === -1) {
            return;
        }

        const data = payload?.data ?? payload ?? {};
        const rawProgress = Number(data.progress ?? data.percent ?? data.progress_value ?? NaN);
        const hasProgressValue = Number.isFinite(rawProgress);
        const percent = hasProgressValue ? computeProgressPercent(rawProgress) : null;

        const downloadUrl =
            data.download_url ??
            data.storage_path ??
            data.url ??
            state.downloads[index].metadata?.download_url ??
            state.downloads[index].storage_path;
        const hasDownloadLink = Boolean(downloadUrl);

        const errorMessage = data.error ?? data.error_message ?? state.downloads[index].error_message ?? null;

        const nextMetadata = {
            ...(state.downloads[index].metadata ?? {}),
            progress_url: data.progress_url ?? state.downloads[index].metadata?.progress_url,
            progress_poll_url: data.progress_poll_url ?? state.downloads[index].metadata?.progress_poll_url,
            download_url: downloadUrl ?? state.downloads[index].metadata?.download_url,
            progress: {
                raw_value: hasProgressValue
                    ? rawProgress
                    : state.downloads[index].metadata?.progress?.raw_value ?? null,
                percent: percent ?? state.downloads[index].metadata?.progress?.percent ?? null,
                text: data.text ?? data.message ?? state.downloads[index].metadata?.progress?.text ?? null,
                checked_at: new Date().toISOString(),
                payload: data,
            },
        };

        const derivedStatus = data.status ?? state.downloads[index].status;
        let status = normaliseStatus(derivedStatus);

        if (Number.isFinite(rawProgress) && rawProgress >= 1000 && status !== 'failed') {
            status = 'completed';
        }

        if (hasDownloadLink && status !== 'failed') {
            status = 'completed';
        }

        const nextRecord = {
            ...state.downloads[index],
            status,
            storage_path: downloadUrl ?? state.downloads[index].storage_path,
            metadata: nextMetadata,
            error_message: errorMessage,
            updated_at: new Date().toISOString(),
        };

        state.downloads[index] = nextRecord;
        renderDownloads();
        updateSummaryFromRecord(nextRecord);

        if (['completed', 'failed'].includes(status) || hasDownloadLink) {
            clearProgressPoller(downloadId);
            clearStatusPoller(downloadId);
            refreshDownloadStatus(downloadId);
        } else {
            ensureProgressPoller(nextRecord);
        }
    }

    function updateDownloadRecord(payload) {
        const resource = payload?.data ?? payload;
        const record = mapResourceToRecord(resource);

        if (!record) {
            return;
        }

        upsertDownloadRecord(record);
        renderDownloads();
        updateSummaryFromRecord(record);

        if (['completed', 'failed'].includes(record.status) || recordHasDownloadUrl(record)) {
            clearStatusPoller(record.download_id);
            clearProgressPoller(record.download_id);
            return;
        }

        ensureProgressPoller(record);
    }

    function renderDownloads() {
        state.downloads = getSortedDownloads();
        const downloads = state.downloads;

        if (!downloads.length) {
            downloadsTable.innerHTML = downloadsEmptyTemplate;
            downloadsEmpty = document.getElementById('downloads-empty');
            return;
        }

        downloadsTable.innerHTML = downloads
            .map((item) => {
                const statusBadge = renderStatusBadge(item.status);
                const progressPercent = formatProgressPercent(item);
                const updatedLabel = item.updated_at ? new Date(item.updated_at).toLocaleTimeString() : '—';
                const action =
                    item.storage_path && item.status === 'completed'
                        ? `<a href="${item.storage_path}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-200 hover:bg-cyan-500/20">Download</a>`
                        : '—';

                const statusMarkup = (() => {
                    const errorText = item.error_message
                        ? `<span class="text-xs text-rose-300">${escapeHtml(item.error_message)}</span>`
                        : '';

                    const progressMarkup = progressPercent !== null && item.status !== 'completed'
                        ? `<div class="flex flex-col gap-1">
                                <div class="h-1.5 w-32 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full bg-cyan-500 transition-all duration-300" style="width: ${progressPercent}%;"></div>
                                </div>
                                <span class="text-xs text-slate-400">${progressPercent}%</span>
                           </div>`
                        : '';

                    return `
                        <div class="flex flex-col gap-1">
                            ${statusBadge}
                            ${progressMarkup}
                            ${errorText}
                        </div>
                    `;
                })();

                return `
                    <tr>
                        <td class="px-4 py-4">${escapeHtml(item.title)}</td>
                        <td class="px-4 py-4 uppercase text-slate-300">${escapeHtml(item.format)}</td>
                        <td class="px-4 py-4 text-slate-300">${escapeHtml(item.quality)}</td>
                        <td class="px-4 py-4">${statusMarkup}</td>
                        <td class="px-4 py-4 text-slate-400">${updatedLabel}</td>
                        <td class="px-4 py-4">${action}</td>
                    </tr>
                `;
            })
            .join('');

        downloadsEmpty = document.getElementById('downloads-empty');
    }

    function renderStatusBadge(status) {
        const map = {
            queued: 'bg-slate-800 text-slate-200 border border-slate-700',
            processing: 'bg-amber-500/10 text-amber-300 border border-amber-500/40',
            completed: 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/40',
            failed: 'bg-rose-500/10 text-rose-300 border border-rose-500/40',
        };
        const classes = map[status] ?? 'bg-slate-800 text-slate-300 border border-slate-700';
        return `<span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${classes}">${escapeHtml(status)}</span>`;
    }

    function formatProgressPercent(record) {
        const percent = record?.metadata?.progress?.percent;
        if (percent === null || percent === undefined) {
            return null;
        }

        return Math.min(100, Math.max(0, Math.round(percent)));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '<')
            .replace(/>/g, '>');
    }
});
</script>
@endpush
@endsection
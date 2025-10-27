# Video Downloader Architecture Guide

This document explains how the Laravel card-based downloader application is structured, how downloads move through the system, and what operational considerations apply.

---

## High-Level Overview

The application is composed of these key layers:

- **HTTP API** – `/api/v1/*` endpoints backed by `DownloadController`. Accepts requests from the card UI and delegates to services.
- **Services** – `DownloadService` orchestrates persistence and queueing. `VideoDownloadApiClient` wraps the external API.
- **Jobs** – `ProcessDownload` runs on the `downloads` queue, calling the external API and updating records.
- **Persistence** – `downloads` table tracks every job with soft metadata for audit and status.
- **Frontend** – `home.blade.php` renders the card layout with progressive enhancement (fetch + poll).
- **Rate Limiting** – Centralised with `RateLimiter::for('downloads-api')` to avoid hammering the external provider.

```mermaid
flowchart LR
    UserInput[Card UI<br/>video URL] --> Lookup[Fetch /api/v1/video-info]
    Lookup --> UIState[Render metadata + enable cards]
    UIState --> SubmitDownload[POST /api/v1/download]
    SubmitDownload --> Record[Persist Download model<br/>status = queued]
    Record --> QueueJob[Dispatch ProcessDownload job]
    QueueJob --> ExternalAPI[Call saver API via VideoDownloadApiClient]
    ExternalAPI --> UpdateModel[Update Download row<br/>status + metadata]
    UpdateModel --> Poller[Client polling /download-status]
    Poller --> Ready[Show download link or error badge]
```

---

## Download Lifecycle

1. **Fetch metadata** – UI calls `GET /api/v1/video-info?url=`. `DownloadService::fetchVideoInfo` guards and relays to the external API.
2. **Queue job** – `POST /api/v1/download` creates a `Download` row (status `queued`) and dispatches `ProcessDownload`.
3. **Processing** – the job sets `status = processing`, calls either `downloadVideo` or `extractAudio`, and updates metadata.
4. **Completion** – on success the job stores the remote download URL and marks `status = completed`. Failures capture the message.
5. **Polling** – the frontend polls `GET /api/v1/download-status/{download_id}` until the job finishes, updating the status table.

---

## Background Processing & Storage Strategy

- Queue connection defaults to `database`. Run `php artisan queue:table` and `php artisan migrate` (already scaffolded by Laravel) to enable.
- Recommended worker command: `php artisan queue:work --queue=downloads --sleep=3 --max-time=3600`.
- The external API serves file URLs directly; no local disk storage is required by default. If you need to proxy or cache files, add a storage column and use Laravel filesystem.
- Implement a scheduler task to prune stale records (e.g. hourly job deleting `status = completed` older than N days).

---

## Validation, Error Handling & Logging

- Request validation is centralised in `StoreDownloadRequest`.
- Controller responses surface human-readable errors to the frontend (HTTP 400 on external API failure).
- `ProcessDownload` uses guarded updates and rescues with `failed()` callback, logging warning entries via `Log::warning`.
- Rate-limited queue middleware ensures we don’t exceed provider limits (keyed by client IP).
- UI shows inline feedback and disables buttons while requests are inflight.

---

## Testing Strategy

1. **Feature tests** – simulate user flow: fetch info, queue download, poll status. Use HTTP fake responses for the external API so tests remain deterministic.
2. **Service unit tests** – cover `DownloadService::queueDownload`, verifying accurate model attributes and job dispatch.
3. **Job tests** – use `Bus::fake` / `Http::fake` to validate `ProcessDownload` transitions (success & failure paths).
4. **JavaScript interaction** – rely on Laravel Dusk or Cypress to ensure cards enable/disable correctly and the table updates when mock endpoints respond.

---

## Deployment Checklist

- Configure queue worker(s) and ensure `queue:work` runs under a process manager (Supervisor, systemd, Laravel Horizon).
- Set environment variables: `VIDEO_DOWNLOAD_API_*`, rate limit values, queue connection credentials.
- Enable HTTPS and ensure CORS policies align with hosting (if serving cross-domain).
- Log channel should route warnings to a central aggregator for failed downloads.
- Set up schedule for pruning: `php artisan schedule:work` with a command to delete stale `downloads` rows if storage privacy is important.

---

## Next Steps / Extensibility

- Add user authentication & personal dashboards if multi-tenant tracking is needed.
- Persist signed URLs locally for expiring downloads.
- Hook into notifications (email/SMS/Webhooks) when `Download` transitions to completed.
- Support playlist/batch conversions by extending `DownloadService` to accept arrays and dispatch multiple jobs.

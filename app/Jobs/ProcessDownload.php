<?php

namespace App\Jobs;

use App\Models\Download;
use App\Services\EnhancedVideoDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDownload implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Download $download)
    {
        $this->onQueue('downloads');
    }

    /**
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('downloads-api'),
        ];
    }

    public function handle(EnhancedVideoDownloadService $downloadService): void
    {
        $download = $this->download->fresh();

        if (!$download) {
            return;
        }

        try {
            $downloadService->processDownload($download);
        } catch (\Throwable $exception) {
            $this->markFailed($exception->getMessage());
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->markFailed($exception?->getMessage() ?? 'Download job failed unexpectedly.');
    }

    private function markFailed(string $message): void
    {
        $this->download->update([
            'status' => Download::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $message,
        ]);

        Log::warning('ProcessDownload failed', [
            'download_id' => $this->download->download_id,
            'message' => $message,
        ]);
    }
}
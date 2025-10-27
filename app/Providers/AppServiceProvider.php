<?php

namespace App\Providers;

use App\Services\EnhancedVideoDownloadService;
use App\Services\VideoDownloadApiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VideoDownloadApiClient::class, function ($app) {
            $config = config('services.video_download_api', []);

            return new VideoDownloadApiClient(
                $config['key'] ?? '',
                [
                    'base_url' => $config['base_url'] ?? null,
                    'timeout' => $config['timeout'] ?? null,
                    'retry_times' => $config['retry_times'] ?? null,
                    'retry_delay' => $config['retry_delay'] ?? null,
                ]
            );
        });

        $this->app->singleton(EnhancedVideoDownloadService::class, function ($app) {
            return new EnhancedVideoDownloadService($app->make(VideoDownloadApiClient::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('downloads-api', function ($job) {
            $download = property_exists($job, 'download') ? $job->download : null;

            return Limit::perMinute((int) config('services.video_download_api.rate_limit', 60))
                ->by(optional($download)->ip_address ?? 'system');
        });
    }
}

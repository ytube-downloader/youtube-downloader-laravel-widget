<?php

namespace App\Services;

use App\Jobs\ProcessDownload;
use App\Models\Download;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnhancedVideoDownloadService
{
    private VideoDownloadApiClient $apiClient;

    /**
     * Map between friendly quality/format strings and API format identifiers.
     *
     * @var array<string, string|int>
     */
    private array $formatMapping;

    public function __construct(VideoDownloadApiClient $apiClient)
    {
        $this->apiClient = $apiClient;

        $this->formatMapping = [
            'mp3' => VideoDownloadApiClient::FORMAT_MP3,
            'm4a' => VideoDownloadApiClient::FORMAT_M4A,
            'wav' => VideoDownloadApiClient::FORMAT_WAV,
            'flac' => VideoDownloadApiClient::FORMAT_FLAC,
            'aac' => VideoDownloadApiClient::FORMAT_AAC,
            'ogg' => VideoDownloadApiClient::FORMAT_OGG,
            '360p' => VideoDownloadApiClient::FORMAT_360P,
            '480p' => VideoDownloadApiClient::FORMAT_480P,
            '720p' => VideoDownloadApiClient::FORMAT_720P,
            '1080p' => VideoDownloadApiClient::FORMAT_1080P,
            '1440p' => VideoDownloadApiClient::FORMAT_1440P,
            '4k' => VideoDownloadApiClient::FORMAT_4K,
            '8k' => VideoDownloadApiClient::FORMAT_8K,
        ];
    }

    /**
     * Create a local download record and enqueue processing.
     */
    public function createDownload(array $data, string $ipAddress): Download
    {
        $download = Download::create([
            'download_id' => (string) Str::uuid(),
            'video_url' => $data['url'],
            'quality' => $data['quality'],
            'format' => $data['format'],
            'status' => Download::STATUS_PENDING,
            'ip_address' => $ipAddress,
            'metadata' => [
                'user_agent' => request()->header('User-Agent'),
                'requested_at' => now()->toISOString(),
                'api_client' => 'video-download-api.com',
                'options' => $data['options'] ?? [],
            ],
        ]);

        $download->update([
            'status' => Download::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $download = $download->fresh();

        try {
            $this->processDownload($download);
        } catch (\Throwable $exception) {
            throw $exception;
        }

        return $download->fresh();
    }

    /**
     * Retrieve video information with caching.
     */
    public function getVideoInfo(string $url): array
    {
        $cacheKey = 'video_info_' . md5($url);
        $ttl = (int) config('services.video_download_api.cache_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($url) {
            try {
                if (!$this->apiClient->isValidVideoUrl($url)) {
                    throw new \InvalidArgumentException('Invalid or unsupported video URL.');
                }

                $response = $this->apiClient->getVideoInfo($url);

                if (!($response['success'] ?? false)) {
                    throw new \RuntimeException($response['error'] ?? 'Failed to retrieve video information.');
                }

                return $this->formatVideoInfo($response['data'] ?? []);
            } catch (\Throwable $exception) {
                Log::error('Failed to get video info', [
                    'url' => $url,
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => $exception->getMessage(),
                ];
            }
        });
    }

    /**
     * Process a queued download by invoking the external API.
     */
    public function processDownload(Download $download): void
    {
        if ($download->isTerminal()) {
            return;
        }

        $metadata = $download->metadata ?? [];

        $existingProgressUrl = Arr::get($metadata, 'progress_url') ?? Arr::get($metadata, 'progress_poll_url');
        $existingDownloadId = Arr::get($metadata, 'api_download_id');

        if ($existingProgressUrl && $existingDownloadId) {
            $this->resumeDownloadFromProgress($download, (string) $existingDownloadId, (string) $existingProgressUrl);

            return;
        }

        try {
            $download->update([
                'status' => Download::STATUS_PROCESSING,
                'started_at' => $download->started_at ?? now(),
            ]);

            $apiFormat = $this->formatMapping[$download->format] ?? VideoDownloadApiClient::FORMAT_1080P;
            $options = Arr::get($download->metadata ?? [], 'options', []);
            $isAudio = in_array($download->format, ['mp3', 'm4a', 'wav', 'flac', 'aac', 'ogg'], true);

            Log::debug('Download processing format mapping', [
                'download_id' => $download->download_id,
                'requested_format' => $download->format,
                'mapped_api_format' => $apiFormat,
                'is_audio' => $isAudio,
            ]);
            $response = $isAudio
                ? $this->apiClient->extractAudio(
                    $download->video_url,
                    $apiFormat,
                    (int) ($options['audio_quality'] ?? VideoDownloadApiClient::AUDIO_QUALITY_STANDARD),
                    $options
                )
                : $this->apiClient->downloadVideo(
                    $download->video_url,
                    $apiFormat,
                    $options
                );

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException($response['error'] ?? 'API download failed');
            }

            $this->handleSuccessfulResponse($download, $response);
        } catch (\Throwable $exception) {
            $this->markDownloadFailed($download, $exception->getMessage());
        }
    }

    /**
     * Retrieve download status from the API and apply to local record.
     */
    public function getDownloadStatus(string $downloadId): array
    {
        $response = $this->apiClient->getDownloadStatus($downloadId);

        if ($response['success'] ?? false) {
            $download = Download::where('download_id', $downloadId)->first();

            if ($download) {
                $this->updateLocalDownloadStatus($download, $response['data'] ?? []);
            }
        }

        return $response;
    }

    /**
     * Download a video immediately via the API (utility).
     */
    public function downloadVideo(string $url, string $quality = '1080p', array $options = []): array
    {
        $format = $this->formatMapping[$quality] ?? VideoDownloadApiClient::FORMAT_1080P;

        return $this->apiClient->downloadVideo($url, $format, $options);
    }

    /**
     * Extract audio immediately via the API (utility).
     */
    public function extractAudio(
        string $url,
        string $format = 'mp3',
        int $quality = VideoDownloadApiClient::AUDIO_QUALITY_STANDARD,
        array $options = []
    ): array {
        $formatCode = $this->formatMapping[$format] ?? VideoDownloadApiClient::FORMAT_MP3;

        return $this->apiClient->extractAudio($url, $formatCode, $quality, $options);
    }

    public function download4K(string $url, array $options = []): array
    {
        return $this->apiClient->download4K($url, $options);
    }

    public function extractWAV(string $url, int $quality = VideoDownloadApiClient::AUDIO_QUALITY_PREMIUM): array
    {
        return $this->apiClient->extractWAV($url, $quality);
    }

    public function extractFLAC(string $url): array
    {
        return $this->apiClient->extractFLAC($url);
    }

    public function batchDownload(array $videoUrls, string $quality = '1080p', array $options = []): array
    {
        $format = $this->formatMapping[$quality] ?? VideoDownloadApiClient::FORMAT_1080P;

        return $this->apiClient->batchDownload($videoUrls, $format, $options);
    }

    private function handleSuccessfulResponse(Download $download, array $response): void
    {
        $downloadId = $response['download_id'] ?? null;
        $rawProgressUrl = $response['progress_poll_url'] ?? $response['progress_url'] ?? null;
        $progressUrl = $this->apiClient->resolveProgressUrl($rawProgressUrl, $downloadId);

        $metadata = $this->mergeMetadata($download, [
            'api_download_id' => $downloadId,
            'progress_url' => $progressUrl,
            'progress_poll_url' => $progressUrl,
            'download_url' => $response['download_url'] ?? null,
            'content_html' => $response['content_html'] ?? null,
            'alternative_download_urls' => $response['alternative_download_urls'] ?? null,
            'api_response' => $response,
        ]);

        $download->update([
            'metadata' => $metadata,
        ]);

        if ($downloadId) {
            $this->monitorDownloadProgress($download, (string) $downloadId, $progressUrl);

            return;
        }

        $contentPayload = array_filter([
            'download_url' => $response['download_url'] ?? null,
            'raw_content' => $response['content'] ?? null,
            'content_html' => $response['content_html'] ?? null,
            'alternative_download_urls' => $response['alternative_download_urls'] ?? null,
        ], static fn ($value) => $value !== null);

        $this->finalizeSuccessfulDownload(
            $download,
            $response['info'] ?? [],
            $contentPayload
        );
    }

    private function resumeDownloadFromProgress(Download $download, string $downloadId, string $progressUrl): void
    {
        if ($download->isTerminal()) {
            return;
        }

        $payload = [
            'status' => Download::STATUS_PROCESSING,
        ];

        if (!$download->started_at) {
            $payload['started_at'] = now();
        }

        $download->update($payload);

        $this->monitorDownloadProgress($download->fresh(), $downloadId, $progressUrl);
    }

    private function monitorDownloadProgress(Download $download, string $downloadId, ?string $progressUrl): void
    {
        $maxAttempts = 30;
        $delaySeconds = 2;

        $endpoint = $progressUrl ?: $this->apiClient->getProgressUrl($downloadId);
        $currentDownload = $download->fresh();

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if (!$currentDownload || $currentDownload->isTerminal()) {
                return;
            }

            $progressResponse = $this->apiClient->checkProgress($endpoint);

            if (!($progressResponse['success'] ?? false)) {
                Log::debug('Download progress check unsuccessful', [
                    'download_id' => $downloadId,
                    'progress_url' => $endpoint,
                    'attempt' => $attempt + 1,
                    'error' => $progressResponse['error'] ?? 'Unknown error',
                ]);

                sleep($delaySeconds);
                $currentDownload = $currentDownload->fresh();

                continue;
            }

            $payload = $progressResponse['data'] ?? [];

            $this->applyProgressPayload($currentDownload, $payload, $downloadId);

            $currentDownload = $currentDownload->fresh();

            if (!$currentDownload) {
                return;
            }

            if ($currentDownload->isTerminal()) {
                return;
            }

            if ($this->isProgressComplete($payload)) {
                $this->finalizeProgressDownload($currentDownload, $payload);

                return;
            }

            sleep($delaySeconds);
        }

        $freshDownload = $currentDownload?->fresh() ?? $download->fresh();

        if ($freshDownload && !$freshDownload->isTerminal()) {
            ProcessDownload::dispatch($freshDownload)->delay(now()->addSeconds($delaySeconds * 5));

            Log::info('Re-queued download monitoring job after window elapsed', [
                'download_id' => $download->download_id,
                'api_download_id' => $downloadId,
                'progress_url' => $endpoint,
            ]);

            return;
        }

        Log::warning('Download still processing after monitoring window', [
            'download_id' => $download->download_id,
            'api_download_id' => $downloadId,
            'progress_url' => $endpoint,
        ]);
    }

    private function applyProgressPayload(Download $download, array $payload, string $downloadId): void
    {
        if ($download->isTerminal()) {
            return;
        }

        $progressValue = (int) ($payload['progress'] ?? 0);
        $progressPercent = $this->computeProgressPercent($progressValue);

        $metadata = $this->mergeMetadata($download, [
            'progress' => [
                'raw_value' => $progressValue,
                'percent' => $progressPercent,
                'text' => $payload['text'] ?? $payload['message'] ?? null,
                'checked_at' => now()->toISOString(),
                'source' => 'progress_url',
                'payload' => $payload,
            ],
            'alternative_download_urls' => $payload['alternative_download_urls'] ?? null,
            'api_download_id' => $downloadId,
        ]);

        $update = [
            'status' => Download::STATUS_PROCESSING,
            'metadata' => $metadata,
        ];

        if (!empty($payload['download_url']) && is_string($payload['download_url'])) {
            $update['storage_path'] = $payload['download_url'];
            $metadata['download_url'] = $payload['download_url'];
        }

        if (!$download->started_at) {
            $update['started_at'] = now();
        }

        $download->update($update);
    }

    private function finalizeProgressDownload(Download $download, array $payload): void
    {
        if ($download->isTerminal()) {
            return;
        }

        $downloadUrl = $this->resolveDownloadUrl($payload);

        $metadata = $download->metadata ?? [];

        $info = Arr::get($metadata, 'api_response.info', []);
        if (!is_array($info)) {
            $info = [];
        }

        $content = Arr::get($metadata, 'api_response.content', []);
        if (!is_array($content)) {
            $content = [];
        }

        if ($downloadUrl) {
            $content['download_url'] = $downloadUrl;
        }

        if (!empty($payload['alternative_download_urls'])) {
            $content['alternative_download_urls'] = $payload['alternative_download_urls'];
        }

        if (isset($payload['progress'])) {
            $content['progress'] = $payload['progress'];
        }

        if (isset($payload['message']) || isset($payload['text'])) {
            $content['status_text'] = $payload['text'] ?? $payload['message'];
        }

        $this->finalizeSuccessfulDownload($download, $info, $content);
    }

    private function resolveDownloadUrl(array $payload): ?string
    {
        $directUrl = $payload['download_url'] ?? null;

        if (is_string($directUrl) && $directUrl !== '') {
            return $directUrl;
        }

        $alternatives = $payload['alternative_download_urls'] ?? null;

        if (!is_array($alternatives) || empty($alternatives)) {
            return null;
        }

        if (Arr::isAssoc($alternatives)) {
            if (isset($alternatives['url']) && is_string($alternatives['url'])) {
                return $alternatives['url'];
            }

            foreach ($alternatives as $candidate) {
                if (is_array($candidate) && isset($candidate['url']) && is_string($candidate['url'])) {
                    return $candidate['url'];
                }
            }

            return null;
        }

        foreach ($alternatives as $candidate) {
            if (is_array($candidate) && isset($candidate['url']) && is_string($candidate['url'])) {
                return $candidate['url'];
            }
        }

        return null;
    }

    private function isProgressComplete(array $payload): bool
    {
        $progressValue = (int) ($payload['progress'] ?? 0);
        $text = strtolower((string) ($payload['text'] ?? $payload['message'] ?? ''));

        if ($this->resolveDownloadUrl($payload)) {
            return true;
        }

        if ($progressValue >= 1000) {
            return true;
        }

        if ($progressValue > 100 && $progressValue < 1000) {
            return false;
        }

        if ($progressValue >= 100) {
            return true;
        }

        return str_contains($text, 'finish')
            || str_contains($text, 'ready')
            || str_contains($text, 'completed')
            || str_contains($text, 'complete')
            || str_contains($text, 'read');
    }

    private function computeProgressPercent(int $progressValue): int
    {
        if ($progressValue <= 0) {
            return 0;
        }

        if ($progressValue >= 1000) {
            return 100;
        }

        if ($progressValue <= 100) {
            return min(100, $progressValue);
        }

        return min(100, (int) round($progressValue / 10));
    }

    private function finalizeSuccessfulDownload(Download $download, array $info, array $content): void
    {
        $download->update([
            'status' => Download::STATUS_COMPLETED,
            'completed_at' => now(),
            'video_title' => $info['title'] ?? $download->video_title,
            'file_size' => $this->extractFileSize($info),
            'storage_path' => $content['download_url'] ?? Arr::get($info, 'download_url'),
            'metadata' => $this->mergeMetadata($download, [
                'video_duration' => $this->extractDuration($info),
                'info' => $info,
                'content' => $content,
                'content_html' => $content['content_html'] ?? null,
                'alternative_download_urls' => $content['alternative_download_urls'] ?? null,
            ]),
        ]);

        Log::info('Download completed via API', [
            'download_id' => $download->download_id,
        ]);
    }

    private function updateLocalDownloadStatus(Download $download, array $apiData): void
    {
        $statusMapping = [
            'completed' => Download::STATUS_COMPLETED,
            'processing' => Download::STATUS_PROCESSING,
            'failed' => Download::STATUS_FAILED,
            'pending' => Download::STATUS_PROCESSING,
        ];

        $apiStatus = $apiData['status'] ?? 'processing';
        $localStatus = $statusMapping[$apiStatus] ?? Download::STATUS_PROCESSING;

        $payload = [
            'status' => $localStatus,
            'metadata' => $this->mergeMetadata($download, [
                'progress' => $apiData['progress'] ?? null,
                'status_payload' => $apiData,
            ]),
        ];

        if ($localStatus === Download::STATUS_COMPLETED) {
            $payload['completed_at'] = now();
            $payload['storage_path'] = $apiData['download_url'] ?? $download->storage_path;
            $payload['file_size'] = $this->extractFileSize($apiData);
        }

        if ($localStatus === Download::STATUS_FAILED) {
            $payload['error_message'] = $apiData['error'] ?? 'Download failed.';
            $payload['completed_at'] = now();
        }

        $download->update($payload);
    }

    private function markDownloadFailed(Download $download, string $message): void
    {
        $download->update([
            'status' => Download::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
            'metadata' => $this->mergeMetadata($download, [
                'failed_at' => now()->toISOString(),
            ]),
        ]);

        Log::error('Download failed via API', [
            'download_id' => $download->download_id,
            'error' => $message,
        ]);
    }

    private function formatVideoInfo(array $apiData): array
    {
        $info = $apiData['info'] ?? [];

        return [
            'success' => true,
            'video_info' => [
                'title' => $info['title'] ?? 'Unknown Title',
                'duration' => $this->extractDuration($info),
                'thumbnail' => $info['image'] ?? $info['thumbnail'] ?? null,
                'views' => $info['views'] ?? null,
                'upload_date' => $info['upload_date'] ?? null,
                'uploader' => $info['uploader'] ?? $info['author'] ?? null,
                'description' => $info['description'] ?? null,
                'available_qualities' => $this->getAvailableQualities(),
                'available_formats' => $this->getAvailableFormats(),
                'estimated_sizes' => $this->calculateEstimatedSizes($info),
            ],
        ];
    }

    private function extractDuration(array $info): string
    {
        if (isset($info['duration'])) {
            return (string) $info['duration'];
        }

        if (isset($info['duration_seconds'])) {
            $seconds = (int) $info['duration_seconds'];

            return gmdate($seconds >= 3600 ? 'H:i:s' : 'i:s', $seconds);
        }

        return 'Unknown';
    }

    private function extractFileSize(array $info): ?int
    {
        $filesize = Arr::get($info, 'filesize');

        if (is_numeric($filesize)) {
            return (int) $filesize;
        }

        return null;
    }

    private function getAvailableQualities(): array
    {
        return ['4K', '1440p', '1080p', '720p', '480p', '360p'];
    }

    private function getAvailableFormats(): array
    {
        return ['MP4', 'WEBM', 'MP3', 'WAV', 'M4A', 'AAC', 'FLAC', 'OGG'];
    }

    private function calculateEstimatedSizes(array $info): array
    {
        $durationSeconds = (int) ($info['duration_seconds'] ?? 180);
        $sizes = [];

        foreach ($this->formatMapping as $format => $apiFormat) {
            $sizes[$format] = $this->apiClient->estimateFileSize($durationSeconds, $apiFormat)['formatted'];
        }

        return $sizes;
    }

    /**
     * Merge additional metadata into the download record.
     */
    private function mergeMetadata(Download $download, array $extra): array
    {
        return array_filter(array_merge($download->metadata ?? [], $extra), fn ($value) => $value !== null);
    }
}
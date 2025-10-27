<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoDownloadApiClient
{
    private const DEFAULT_BASE_URL = 'https://p.savenow.to/ajax/download.php';
    private const DEFAULT_PROGRESS_ENDPOINT = 'https://p.savenow.to/api/progress';
    private const DEFAULT_LEGACY_PROGRESS_ENDPOINT = 'https://p.savenow.to/ajax/progress';
    private const DEFAULT_RETRY_DELAY = 1000;
    private const DEFAULT_TIMEOUT = 120;

    /**
     * Format constants for different video/audio qualities.
     */
    public const FORMAT_MP3 = 'mp3';
    public const FORMAT_M4A = 'm4a';
    public const FORMAT_WEBM_AUDIO = 'webm';
    public const FORMAT_AAC = 'aac';
    public const FORMAT_FLAC = 'flac';
    public const FORMAT_WAV = 'wav';
    public const FORMAT_OGG = 'ogg';

    public const FORMAT_360P = '360';
    public const FORMAT_480P = '480';
    public const FORMAT_720P = '720';
    public const FORMAT_1080P = '1080';
    public const FORMAT_1440P = '1440';
    public const FORMAT_4K = '2160';
    public const FORMAT_8K = '4320';

    /**
     * Audio quality bitrates.
     */
    public const AUDIO_QUALITY_LOW = 96;
    public const AUDIO_QUALITY_STANDARD = 128;
    public const AUDIO_QUALITY_HIGH = 192;
    public const AUDIO_QUALITY_PREMIUM = 256;
    public const AUDIO_QUALITY_MAXIMUM = 320;

    private string $apiKey;
    private string $baseUrl;
    private array $defaultOptions;
    private string $progressEndpoint;
    private string $legacyProgressEndpoint;

    public function __construct(?string $apiKey = null, array $options = [])
    {
        $config = config('services.video_download_api', []);

        $this->apiKey = $apiKey ?? (string) ($config['key'] ?? '');
        $this->baseUrl = (string) ($options['base_url'] ?? $config['base_url'] ?? self::DEFAULT_BASE_URL);
        $this->progressEndpoint = (string) ($options['progress_endpoint'] ?? $config['progress_endpoint'] ?? self::DEFAULT_PROGRESS_ENDPOINT);
        $this->legacyProgressEndpoint = (string) ($options['legacy_progress_endpoint'] ?? $config['legacy_progress_endpoint'] ?? self::DEFAULT_LEGACY_PROGRESS_ENDPOINT);

        $this->defaultOptions = [
            'timeout' => (int) ($options['timeout'] ?? $config['timeout'] ?? self::DEFAULT_TIMEOUT),
            'retry_times' => max(1, (int) ($options['retry_times'] ?? $config['retry_times'] ?? 3)),
            'retry_delay' => max(100, (int) ($options['retry_delay'] ?? $config['retry_delay'] ?? self::DEFAULT_RETRY_DELAY)),
        ];
    }

    /**
     * Get video information without downloading the file.
     */
    public function getVideoInfo(string $videoUrl): array
    {
        try {
            $response = $this->makeRequest([
                'url' => $videoUrl,
                'format' => self::FORMAT_1080P,
                'add_info' => 1,
                'info_only' => 1,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Download video in specified quality.
     */
    public function downloadVideo(string $videoUrl, string $format = self::FORMAT_1080P, array $options = []): array
    {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => $format,
            'add_info' => 1,
        ], $options);

        return $this->initiateDownload($params, 'video');
    }

    /**
     * Extract audio in specified format.
     */
    public function extractAudio(
        string $videoUrl,
        string $format = self::FORMAT_MP3,
        int $audioQuality = self::AUDIO_QUALITY_STANDARD,
        array $options = []
    ): array {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => $format,
            'audio_quality' => $audioQuality,
            'add_info' => 1,
        ], $options);

        return $this->initiateDownload($params, 'audio');
    }

    /**
     * Download 4K video.
     */
    public function download4K(string $videoUrl, array $options = []): array
    {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => self::FORMAT_4K,
            'add_info' => 1,
            'allow_extended_duration' => 1,
        ], $options);

        return $this->initiateDownload($params, '4k_video');
    }

    /**
     * Download 8K video.
     */
    public function download8K(string $videoUrl, array $options = []): array
    {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => self::FORMAT_8K,
            'add_info' => 1,
            'allow_extended_duration' => 1,
        ], $options);

        return $this->initiateDownload($params, '8k_video');
    }

    /**
     * Extract high-quality WAV audio.
     */
    public function extractWAV(string $videoUrl, int $audioQuality = self::AUDIO_QUALITY_PREMIUM, array $options = []): array
    {
        return $this->extractAudio($videoUrl, self::FORMAT_WAV, $audioQuality, $options);
    }

    /**
     * Extract lossless FLAC audio.
     */
    public function extractFLAC(string $videoUrl, array $options = []): array
    {
        return $this->extractAudio($videoUrl, self::FORMAT_FLAC, 0, $options);
    }

    /**
     * Download with specific audio language.
     */
    public function downloadWithLanguage(string $videoUrl, string $format, string $audioLanguage, array $options = []): array
    {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => $format,
            'audio_language' => $audioLanguage,
            'add_info' => 1,
        ], $options);

        return $this->initiateDownload($params, 'video_with_language');
    }

    /**
     * Download video clip between given timestamps.
     */
    public function downloadClip(string $videoUrl, string $format, int $startTime, int $endTime, array $options = []): array
    {
        $params = array_merge([
            'url' => $videoUrl,
            'format' => $format,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'add_info' => 1,
        ], $options);

        return $this->initiateDownload($params, 'clip');
    }

    /**
     * Retrieve download status using the progress polling endpoint (with legacy fallback).
     */
    public function getDownloadStatus(string $downloadId): array
    {
        return $this->checkProgress($downloadId);
    }

    /**
     * Check download progress using the polling endpoint.
     *
     * @param  string  $progressIdentifier  Either a poll URL or a download identifier.
     */
    public function checkProgress(string $progressIdentifier): array
    {
        $query = [];

        if ($this->apiKey !== '') {
            $query['apikey'] = $this->apiKey;
        }

        if (str_starts_with(strtolower($progressIdentifier), 'http')) {
            $normalizedUrl = $this->normalizeProgressUrl($progressIdentifier);

            return $this->performProgressRequest(
                $normalizedUrl,
                $query,
                null,
                $progressIdentifier
            );
        }

        $normalizedId = trim($progressIdentifier);

        if ($normalizedId === '') {
            return [
                'success' => false,
                'error' => 'Empty download identifier provided for progress lookup',
            ];
        }

        $attempts = [
            [$this->progressEndpoint, array_merge($query, ['id' => $normalizedId])],
            [$this->legacyProgressEndpoint, array_merge($query, ['id' => $normalizedId])],
            [$this->buildProgressUrl($normalizedId), $query],
        ];

        $lastError = null;
        $lastData = null;

        foreach ($attempts as [$endpoint, $attemptQuery]) {
            $result = $this->performProgressRequest(
                $endpoint,
                $attemptQuery,
                $normalizedId,
                $progressIdentifier
            );

            if ($result['success'] ?? false) {
                return $result;
            }

            $lastError = $result['error'] ?? $lastError;
            $lastData = $result['data'] ?? $lastData;
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Progress request failed',
            'data' => $lastData,
        ];
    }

    private function performProgressRequest(string $url, array $query, ?string $downloadId, string $progressIdentifier): array
    {
        try {
            $response = Http::timeout($this->defaultOptions['timeout'])
                ->acceptJson()
                ->get($url, $query);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $payload = $response->json();

            if (!is_array($payload)) {
                throw new \RuntimeException('Invalid progress response payload');
            }

            $normalized = $this->normalizeProgressPayload(
                $payload,
                $downloadId,
                $url
            );

            if (!($normalized['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $normalized['message'] ?? $normalized['error'] ?? 'Progress request failed',
                    'data' => $normalized,
                ];
            }

            return [
                'success' => true,
                'data' => $normalized,
            ];
        } catch (\Throwable $exception) {
            Log::debug('Video download progress request failed', [
                'progress_identifier' => $progressIdentifier,
                'progress_endpoint' => $url,
                'query' => $query,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Retrieve supported formats for a specific URL.
     */
    public function getSupportedFormats(string $videoUrl): array
    {
        try {
            $response = $this->makeRequest([
                'url' => $videoUrl,
                'action' => 'get_formats',
                'add_info' => 1,
            ]);

            return [
                'success' => true,
                'formats' => $response['available_formats'] ?? [],
                'info' => $response['info'] ?? null,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Batch download multiple videos.
    private function performProgressRequest(string $url, array $query, ?string $downloadId, string $progressIdentifier): array
    {
        try {
            $response = Http::timeout($this->defaultOptions['timeout'])
                ->acceptJson()
                ->get($url, $query);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $payload = $response->json();

            if (!is_array($payload)) {
                throw new \RuntimeException('Invalid progress response payload');
            }

            $normalized = $this->normalizeProgressPayload(
                $payload,
                $downloadId,
                $url
            );

            if (!($normalized['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $normalized['message'] ?? $normalized['error'] ?? 'Progress request failed',
                    'data' => $normalized,
                ];
            }

            return [
                'success' => true,
                'data' => $normalized,
            ];
        } catch (\Throwable $exception) {
            Log::debug('Video download progress request failed', [
                'progress_identifier' => $progressIdentifier,
                'progress_endpoint' => $url,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

     */
    public function batchDownload(array $videoUrls, string $format, array $options = []): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($videoUrls as $index => $url) {
            try {
                $result = $this->downloadVideo($url, $format, $options);

                $results[] = [
                    'index' => $index,
                    'url' => $url,
                    'result' => $result,
                ];

                if ($result['success'] ?? false) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $exception) {
                $results[] = [
                    'index' => $index,
                    'url' => $url,
                    'result' => [
                        'success' => false,
                        'error' => $exception->getMessage(),
                    ],
                ];
                $failed++;
            }

            if ($index < count($videoUrls) - 1) {
                usleep(500_000); // throttle between requests (500ms)
            }
        }

        return [
            'success' => $failed === 0,
            'summary' => [
                'total' => count($videoUrls),
                'successful' => $successful,
                'failed' => $failed,
            ],
            'results' => $results,
        ];
    }

    /**
     * Determine if the URL belongs to a supported platform.
     */
    public function isValidVideoUrl(string $url): bool
    {
        $supportedDomains = [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'dailymotion.com',
            'facebook.com',
            'instagram.com',
            'tiktok.com',
            'twitter.com',
            'twitch.tv',
        ];

        foreach ($supportedDomains as $domain) {
            if (str_contains($url, $domain)) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    /**
     * Retrieve a human-readable name for a format identifier.
     *
     * @param  string|int  $format
     */
    public function getFormatName(string|int $format): string
    {
        $formatNames = [
            self::FORMAT_MP3 => 'MP3 Audio',
            self::FORMAT_M4A => 'M4A Audio',
            self::FORMAT_WEBM_AUDIO => 'WebM Audio',
            self::FORMAT_AAC => 'AAC Audio',
            self::FORMAT_FLAC => 'FLAC Audio (Lossless)',
            self::FORMAT_WAV => 'WAV Audio (Uncompressed)',
            self::FORMAT_OGG => 'OGG Audio',
            self::FORMAT_360P => '360p Video',
            self::FORMAT_480P => '480p Video',
            self::FORMAT_720P => '720p HD Video',
            self::FORMAT_1080P => '1080p Full HD Video',
            self::FORMAT_1440P => '1440p 2K Video',
            self::FORMAT_4K => '4K Ultra HD Video',
            self::FORMAT_8K => '8K Ultra HD Video',
        ];

        return $formatNames[$format] ?? sprintf('Format %s', $format);
    }

    /**
     * Roughly estimate expected file sizes.
     *
     * @param  string|int  $format
     */
    public function estimateFileSize(int $durationSeconds, string|int $format): array
    {
        $formatKey = (string) $format;

        $sizesPerMinute = [
            (string) self::FORMAT_MP3 => 1.0,
            (string) self::FORMAT_M4A => 0.8,
            (string) self::FORMAT_AAC => 0.9,
            (string) self::FORMAT_WAV => 10.0,
            (string) self::FORMAT_FLAC => 5.0,
            (string) self::FORMAT_360P => 5.0,
            (string) self::FORMAT_480P => 12.0,
            (string) self::FORMAT_720P => 25.0,
            (string) self::FORMAT_1080P => 50.0,
            (string) self::FORMAT_1440P => 80.0,
            (string) self::FORMAT_4K => 150.0,
            (string) self::FORMAT_8K => 400.0,
        ];

        $minutes = max(1, $durationSeconds) / 60;
        $sizePerMinute = $sizesPerMinute[$formatKey] ?? 25.0;
        $estimatedMb = $minutes * $sizePerMinute;

        return [
            'estimated_mb' => round($estimatedMb, 1),
            'estimated_gb' => round($estimatedMb / 1024, 2),
            'formatted' => $estimatedMb > 1024
                ? round($estimatedMb / 1024, 2) . ' GB'
                : round($estimatedMb, 1) . ' MB',
        ];
    }

    private function initiateDownload(array $params, string $downloadType): array
    {
        try {
            $response = $this->makeRequest($params);

            $downloadId = $response['id'] ?? null;
            $progressUrl = $response['progress_url'] ?? ($downloadId ? $this->buildProgressUrl($downloadId) : null);
            $contentRaw = $response['content'] ?? null;
            $contentHtml = $this->decodeContentHtml($contentRaw);
            $alternativeDownloadUrls = $this->normalizeAlternativeDownloadUrls($response['alternative_download_urls'] ?? null);

            $result = [
                'success' => true,
                'download_id' => $downloadId,
                'download_type' => $downloadType,
                'info' => $response['info'] ?? null,
                'content' => $contentRaw,
                'content_html' => $contentHtml,
                'download_url' => $response['download_url'] ?? null,
                'progress_url' => $progressUrl,
                'progress_poll_url' => $progressUrl,
                'alternative_download_urls' => $alternativeDownloadUrls,
            ];

            if ($contentHtml !== null) {
                $result['content_html'] = $contentHtml;
            }

            if ($progressUrl) {
                $result['progress_poll_url'] = $progressUrl;
            }

            if ($alternativeDownloadUrls !== null) {
                $result['alternative_download_urls'] = $alternativeDownloadUrls;
            }

            if (isset($response['extended_duration'])) {
                $result['extended_duration'] = $response['extended_duration'];
                $result['pricing'] = [
                    'multiplier' => $response['extended_duration']['multiplier'] ?? 1,
                    'original_price' => $response['extended_duration']['original_price'] ?? 0,
                    'final_price' => $response['extended_duration']['final_price'] ?? 0,
                ];
            }

            Log::info('Video download initiated', [
                'download_id' => $result['download_id'],
                'type' => $downloadType,
                'url' => $params['url'] ?? 'unknown',
                'progress_url' => $progressUrl,
            ]);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Video download failed', [
                'error' => $exception->getMessage(),
                'params' => $params,
                'type' => $downloadType,
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'download_type' => $downloadType,
            ];
        }
    }

    /**
     * Perform an HTTP request against the remote API with retry support.
     *
     * @throws \Throwable
     */
    private function makeRequest(array $params): array
    {
        $params['apikey'] = $this->apiKey;

        $sanitizedParams = $this->sanitizeParamsForLogging($params);

        Log::debug('Video download API request initialised', [
            'endpoint' => $this->baseUrl,
            'params' => $sanitizedParams,
        ]);

        $attempt = 0;
        $maxAttempts = $this->defaultOptions['retry_times'];

        while ($attempt < $maxAttempts) {
            try {
                $response = Http::timeout($this->defaultOptions['timeout'])
                    ->acceptJson()
                    ->get($this->baseUrl, $params);

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                $payload = $response->json();
                $payloadForLog = is_array($payload) ? $this->sanitizePayloadForLogging($payload) : $payload;

                Log::debug('Video download API response received', [
                    'endpoint' => $this->baseUrl,
                    'params' => $sanitizedParams,
                    'status' => $response->status(),
                    'payload' => $payloadForLog,
                ]);

                if (!is_array($payload)) {
                    throw new RequestException($response);
                }

                if (!($payload['success'] ?? false)) {
                    throw new \RuntimeException($payload['error'] ?? 'API request failed');
                }

                return $payload;
            } catch (ConnectionException $exception) {
                $attempt++;

                Log::warning('Video download API connection failure', [
                    'endpoint' => $this->baseUrl,
                    'params' => $sanitizedParams,
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                if ($attempt >= $maxAttempts) {
                    throw new \RuntimeException(
                        sprintf('Connection failed after %d attempts: %s', $maxAttempts, $exception->getMessage()),
                        0,
                        $exception
                    );
                }

                usleep($this->defaultOptions['retry_delay'] * 1000);
            } catch (RequestException $exception) {
                $errorResponse = property_exists($exception, 'response') ? $exception->response : null;

                Log::error('Video download API request exception', [
                    'endpoint' => $this->baseUrl,
                    'params' => $sanitizedParams,
                    'status' => $errorResponse?->status(),
                    'body' => $errorResponse?->body(),
                    'error' => $exception->getMessage(),
                ]);

                throw new \RuntimeException('Request failed: ' . $exception->getMessage(), 0, $exception);
            }
        }

        Log::error('Video download API max retries reached', [
            'endpoint' => $this->baseUrl,
            'params' => $sanitizedParams,
            'attempts' => $maxAttempts,
        ]);

        throw new \RuntimeException('Max retry attempts reached');
    }

    private function sanitizeParamsForLogging(array $params): array
    {
        $sanitized = $params;
        unset($sanitized['apikey']);

        if (isset($sanitized['content'])) {
            $sanitized['content'] = null;
        }

        return $sanitized;
    }

    private function sanitizePayloadForLogging(array $payload): array
    {
        $sanitized = $payload;

        if (isset($sanitized['content'])) {
            $sanitized['content'] = null;
        }

        return $sanitized;
    }

    public function getProgressUrl(string $downloadId): string
    {
        return $this->buildProgressUrl($downloadId);
    }

    public function resolveProgressUrl(?string $progressIdentifier, ?string $downloadId = null): ?string
    {
        $candidate = trim((string) $progressIdentifier);

        if ($candidate !== '') {
            return $this->normalizeProgressUrl($candidate);
        }

        if ($downloadId !== null && trim($downloadId) !== '') {
            return $this->buildProgressUrl($downloadId);
        }

        return null;
    }

    private function buildProgressUrl(string $downloadId): string
    {
        $downloadId = trim($downloadId);

        if ($downloadId === '') {
            return $this->progressEndpoint;
        }

        $separator = str_contains($this->progressEndpoint, '?') ? '&' : '?';

        return $this->progressEndpoint . $separator . 'id=' . urlencode($downloadId);
    }

    private function normalizeProgressUrl(string $candidate): string
    {
        $trimmed = trim($candidate);

        if ($trimmed === '') {
            return $this->progressEndpoint;
        }

        if (!str_starts_with(strtolower($trimmed), 'http')) {
            return $this->progressEndpoint;
        }

        $parsed = parse_url($trimmed);

        if (!$parsed || !isset($parsed['host'])) {
            return $trimmed;
        }

        $host = strtolower($parsed['host']);
        $path = $parsed['path'] ?? '';

        if ($host !== 'p.savenow.to') {
            return $trimmed;
        }

        if (str_starts_with($path, '/api/progress')) {
            return $trimmed;
        }

        if (str_starts_with($path, '/ajax/progress')) {
            $query = $parsed['query'] ?? '';
            $idParam = null;

            if ($query !== '') {
                parse_str($query, $params);
                $idParam = $params['id'] ?? $params['download_id'] ?? null;

                if (is_array($idParam)) {
                    $idParam = reset($idParam);
                }
            }

            if ($idParam) {
                return $this->buildProgressUrl((string) $idParam);
            }

            return $this->progressEndpoint . ($query ? ('?' . $query) : '');
        }

        return $trimmed;
    }

    private function decodeContentHtml(mixed $content): ?string
    {
        if (!is_string($content) || $content === '') {
            return null;
        }

        $decoded = base64_decode($content, true);

        return $decoded === false ? null : $decoded;
    }

    private function normalizeProgressPayload(array $payload, ?string $downloadId, string $requestedUrl): array
    {
        $rawSuccess = $payload['success'] ?? $payload['status'] ?? null;

        if (is_numeric($rawSuccess)) {
            $payload['success'] = (int) $rawSuccess > 0;
        } elseif (is_string($rawSuccess)) {
            $payload['success'] = strtolower($rawSuccess) !== 'false';
        } else {
            $payload['success'] = (bool) $rawSuccess;
        }

        $payload['download_id'] = $payload['id'] ?? $payload['download_id'] ?? $downloadId;
        $payload['progress_poll_url'] = $requestedUrl;

        if (!($payload['success'] ?? false)) {
            return $payload;
        }

        $payload['alternative_download_urls'] = $this->normalizeAlternativeDownloadUrls($payload['alternative_download_urls'] ?? null);

        $progressValue = is_numeric($payload['progress'] ?? null)
            ? (int) $payload['progress']
            : 0;

        $payload['progress'] = $progressValue;
        $payload['progress_percent'] = $this->calculateProgressPercent($progressValue);

        return $payload;
    }

    private function calculateProgressPercent(int $progressValue): int
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

    private function normalizeAlternativeDownloadUrls(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (!is_array($value)) {
            return null;
        }

        if (!array_is_list($value)) {
            $normalized = $this->normalizeAlternativeDownloadUrlItem($value);

            return $normalized ? [$normalized] : null;
        }

        $normalized = [];

        foreach ($value as $item) {
            $candidate = $this->normalizeAlternativeDownloadUrlItem($item);

            if ($candidate !== null) {
                $normalized[] = $candidate;
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeAlternativeDownloadUrlItem(mixed $item): ?array
    {
        if (is_object($item)) {
            $item = json_decode(json_encode($item), true);
        }

        if (!is_array($item)) {
            return null;
        }

        $url = $item['url'] ?? null;

        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        return [
            'type' => $item['type'] ?? null,
            'url' => $url,
            'has_ssl' => isset($item['has_ssl']) ? (bool) $item['has_ssl'] : null,
        ];
    }
}
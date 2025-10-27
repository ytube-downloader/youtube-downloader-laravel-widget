<?php

namespace App\Services;

use App\Models\Download;
use Illuminate\Support\Arr;

class DownloadService
{
    private const DEFAULT_VIDEO_QUALITIES = ['4k', '1440p', '1080p', '720p', '480p', '360p'];
    private const DEFAULT_AUDIO_BITRATES = [320, 256, 192, 128, 96];

    public function __construct(private readonly EnhancedVideoDownloadService $enhancedService)
    {
    }

    /**
     * Fetch video information through the enhanced service and normalize the payload
     * to preserve the legacy response structure expected by existing consumers.
     */
    public function fetchVideoInfo(string $url): array
    {
        $result = $this->enhancedService->getVideoInfo($url);

        if (!($result['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unable to fetch video information.',
            ];
        }

        return [
            'success' => true,
            'data' => $this->normalizeVideoInfo($result),
        ];
    }

    /**
     * Queue a download by delegating to the enhanced service while retaining legacy metadata.
     */
    public function queueDownload(string $url, string $quality, string $format, string $clientIp, array $options = []): Download
    {
        $download = $this->enhancedService->createDownload([
            'url' => $url,
            'quality' => $quality,
            'format' => $format,
            'options' => $options,
        ], $clientIp);

        // Persist platform for compatibility with UI expectations.
        $download->update([
            'platform' => $this->detectPlatform($url),
        ]);

        return $download->fresh();
    }

    /**
     * Thin wrappers that expose the enhanced service capabilities to callers that still depend on this facade.
     */
    public function downloadVideo(string $url, string $quality, array $options = []): array
    {
        return $this->enhancedService->downloadVideo($url, $quality, $options);
    }

    public function extractAudio(string $url, string $format, int $bitrate, array $options = []): array
    {
        return $this->enhancedService->extractAudio($url, $format, $bitrate, $options);
    }

    public function download4K(string $url, array $options = []): array
    {
        return $this->enhancedService->download4K($url, $options);
    }

    public function extractWAV(string $url, int $quality): array
    {
        return $this->enhancedService->extractWAV($url, $quality);
    }

    public function extractFLAC(string $url): array
    {
        return $this->enhancedService->extractFLAC($url);
    }

    public function batchDownload(array $urls, string $quality, array $options = []): array
    {
        return $this->enhancedService->batchDownload($urls, $quality, $options);
    }

    /**
     * Refresh the local model status using the remote download identifier.
     */
    public function refreshStatus(Download $download): Download
    {
        $apiDownloadId = Arr::get($download->metadata, 'api_download_id');

        if (!$apiDownloadId) {
            return $download;
        }

        $this->enhancedService->getDownloadStatus($apiDownloadId);

        return $download->fresh();
    }

    /**
     * Fetch the remote status payload without mutating the local model.
     */
    public function fetchRemoteStatus(Download $download): array
    {
        $apiDownloadId = Arr::get($download->metadata, 'api_download_id');

        if (!$apiDownloadId) {
            return [
                'success' => false,
                'error' => 'Download does not have an associated remote identifier.',
            ];
        }

        return $this->enhancedService->getDownloadStatus($apiDownloadId);
    }

    /**
     * Determine the platform associated with the provided URL.
     */
    private function detectPlatform(string $url): ?string
    {
        $domains = [
            'youtube' => ['youtube.com', 'youtu.be'],
            'vimeo' => ['vimeo.com'],
            'dailymotion' => ['dailymotion.com'],
            'facebook' => ['facebook.com', 'fb.watch'],
            'instagram' => ['instagram.com'],
            'tiktok' => ['tiktok.com'],
            'twitter' => ['twitter.com', 'x.com'],
        ];

        foreach ($domains as $platform => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($url, $pattern)) {
                    return $platform;
                }
            }
        }

        return null;
    }

    /**
     * Adapt enhanced service metadata to the legacy structure.
     */
    private function normalizeVideoInfo(array $payload): array
    {
        $info = data_get($payload, 'video_info', []);
        if (!is_array($info)) {
            $info = data_get($payload, 'info', []);
        }

        $title = $this->firstNonEmpty([
            data_get($info, 'title'),
            data_get($payload, 'title'),
            data_get($payload, 'data.title'),
        ]) ?? 'Untitled video';

        $thumbnail = $this->firstNonEmpty([
            data_get($info, 'image'),
            data_get($info, 'thumbnail'),
            data_get($info, 'thumbnails.0.url'),
            data_get($payload, 'thumbnail'),
            data_get($payload, 'image'),
        ]);

        if (is_string($thumbnail) && str_starts_with($thumbnail, '//')) {
            $thumbnail = 'https:' . $thumbnail;
        }

        return [
            'info' => [
                'title' => $title,
                'duration' => $this->normalizeDuration($this->firstScalar([
                    data_get($info, 'duration'),
                    data_get($info, 'duration_seconds'),
                    data_get($payload, 'duration'),
                ])),
                'author' => $this->firstNonEmpty([
                    data_get($info, 'uploader'),
                    data_get($info, 'author'),
                    data_get($payload, 'author'),
                ]),
                'upload_date' => $this->firstNonEmpty([
                    data_get($info, 'upload_date'),
                    data_get($info, 'published'),
                    data_get($info, 'published_at'),
                ]),
                'image' => $thumbnail,
                'thumbnail' => $thumbnail,
                'available_qualities' => $info['available_qualities'] ?? $this->normalizeQualities($payload),
                'available_audio_formats' => $info['available_audio_formats'] ?? $this->normalizeAudioFormats($payload),
                'description' => $this->firstNonEmpty([
                    data_get($info, 'description'),
                    data_get($payload, 'description'),
                ]),
            ],
            'source' => $payload,
        ];
    }

    private function firstNonEmpty(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function firstScalar(array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }

            if (is_numeric($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeDuration(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $seconds = (int) $value;
        } else {
            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            if (preg_match('/^\d+$/', $value)) {
                $seconds = (int) $value;
            } elseif (str_contains($value, ':')) {
                return $value;
            } else {
                return $value;
            }
        }

        if ($seconds < 0) {
            $seconds = 0;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds)
            : sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    private function normalizeQualities(array $response): array
    {
        $candidates = [
            data_get($response, 'video_info.available_qualities'),
            data_get($response, 'info.available_qualities'),
            data_get($response, 'data.available_qualities'),
            data_get($response, 'qualities'),
            data_get($response, 'videos'),
        ];

        $qualities = [];

        foreach ($candidates as $candidate) {
            foreach ($this->extractQualities($candidate) as $quality) {
                $qualities[] = $quality;
            }
        }

        $qualities = array_values(array_unique(array_filter($qualities)));

        if (empty($qualities)) {
            return self::DEFAULT_VIDEO_QUALITIES;
        }

        return $qualities;
    }

    private function extractQualities(mixed $candidate): array
    {
        if ($candidate === null) {
            return [];
        }

        if (is_string($candidate)) {
            $parts = preg_split('/[\s,|\/]+/', $candidate) ?: [];

            return array_values(array_filter(array_map(
                fn (string $value) => $this->castQuality($value),
                $parts
            )));
        }

        if (is_numeric($candidate)) {
            $quality = $this->castQuality((string) $candidate);

            return $quality ? [$quality] : [];
        }

        if (!is_array($candidate)) {
            return [];
        }

        $results = [];

        if (Arr::isAssoc($candidate)) {
            $results = array_merge(
                $results,
                $this->extractQualities($candidate['quality'] ?? null),
                $this->extractQualities($candidate['label'] ?? null),
                $this->extractQualities($candidate['resolution'] ?? null),
                $this->extractQualities($candidate['format'] ?? null),
                $this->extractQualities($candidate['name'] ?? null)
            );

            if (isset($candidate['qualities'])) {
                $results = array_merge($results, $this->extractQualities($candidate['qualities']));
            }
        } else {
            foreach ($candidate as $item) {
                $results = array_merge($results, $this->extractQualities($item));
            }
        }

        return $results;
    }

    private function castQuality(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(['quality', 'hd'], '', $value);
        $value = str_replace(' ', '', $value);

        if (preg_match('/^(2160|4320)p?$/', $value, $matches)) {
            return $matches[1] === '2160' ? '4k' : '8k';
        }

        if (preg_match('/^\d{3,4}p$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{3,4}$/', $value)) {
            return $value . 'p';
        }

        if (in_array($value, ['4k', '8k', 'uhd', 'fullhd'], true)) {
            return match ($value) {
                'uhd' => '4k',
                'fullhd' => '1080p',
                default => $value,
            };
        }

        return null;
    }

    private function normalizeAudioFormats(array $response): array
    {
        $candidates = [
            data_get($response, 'video_info.available_audio_formats'),
            data_get($response, 'info.available_audio_formats'),
            data_get($response, 'audio'),
            data_get($response, 'audio_formats'),
        ];

        $bitrates = [];

        foreach ($candidates as $candidate) {
            foreach ($this->extractAudioBitrates($candidate) as $bitrate) {
                $bitrates[] = $bitrate;
            }
        }

        $bitrates = array_values(array_unique(array_filter($bitrates)));

        if (empty($bitrates)) {
            return self::DEFAULT_AUDIO_BITRATES;
        }

        rsort($bitrates);

        return $bitrates;
    }

    private function extractAudioBitrates(mixed $candidate): array
    {
        if ($candidate === null) {
            return [];
        }

        if (is_numeric($candidate)) {
            $bitrate = $this->castBitrate($candidate);

            return $bitrate ? [$bitrate] : [];
        }

        if (is_string($candidate)) {
            $parts = preg_split('/[\s,|\/]+/', $candidate) ?: [];

            return array_values(array_filter(array_map(
                fn (string $value) => $this->castBitrate($value),
                $parts
            )));
        }

        if (!is_array($candidate)) {
            return [];
        }

        $results = [];

        if (Arr::isAssoc($candidate)) {
            $results = array_merge(
                $results,
                $this->extractAudioBitrates($candidate['bitrate'] ?? null),
                $this->extractAudioBitrates($candidate['kbps'] ?? null),
                $this->extractAudioBitrates($candidate['quality'] ?? null)
            );
        } else {
            foreach ($candidate as $item) {
                $results = array_merge($results, $this->extractAudioBitrates($item));
            }
        }

        return $results;
    }

    private function castBitrate(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^0-9]/', '', $value) ?? '';
            if ($value === '') {
                return null;
            }
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }
}
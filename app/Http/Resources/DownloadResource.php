<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadResource extends JsonResource
{
    /**
     * @param  array<string, mixed>|Request  $request
     */
    public function toArray($request): array
    {
        return [
            'download_id' => $this->download_id,
            'video_url' => $this->video_url,
            'video_title' => $this->video_title,
            'platform' => $this->platform,
            'quality' => $this->quality,
            'format' => $this->format,
            'status' => $this->status,
            'storage_path' => $this->storage_path,
            'file_size' => $this->formatted_size,
            'queued_at' => optional($this->queued_at)->toIso8601String(),
            'started_at' => optional($this->started_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'metadata' => $this->metadata,
            'error_message' => $this->error_message,
        ];
    }
}
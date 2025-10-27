<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'download_id',
        'video_url',
        'video_title',
        'platform',
        'quality',
        'format',
        'file_size',
        'status',
        'storage_path',
        'error_message',
        'metadata',
        'queued_at',
        'started_at',
        'completed_at',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function getRouteKeyName(): string
    {
        return 'download_id';
    }

    protected function formattedSize(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->file_size
                ? $this->formatBytes($this->file_size)
                : null,
        );
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
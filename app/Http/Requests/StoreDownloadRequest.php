<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'quality' => ['nullable', 'string', Rule::in(['4k', '1440p', '1080p', '720p', '480p', '360p'])],
            'format' => ['nullable', 'string', Rule::in(['mp4', 'webm', 'mp3', 'wav', 'm4a', 'aac', 'flac', 'ogg'])],
            'options' => ['sometimes', 'array'],
            'options.audio_quality' => ['sometimes', 'integer', Rule::in([96, 128, 192, 256, 320])],
            'options.audio_language' => ['sometimes', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quality' => $this->input('quality', '1080p'),
            'format' => $this->input('format', 'mp4'),
        ]);
    }
}
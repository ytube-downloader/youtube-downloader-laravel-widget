<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDownloadRequest;
use App\Http\Resources\DownloadResource;
use App\Models\Download;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function __construct(private readonly DownloadService $downloadService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $result = $this->downloadService->fetchVideoInfo($request->string('url'));

        return response()->json($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    public function store(StoreDownloadRequest $request): JsonResponse
    {
        $download = $this->downloadService->queueDownload(
            url: $request->string('url'),
            quality: $request->string('quality'),
            format: $request->string('format'),
            clientIp: $request->ip(),
            options: $request->validated('options', [])
        );

        return DownloadResource::make($download)
            ->additional([
                'success' => true,
                'message' => 'Download request accepted',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function status(Download $download): DownloadResource
    {
        $refreshed = $this->downloadService->refreshStatus($download);

        return DownloadResource::make($refreshed);
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\MediaRepository;
use App\Services\ImageService;
use App\Services\MediaService;

class MediaController
{
    private MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService(
            new MediaRepository(),
            new ImageService(),
        );
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search'      => $request->query('search'),
            'type'        => $request->query('type'),
            'uploaded_by' => $request->query('uploaded_by'),
        ];

        return Response::success(
            $this->mediaService->all($this->websiteId($request), $filters)
        );
    }

    public function show(Request $request): Response
    {
        $media = $this->mediaService->findById(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($media);
    }

    public function upload(Request $request): Response
    {
        $auth      = $request->param('_auth');
        $websiteId = $this->websiteId($request);
        $file      = $_FILES['file'] ?? null;

        if ($file === null) {
            return Response::json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['file' => ['No file was uploaded.']],
            ], 422);
        }

        $media = $this->mediaService->upload(
            file:       $file,
            websiteId:  $websiteId,
            uploadedBy: (int) $auth->sub,
            altText:    $_POST['alt_text'] ?? null,
            caption:    $_POST['caption'] ?? null,
        );

        return Response::created($media, 'File uploaded successfully.');
    }

    public function updateMeta(Request $request): Response
    {
        $body  = $request->body();
        $media = $this->mediaService->updateMeta(
            (int) $request->param('id'),
            $this->websiteId($request),
            $body['alt_text'] ?? null,
            $body['caption'] ?? null,
        );
        return Response::success($media, 'Media updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->mediaService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success([], 'Media deleted successfully.');
    }
}

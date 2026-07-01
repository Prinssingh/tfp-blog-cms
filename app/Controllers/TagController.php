<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateTagDTO;
use App\Repositories\TagRepository;
use App\Services\TagService;

class TagController
{
    private TagService $tagService;

    public function __construct()
    {
        $this->tagService = new TagService(new TagRepository());
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    private function userId(Request $request): int
    {
        return (int) $request->param('_auth')->sub;
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];
        return Response::success($this->tagService->all($this->websiteId($request), $filters));
    }

    public function trash(Request $request): Response
    {
        return Response::success($this->tagService->trash($this->websiteId($request)));
    }

    public function show(Request $request): Response
    {
        $tag = $this->tagService->findById(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($tag);
    }

    public function store(Request $request): Response
    {
        $dto = new CreateTagDTO($request->body(), $this->websiteId($request), $this->userId($request));
        $tag = $this->tagService->create($dto);
        return Response::created($tag, 'Tag created successfully.');
    }

    public function update(Request $request): Response
    {
        $dto = new UpdateTagDTO($request->body(), $this->userId($request));
        $tag = $this->tagService->update(
            (int) $request->param('id'),
            $this->websiteId($request),
            $dto,
        );
        return Response::success($tag, 'Tag updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->tagService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success([], 'Tag moved to trash.');
    }

    public function restore(Request $request): Response
    {
        $tag = $this->tagService->restore(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($tag, 'Tag restored.');
    }

    public function forceDelete(Request $request): Response
    {
        $this->tagService->forceDelete(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success([], 'Tag permanently deleted.');
    }

    public function merge(Request $request): Response
    {
        $body = $request->body();
        $tag  = $this->tagService->merge(
            (int) ($body['source_id'] ?? 0),
            (int) ($body['target_id'] ?? 0),
            $this->websiteId($request),
        );
        return Response::success($tag, 'Tags merged successfully.');
    }
}

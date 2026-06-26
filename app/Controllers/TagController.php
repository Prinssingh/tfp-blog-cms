<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreateTagDTO;
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

    public function index(Request $request): Response
    {
        return Response::success(
            $this->tagService->all($this->websiteId($request), $request->query('search'))
        );
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
        $dto = new CreateTagDTO($request->body(), $this->websiteId($request));
        $tag = $this->tagService->create($dto);
        return Response::created($tag, 'Tag created successfully.');
    }

    public function update(Request $request): Response
    {
        $body = $request->body();
        $tag  = $this->tagService->update(
            (int) $request->param('id'),
            $this->websiteId($request),
            $body['name'] ?? '',
            $body['slug'] ?? null,
        );
        return Response::success($tag, 'Tag updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->tagService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success([], 'Tag deleted successfully.');
    }
}

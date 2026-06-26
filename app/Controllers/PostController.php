<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreatePostDTO;
use App\DTOs\UpdatePostDTO;
use App\Exceptions\ValidationException;
use App\Repositories\AuditRepository;
use App\Repositories\PostRepository;
use App\Repositories\TagRepository;
use App\Services\PostService;

class PostController
{
    private PostService $postService;

    public function __construct()
    {
        $this->postService = new PostService(
            new PostRepository(),
            new TagRepository(),
            new AuditRepository(),
        );
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
            'status'      => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'author_id'   => $request->query('author_id'),
            'search'      => $request->query('search'),
            'sort'        => $request->query('sort'),
            'order'       => $request->query('order'),
            'page'        => $request->query('page', 1),
            'limit'       => $request->query('limit', 20),
        ];

        $result = $this->postService->all($this->websiteId($request), $filters);

        return Response::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        );
    }

    public function show(Request $request): Response
    {
        $post = $this->postService->findById(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($post);
    }

    public function store(Request $request): Response
    {
        $dto  = new CreatePostDTO($request->body(), $this->websiteId($request), $this->userId($request));
        $post = $this->postService->create($dto);
        return Response::created($post, 'Post created successfully.');
    }

    public function update(Request $request): Response
    {
        $dto  = new UpdatePostDTO($request->body(), $this->userId($request));
        $post = $this->postService->update(
            (int) $request->param('id'),
            $this->websiteId($request),
            $dto,
        );
        return Response::success($post, 'Post updated successfully.');
    }

    public function publish(Request $request): Response
    {
        $post = $this->postService->publish(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success($post, 'Post published successfully.');
    }

    public function schedule(Request $request): Response
    {
        $scheduledAt = $request->body('scheduled_at');

        if (empty($scheduledAt)) {
            throw new ValidationException(['scheduled_at' => ['Scheduled date is required.']]);
        }

        $post = $this->postService->schedule(
            (int) $request->param('id'),
            $this->websiteId($request),
            $scheduledAt,
            $this->userId($request),
        );
        return Response::success($post, 'Post scheduled successfully.');
    }

    public function duplicate(Request $request): Response
    {
        $post = $this->postService->duplicate(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::created($post, 'Post duplicated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->postService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success([], 'Post deleted successfully.');
    }
}

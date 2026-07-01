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

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $filters = [
            'status'      => $request->query('status'),
            'author_id'   => $request->query('author_id'),
            'category_id' => $request->query('category_id'),
            'tag_id'      => $request->query('tag_id'),
            'search'      => $request->query('search'),
            'date_from'   => $request->query('date_from'),
            'date_to'     => $request->query('date_to'),
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

    public function trash(Request $request): Response
    {
        $result = $this->postService->trash($this->websiteId($request), [
            'page'  => $request->query('page', 1),
            'limit' => $request->query('limit', 20),
        ]);

        return Response::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        );
    }

    public function statusCounts(Request $request): Response
    {
        $counts = $this->postService->statusCounts($this->websiteId($request));
        return Response::success($counts);
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

    public function destroy(Request $request): Response
    {
        $this->postService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success([], 'Post deleted successfully.');
    }

    public function restoreFromTrash(Request $request): Response
    {
        $post = $this->postService->restoreFromTrash(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success($post, 'Post restored successfully.');
    }

    public function forceDelete(Request $request): Response
    {
        $this->postService->forceDelete(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success([], 'Post permanently deleted.');
    }

    // ── Workflow ──────────────────────────────────────────────────────────────

    public function submitReview(Request $request): Response
    {
        $post = $this->postService->submitReview(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
            $request->body('notes'),
        );
        return Response::success($post, 'Submitted for review.');
    }

    public function startReview(Request $request): Response
    {
        $post = $this->postService->startReview(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success($post, 'Review started.');
    }

    public function approve(Request $request): Response
    {
        $post = $this->postService->approve(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
            $request->body('notes'),
        );
        return Response::success($post, 'Post approved.');
    }

    public function reject(Request $request): Response
    {
        $post = $this->postService->reject(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
            $request->body('reason'),
        );
        return Response::success($post, 'Post rejected.');
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

    public function archive(Request $request): Response
    {
        $post = $this->postService->archive(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success($post, 'Post archived.');
    }

    // ── Revisions ─────────────────────────────────────────────────────────────

    public function revisions(Request $request): Response
    {
        $revisions = $this->postService->revisions(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($revisions);
    }

    public function revision(Request $request): Response
    {
        $revision = $this->postService->revision(
            (int) $request->param('id'),
            $this->websiteId($request),
            (int) $request->param('revision_id'),
        );
        return Response::success($revision);
    }

    public function restoreRevision(Request $request): Response
    {
        $post = $this->postService->restoreRevision(
            (int) $request->param('id'),
            $this->websiteId($request),
            (int) $request->param('revision_id'),
            $this->userId($request),
        );
        return Response::success($post, 'Revision restored.');
    }

    // ── Other ─────────────────────────────────────────────────────────────────

    public function preview(Request $request): Response
    {
        $token = $this->postService->createPreviewToken(
            (int) $request->param('id'),
            $this->websiteId($request),
            $this->userId($request),
        );
        return Response::success(['token' => $token]);
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
}

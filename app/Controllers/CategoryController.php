<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\UpdateCategoryDTO;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;

class CategoryController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService(new CategoryRepository());
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    public function index(Request $request): Response
    {
        $filters = [
            'parent_id' => $request->query('parent_id'),
            'search'    => $request->query('search'),
        ];

        return Response::success(
            $this->categoryService->all($this->websiteId($request), $filters)
        );
    }

    public function show(Request $request): Response
    {
        $category = $this->categoryService->findById(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success($category);
    }

    public function store(Request $request): Response
    {
        $dto      = new CreateCategoryDTO($request->body(), $this->websiteId($request));
        $category = $this->categoryService->create($dto);
        return Response::created($category, 'Category created successfully.');
    }

    public function update(Request $request): Response
    {
        $dto      = new UpdateCategoryDTO($request->body());
        $category = $this->categoryService->update(
            (int) $request->param('id'),
            $this->websiteId($request),
            $dto,
        );
        return Response::success($category, 'Category updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->categoryService->delete(
            (int) $request->param('id'),
            $this->websiteId($request),
        );
        return Response::success([], 'Category deleted successfully.');
    }
}

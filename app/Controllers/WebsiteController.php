<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreateWebsiteDTO;
use App\DTOs\UpdateWebsiteDTO;
use App\Repositories\WebsiteRepository;
use App\Services\WebsiteService;

class WebsiteController
{
    private WebsiteService $websiteService;

    public function __construct()
    {
        $this->websiteService = new WebsiteService(new WebsiteRepository());
    }

    public function index(Request $request): Response
    {
        return Response::success($this->websiteService->all());
    }

    public function show(Request $request): Response
    {
        $website = $this->websiteService->findById((int) $request->param('id'));
        return Response::success($website);
    }

    public function store(Request $request): Response
    {
        $dto     = new CreateWebsiteDTO($request->body());
        $website = $this->websiteService->create($dto);
        return Response::created($website, 'Website created successfully.');
    }

    public function update(Request $request): Response
    {
        $dto     = new UpdateWebsiteDTO($request->body());
        $website = $this->websiteService->update((int) $request->param('id'), $dto);
        return Response::success($website, 'Website updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $this->websiteService->delete((int) $request->param('id'));
        return Response::success([], 'Website deleted successfully.');
    }
}

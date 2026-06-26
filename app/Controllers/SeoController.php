<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\UpsertSeoDTO;
use App\Exceptions\ValidationException;
use App\Repositories\SeoRepository;

class SeoController
{
    private SeoRepository $seoRepository;

    private const VALID_ENTITY_TYPES = ['post', 'category', 'tag', 'author', 'page'];

    public function __construct()
    {
        $this->seoRepository = new SeoRepository();
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    private function validateEntityType(string $type): void
    {
        if (!in_array($type, self::VALID_ENTITY_TYPES, true)) {
            throw new ValidationException(['entity_type' => ['Invalid entity type.']]);
        }
    }

    public function show(Request $request): Response
    {
        $entityType = $request->param('entity_type');
        $entityId   = (int) $request->param('entity_id');

        $this->validateEntityType($entityType);

        $seo = $this->seoRepository->find($this->websiteId($request), $entityType, $entityId);

        return Response::success($seo ?? []);
    }

    public function upsert(Request $request): Response
    {
        $entityType = $request->param('entity_type');
        $entityId   = (int) $request->param('entity_id');

        $this->validateEntityType($entityType);

        $dto = new UpsertSeoDTO($request->body());
        $seo = $this->seoRepository->upsert($this->websiteId($request), $entityType, $entityId, $dto);

        return Response::success($seo, 'SEO metadata saved.');
    }
}

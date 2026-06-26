<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AnalyticsRepository;

class AnalyticsController
{
    private AnalyticsRepository $analyticsRepository;

    public function __construct()
    {
        $this->analyticsRepository = new AnalyticsRepository();
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    public function summary(Request $request): Response
    {
        $websiteId = $this->websiteId($request);
        $period    = $request->query('period', '30');

        $cacheKey = "analytics:summary:{$websiteId}:{$period}";

        $data = Cache::remember($cacheKey, 300, function () use ($websiteId, $period) {
            return $this->analyticsRepository->summary($websiteId, $period);
        });

        return Response::success($data);
    }

    public function postViews(Request $request): Response
    {
        $websiteId = $this->websiteId($request);
        $postId    = (int) $request->param('id');
        $days      = (int) $request->query('days', 30);

        $data = $this->analyticsRepository->postViews($postId, $websiteId, $days);

        return Response::success($data);
    }
}

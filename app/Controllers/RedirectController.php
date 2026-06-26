<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Repositories\RedirectRepository;

class RedirectController
{
    private RedirectRepository $redirectRepository;

    public function __construct()
    {
        $this->redirectRepository = new RedirectRepository();
    }

    private function websiteId(Request $request): int
    {
        return (int) ($request->param('_auth')->website_id ?? $request->query('website_id'));
    }

    public function index(Request $request): Response
    {
        return Response::success(
            $this->redirectRepository->all($this->websiteId($request))
        );
    }

    public function store(Request $request): Response
    {
        $body       = $request->body();
        $websiteId  = $this->websiteId($request);
        $oldUrl     = trim($body['old_url'] ?? '');
        $newUrl     = trim($body['new_url'] ?? '');
        $statusCode = (int) ($body['status_code'] ?? 301);

        $errors = [];
        if (empty($oldUrl))   $errors['old_url'][]     = 'Old URL is required.';
        if (empty($newUrl))   $errors['new_url'][]     = 'New URL is required.';
        if (!in_array($statusCode, [301, 302], true)) $errors['status_code'][] = 'Status code must be 301 or 302.';
        if (!empty($errors))  throw new ValidationException($errors);

        if ($this->redirectRepository->findByOldUrl($oldUrl, $websiteId) !== null) {
            throw new AppException('A redirect for this URL already exists.', 409);
        }

        $redirect = $this->redirectRepository->create($websiteId, $oldUrl, $newUrl, $statusCode);
        return Response::created($redirect, 'Redirect created.');
    }

    public function destroy(Request $request): Response
    {
        $this->redirectRepository->delete((int) $request->param('id'));
        return Response::success([], 'Redirect deleted.');
    }
}

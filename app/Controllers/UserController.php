<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService(
            new UserRepository(),
            new AuditRepository(),
        );
    }

    public function index(Request $request): Response
    {
        $filters = [
            'website_id' => $request->query('website_id'),
            'role'       => $request->query('role'),
            'status'     => $request->query('status'),
            'search'     => $request->query('search'),
        ];

        return Response::success($this->userService->all($filters));
    }

    public function show(Request $request): Response
    {
        $user = $this->userService->findById((int) $request->param('id'));
        return Response::success($user);
    }

    public function store(Request $request): Response
    {
        $actorId = (int) $request->param('_auth')->sub;
        $dto     = new CreateUserDTO($request->body());
        $user    = $this->userService->create($dto, $actorId);
        return Response::created($user, 'User created successfully.');
    }

    public function update(Request $request): Response
    {
        $actorId = (int) $request->param('_auth')->sub;
        $dto     = new UpdateUserDTO($request->body());
        $user    = $this->userService->update((int) $request->param('id'), $dto, $actorId);
        return Response::success($user, 'User updated successfully.');
    }

    public function destroy(Request $request): Response
    {
        $actorId = (int) $request->param('_auth')->sub;
        $this->userService->delete((int) $request->param('id'), $actorId);
        return Response::success([], 'User deleted successfully.');
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\LoginDTO;
use App\DTOs\RefreshTokenDTO;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new UserRepository(),
            new AuditRepository(),
        );
    }

    public function login(Request $request): Response
    {
        $dto    = new LoginDTO($request->body());
        $result = $this->authService->login($dto, $request->ip(), $request->header('user-agent', ''));

        return Response::success($result, 'Login successful.');
    }

    public function refresh(Request $request): Response
    {
        $dto    = new RefreshTokenDTO($request->body());
        $result = $this->authService->refresh($dto);

        return Response::success($result, 'Token refreshed.');
    }

    public function logout(Request $request): Response
    {
        $refreshToken = $request->body('refresh_token', '');

        if (!empty($refreshToken)) {
            $this->authService->logout($refreshToken);
        }

        return Response::success([], 'Logged out successfully.');
    }

    public function me(Request $request): Response
    {
        $auth   = $request->param('_auth');
        $userId = (int) $auth->sub;
        $user   = $this->authService->me($userId);

        return Response::success($user);
    }
}

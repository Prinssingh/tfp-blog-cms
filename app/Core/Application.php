<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AppException;
use Throwable;

class Application
{
    private static Application $instance;
    private Router $router;

    public function __construct()
    {
        self::$instance = $this;
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        try {
            $request = Request::capture();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (AppException $e) {
            Response::json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->getErrors(),
            ], $e->getStatusCode())->send();
        } catch (Throwable $e) {
            $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
            Response::json([
                'success' => false,
                'message' => $debug ? $e->getMessage() : 'Internal server error.',
                'errors'  => $debug ? ['trace' => $e->getTraceAsString()] : [],
            ], 500)->send();
        }
    }
}

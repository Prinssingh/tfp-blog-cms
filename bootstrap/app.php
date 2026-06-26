<?php

declare(strict_types=1);

use App\Core\Application;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$dotenv->required([
    'APP_URL',
    'DB_HOST',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'JWT_SECRET',
]);

date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

$app = new Application();

require BASE_PATH . '/routes/api.php';
require BASE_PATH . '/routes/public.php';

return $app;
